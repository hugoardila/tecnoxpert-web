<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const TX_ADMIN_IDLE_SECONDS = 1800;
const TX_ADMIN_MAX_LOGIN_ATTEMPTS = 5;
const TX_ADMIN_LOGIN_WINDOW = 900;

function tx_admin_config(): ?array
{
    $environmentUser = trim((string)(getenv('TECNOXPERT_ADMIN_USER') ?: ''));
    $environmentHash = trim((string)(getenv('TECNOXPERT_ADMIN_PASSWORD_HASH') ?: ''));
    if ($environmentUser !== '' && $environmentHash !== '') {
        return ['username' => $environmentUser, 'password_hash' => $environmentHash];
    }

    $file = tx_private_path('admin.json');
    if (!is_file($file)) {
        return null;
    }

    $decoded = json_decode((string)file_get_contents($file), true);
    if (
        !is_array($decoded)
        || !is_string($decoded['username'] ?? null)
        || !is_string($decoded['password_hash'] ?? null)
    ) {
        return null;
    }

    return $decoded;
}

function tx_admin_login_attempt_file(): string
{
    $directory = tx_ensure_private_directory('rate-limits/admin-login');
    return $directory . DIRECTORY_SEPARATOR . hash('sha256', tx_client_ip()) . '.json';
}

function tx_admin_login(string $username, string $password): string
{
    $config = tx_admin_config();
    if ($config === null) {
        return 'not_configured';
    }

    $attemptFile = tx_admin_login_attempt_file();
    $now = time();
    $attempts = [];
    if (is_file($attemptFile)) {
        $decoded = json_decode((string)file_get_contents($attemptFile), true);
        if (is_array($decoded)) {
            $attempts = array_values(array_filter(
                $decoded,
                static fn(mixed $timestamp): bool => is_int($timestamp) && $timestamp > $now - TX_ADMIN_LOGIN_WINDOW
            ));
        }
    }

    if (count($attempts) >= TX_ADMIN_MAX_LOGIN_ATTEMPTS) {
        return 'locked';
    }

    $validUser = hash_equals((string)$config['username'], trim($username));
    $validPassword = password_verify($password, (string)$config['password_hash']);
    if (!$validUser || !$validPassword) {
        $attempts[] = $now;
        file_put_contents($attemptFile, json_encode($attempts), LOCK_EX);
        @chmod($attemptFile, 0640);
        return 'invalid';
    }

    @unlink($attemptFile);
    tx_start_session();
    session_regenerate_id(true);
    $_SESSION['tx_admin'] = [
        'username' => (string)$config['username'],
        'authenticated_at' => $now,
        'last_activity' => $now,
        'user_agent_hash' => hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? '')),
    ];
    unset($_SESSION['csrf_token']);
    tx_csrf_token();
    return 'ok';
}

function tx_admin_is_authenticated(): bool
{
    tx_start_session();
    $admin = $_SESSION['tx_admin'] ?? null;
    if (!is_array($admin)) {
        return false;
    }

    $lastActivity = (int)($admin['last_activity'] ?? 0);
    $userAgentHash = (string)($admin['user_agent_hash'] ?? '');
    $expectedAgentHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (
        $lastActivity < time() - TX_ADMIN_IDLE_SECONDS
        || $userAgentHash === ''
        || !hash_equals($userAgentHash, $expectedAgentHash)
    ) {
        unset($_SESSION['tx_admin']);
        return false;
    }

    $_SESSION['tx_admin']['last_activity'] = time();
    return true;
}

function tx_admin_username(): string
{
    return (string)($_SESSION['tx_admin']['username'] ?? 'Administrador');
}

function tx_admin_logout(): void
{
    tx_start_session();
    unset($_SESSION['tx_admin'], $_SESSION['tx_admin_flash']);
    session_regenerate_id(true);
}

function tx_admin_flash(string $type, string $message, ?string $link = null): void
{
    tx_start_session();
    $_SESSION['tx_admin_flash'] = [
        'type' => $type,
        'message' => $message,
        'link' => $link,
    ];
}

function tx_admin_take_flash(): ?array
{
    tx_start_session();
    $flash = $_SESSION['tx_admin_flash'] ?? null;
    unset($_SESSION['tx_admin_flash']);
    return is_array($flash) ? $flash : null;
}

