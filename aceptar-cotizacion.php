<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

tx_send_page_headers(true);
tx_start_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

$reference = tx_normalize_reference($_POST['reference'] ?? '');
$hasAccess = $reference !== ''
    && (int)($_SESSION['quote_access'][$reference] ?? 0) > time() - 1800;
$acceptedQuote = (string)($_POST['quote_accept'] ?? '') === '1';
$acceptedTerms = (string)($_POST['terms_accept'] ?? '') === '1';
$acceptedRefunds = (string)($_POST['refund_accept'] ?? '') === '1';

if (
    !tx_verify_csrf($_POST['csrf_token'] ?? null)
    || !$hasAccess
    || !$acceptedQuote
    || !$acceptedTerms
    || !$acceptedRefunds
) {
    header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=aceptacion-invalida', true, 303);
    exit;
}

try {
    $quote = tx_accept_quote($reference);
    if ($quote === null) {
        header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=aceptacion-invalida', true, 303);
        exit;
    }
    $_SESSION['quote_access'][$reference] = time();
    header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=aceptada', true, 303);
} catch (Throwable $error) {
    error_log('TECNOXPERT quote acceptance error: ' . $error->getMessage());
    header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=aceptacion-invalida', true, 303);
}
exit;
