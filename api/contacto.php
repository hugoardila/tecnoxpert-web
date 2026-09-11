<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

tx_send_page_headers(true);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    tx_json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

tx_start_session();

if (!tx_verify_csrf($_POST['csrf_token'] ?? null)) {
    tx_json_response(['success' => false, 'message' => 'La sesión del formulario venció. Recarga la página e intenta nuevamente.'], 419);
}

$formTimestamp = filter_var($_POST['form_timestamp'] ?? null, FILTER_VALIDATE_INT);
$formProof = $_POST['form_proof'] ?? null;
if (!is_int($formTimestamp) || !tx_verify_form_proof($formTimestamp, is_string($formProof) ? $formProof : null)) {
    tx_json_response(['success' => false, 'message' => 'No fue posible validar el formulario. Recarga la página.'], 422);
}

$elapsed = time() - $formTimestamp;
if ($elapsed < 2 || $elapsed > 7200) {
    tx_json_response(['success' => false, 'message' => 'El formulario venció. Recarga la página e intenta nuevamente.'], 422);
}

if (tx_clean_text($_POST['company_website'] ?? '', 200) !== '') {
    tx_json_response(['success' => true, 'message' => 'Solicitud recibida correctamente.'], 201);
}

$name = tx_clean_text($_POST['nombre'] ?? '', 120);
$email = tx_normalize_email($_POST['email'] ?? '');
$phone = tx_clean_text($_POST['telefono'] ?? '', 30);
$city = tx_clean_text($_POST['ciudad'] ?? '', 100);
$service = tx_clean_text($_POST['servicio'] ?? '', 160);
$reference = tx_clean_text($_POST['referencia'] ?? '', 40);
$message = tx_clean_text($_POST['mensaje'] ?? '', 3000);
$privacyAccepted = (string)($_POST['privacy_accept'] ?? '') === '1';

if ($name === '' || $email === '' || $phone === '' || $city === '' || $service === '' || $message === '') {
    tx_json_response(['success' => false, 'message' => 'Completa todos los campos obligatorios.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    tx_json_response(['success' => false, 'message' => 'Ingresa un correo electrónico válido.'], 422);
}
if (!preg_match('/^[0-9+() .-]{7,30}$/', $phone)) {
    tx_json_response(['success' => false, 'message' => 'Ingresa un teléfono válido.'], 422);
}
if (!$privacyAccepted) {
    tx_json_response(['success' => false, 'message' => 'Debes aceptar el tratamiento de datos para enviar la solicitud.'], 422);
}
if ($reference !== '' && tx_normalize_reference($reference) === '') {
    tx_json_response(['success' => false, 'message' => 'La referencia debe contener únicamente letras, números y guiones.'], 422);
}

try {
    $rateDirectory = tx_ensure_private_directory('rate-limits/contact');
    $rateFile = $rateDirectory . DIRECTORY_SEPARATOR . hash('sha256', tx_client_ip()) . '.json';
    $now = time();
    $attempts = [];
    if (is_file($rateFile)) {
        $decoded = json_decode((string)file_get_contents($rateFile), true);
        if (is_array($decoded)) {
            $attempts = array_values(array_filter($decoded, static fn($timestamp): bool => is_int($timestamp) && $timestamp > $now - 600));
        }
    }
    if (count($attempts) >= 5) {
        tx_json_response(['success' => false, 'message' => 'Alcanzaste el límite temporal de solicitudes. Intenta nuevamente en unos minutos.'], 429);
    }
    $attempts[] = $now;
    file_put_contents($rateFile, json_encode($attempts), LOCK_EX);
    @chmod($rateFile, 0640);

    $contactDirectory = tx_ensure_private_directory('contactos');
    $record = [
        'id' => 'TXC-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)),
        'received_at' => date(DATE_ATOM),
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'service' => $service,
        'reference' => $reference !== '' ? tx_normalize_reference($reference) : null,
        'message' => $message,
        'privacy_accepted_at' => date(DATE_ATOM),
        'ip_hash' => hash('sha256', tx_client_ip()),
        'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300, 'UTF-8'),
    ];
    $written = file_put_contents(
        $contactDirectory . DIRECTORY_SEPARATOR . 'contactos.jsonl',
        json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    if ($written === false) {
        throw new RuntimeException('No se pudo registrar la solicitud.');
    }
    @chmod($contactDirectory . DIRECTORY_SEPARATOR . 'contactos.jsonl', 0640);

    $mailEnabled = getenv('TECNOXPERT_MAIL_ENABLED') === '1';
    if ($mailEnabled) {
        $subject = 'Solicitud web ' . $record['id'] . ' - ' . $service;
        $body = "Solicitud recibida desde tecnoxpert.com\n\n"
            . "ID: {$record['id']}\nNombre: {$name}\nCorreo: {$email}\nTeléfono: {$phone}\n"
            . "Ciudad: {$city}\nServicio: {$service}\nReferencia: " . ($record['reference'] ?? 'Sin referencia')
            . "\n\nMensaje:\n{$message}\n";
        $safeName = preg_replace('/[^\pL\pN ._-]+/u', '', $name) ?: 'Cliente';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: TECNOXPERT Web <' . TX_EMAIL_SALES . '>',
            'Reply-To: ' . $safeName . ' <' . $email . '>',
        ];
        @mail(TX_EMAIL_SALES, $subject, $body, implode("\r\n", $headers));
    }

    tx_json_response([
        'success' => true,
        'message' => 'Mensaje recibido correctamente. Conserva el identificador ' . $record['id'] . ' para seguimiento.',
        'request_id' => $record['id'],
    ], 201);
} catch (Throwable $error) {
    error_log('TECNOXPERT contact error: ' . $error->getMessage());
    tx_json_response(['success' => false, 'message' => 'No fue posible registrar la solicitud. Escríbenos por WhatsApp o intenta nuevamente.'], 500);
}