function tx_admin_load_requests(): array
{
    $file = tx_private_path('contactos/contactos.jsonl');
    if (!is_file($file)) {
        return [];
    }

    $handle = fopen($file, 'rb');
    if ($handle === false) {
        return [];
    }

    $requests = [];
    try {
        while (($line = fgets($handle)) !== false) {
            $decoded = json_decode(trim($line), true);
            if (!is_array($decoded) || !is_string($decoded['id'] ?? null)) {
                continue;
            }
            $requests[] = $decoded;
        }
    } finally {
        fclose($handle);
    }

    usort($requests, static function (array $left, array $right): int {
        return strcmp((string)($right['received_at'] ?? ''), (string)($left['received_at'] ?? ''));
    });
    return $requests;
}

function tx_admin_load_request_states(): array
{
    $file = tx_private_path('contactos/estados.json');
    if (!is_file($file)) {
        return [];
    }
    $decoded = json_decode((string)file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function tx_admin_write_json_atomic(string $file, array $data): void
{
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No fue posible preparar el almacenamiento.');
    }

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        throw new RuntimeException('No fue posible serializar la información.');
    }

    $permissionSource = is_file($file) ? $file : $directory;
    $permissionStat = @stat($permissionSource);
    $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('No fue posible guardar la información.');
    }
    @chmod($temporary, 0640);
    if (is_array($permissionStat)) {
        @chown($temporary, (int)$permissionStat['uid']);
        @chgrp($temporary, (int)$permissionStat['gid']);
    }
    if (!rename($temporary, $file)) {
        @unlink($temporary);
        throw new RuntimeException('No fue posible publicar la actualización.');
    }
}

