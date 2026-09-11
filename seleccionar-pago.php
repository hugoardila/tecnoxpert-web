<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

tx_start_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$reference = tx_normalize_reference($_POST['reference'] ?? '');
$provider = trim((string)($_POST['provider'] ?? ''));
$payerCountry = tx_payment_normalize_country($_POST['payer_country'] ?? '');
$hasAccess = $reference !== ''
    && (int)($_SESSION['quote_access'][$reference] ?? 0) > time() - 1800;

if (!tx_verify_csrf($_POST['csrf_token'] ?? null) || !$hasAccess) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

$quote = tx_find_quote($reference);
if ($quote === null || !tx_quote_is_payable($quote)) {
    header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=no-habilitado', true, 303);
    exit;
}

try {
    $payment = tx_payment_start($quote, $provider, $payerCountry);
    $redirectUrl = trim((string)($payment['redirect_url'] ?? ''));
    if ($redirectUrl === '' || !filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('El proveedor no devolvió una URL válida.');
    }
    $_SESSION['quote_access'][$reference] = time();
    header('Location: ' . $redirectUrl, true, 303);
    exit;
} catch (Throwable $error) {
    error_log('TECNOXPERT payment start failed [' . $reference . '/' . $provider . '/' . $payerCountry . ']: ' . $error->getMessage());
    $_SESSION['payment_error'] = 'No fue posible abrir el método de pago. Intenta nuevamente o selecciona otra opción.';
    header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=error-pago', true, 303);
    exit;
}