function tx_admin_update_request_state(string $requestId, string $status, array $extra = []): void
{
    $allowedStatuses = ['new', 'in_review', 'quoted', 'rejected'];
    if (!in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Estado de solicitud inválido.');
    }

    $file = tx_private_path('contactos/estados.json');
    $lockFile = $file . '.lock';
    $lock = fopen($lockFile, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('No fue posible bloquear el estado de solicitudes.');
    }

    try {
        $states = tx_admin_load_request_states();
        $current = is_array($states[$requestId] ?? null) ? $states[$requestId] : [];
        $states[$requestId] = array_merge($current, $extra, [
            'status' => $status,
            'updated_at' => date(DATE_ATOM),
            'updated_by' => tx_admin_username(),
        ]);
        tx_admin_write_json_atomic($file, $states);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function tx_admin_request_status(array $request, array $states, array $quotes): array
{
    $requestId = (string)($request['id'] ?? '');
    $state = is_array($states[$requestId] ?? null) ? $states[$requestId] : [];
    foreach ($quotes as $quote) {
        if (is_array($quote) && (string)($quote['source_request_id'] ?? '') === $requestId) {
            return array_merge($state, [
                'status' => 'quoted',
                'quote_reference' => (string)($quote['reference'] ?? ''),
            ]);
        }
    }
    return array_merge(['status' => 'new'], $state);
}

function tx_admin_status_label(string $status): string
{
    return match ($status) {
        'in_review' => 'En revisión',
        'quoted' => 'Cotizada',
        'rejected' => 'Rechazada',
        default => 'Nueva',
    };
}

function tx_admin_find_request(array $requests, string $requestId): ?array
{
    foreach ($requests as $request) {
        if (is_array($request) && (string)($request['id'] ?? '') === $requestId) {
            return $request;
        }
    }
    return null;
}

function tx_admin_find_quote_for_request(array $quotes, string $requestId): ?array
{
    foreach ($quotes as $quote) {
        if (is_array($quote) && (string)($quote['source_request_id'] ?? '') === $requestId) {
            return $quote;
        }
    }
    return null;
}

function tx_admin_create_quote(array $request, array $input): array
{
    $requestId = (string)($request['id'] ?? '');
    $name = tx_clean_text($request['name'] ?? '', 120);
    $email = tx_normalize_email($request['email'] ?? '');
    $title = tx_clean_text($input['title'] ?? '', 180);
    $description = tx_clean_text($input['description'] ?? '', 3000);
    $delivery = tx_clean_text($input['delivery'] ?? '', 180);
    $currency = strtoupper(tx_clean_text($input['currency'] ?? 'COP', 3));
    $subtotal = filter_var($input['subtotal'] ?? null, FILTER_VALIDATE_FLOAT);
    $tax = filter_var($input['tax'] ?? '0', FILTER_VALIDATE_FLOAT);
    $expiresInput = trim((string)($input['expires'] ?? ''));
    $paymentUrl = trim((string)($input['payment_url'] ?? ''));

    if ($requestId === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('La solicitud no contiene datos válidos del cliente.');
    }
    if ($title === '' || $description === '' || $delivery === '') {
        throw new InvalidArgumentException('Completa el servicio, alcance y plazo de entrega.');
    }
    if ($subtotal === false || $tax === false || $subtotal <= 0 || $tax < 0) {
        throw new InvalidArgumentException('Ingresa valores válidos para subtotal e impuestos.');
    }
    if (!in_array($currency, ['COP', 'USD'], true)) {
        throw new InvalidArgumentException('La moneda seleccionada no está permitida.');
    }
    if ($paymentUrl !== '' && tx_valid_payment_url(['payment_url' => $paymentUrl]) === null) {
        throw new InvalidArgumentException('El enlace de pago no pertenece a un proveedor autorizado.');
    }

    $expiresAt = null;
    if ($expiresInput !== '') {
        $expiresDate = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresInput);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $expiresDate === false
            || (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('La fecha de vencimiento no es válida.');
        }
        $expiresAt = $expiresDate->setTime(23, 59, 59)->format(DATE_ATOM);
        if ($expiresDate < new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha de vencimiento no puede estar en el pasado.');
        }
    }

    $file = tx_private_path('cotizaciones.json');
    $lock = fopen($file . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('No fue posible bloquear las cotizaciones.');
    }

    try {
        $quotes = [];
        if (is_file($file)) {
            $decoded = json_decode((string)file_get_contents($file), true);
            if (!is_array($decoded)) {
                throw new RuntimeException('El archivo de cotizaciones no es válido.');
            }
            $quotes = $decoded;
        }

        foreach ($quotes as $quote) {
            if (is_array($quote) && (string)($quote['source_request_id'] ?? '') === $requestId) {
                throw new RuntimeException('Esta solicitud ya tiene una cotización asociada.');
            }
        }

        $datePart = date('Ymd');
        $sequence = 1;
        foreach ($quotes as $quote) {
            $reference = (string)($quote['reference'] ?? '');
            if (preg_match('/^COT-' . preg_quote($datePart, '/') . '-(\d{3})$/', $reference, $matches)) {
                $sequence = max($sequence, (int)$matches[1] + 1);
            }
        }
        $reference = sprintf('COT-%s-%03d', $datePart, $sequence);
        $token = bin2hex(random_bytes(24));
        $total = round((float)$subtotal + (float)$tax, 2);
        $quote = [
            'reference' => $reference,
            'source_request_id' => $requestId,
            'lookup_email_hash' => hash('sha256', $email),
            'access_token_hash' => hash('sha256', $token),
            'customer_name' => $name,
            'customer_email' => $email,
            'title' => $title,
            'description' => $description,
            'items' => [['description' => $title, 'amount' => (float)$subtotal]],
            'subtotal' => (float)$subtotal,
            'tax' => (float)$tax,
            'total' => $total,
            'currency' => $currency,
            'delivery_estimate' => $delivery,
            'status' => 'pending_acceptance',
            'status_label' => 'Pendiente de aceptación del cliente',
            'expires_at' => $expiresAt,
            'payment_provider' => $paymentUrl !== '' ? 'external' : null,
            'payment_url' => $paymentUrl !== '' ? $paymentUrl : null,
            'provider_payment_id' => null,
            'is_demo' => false,
            'created_at' => date(DATE_ATOM),
            'created_by' => tx_admin_username(),
        ];
        $quotes[] = $quote;
        tx_admin_write_json_atomic($file, $quotes);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    tx_admin_update_request_state($requestId, 'quoted', [
        'quote_reference' => $reference,
        'rejection_reason' => null,
    ]);

    return [
        'quote' => $quote,
        'private_link' => tx_url('/pagar?ref=' . rawurlencode($reference) . '&token=' . rawurlencode($token)),
    ];
}
