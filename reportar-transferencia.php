<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

tx_start_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$attemptId = trim((string)($_POST['attempt'] ?? ''));
$access = trim((string)($_POST['access'] ?? ''));
$attempt = tx_payment_find_attempt($attemptId);
if (
    !tx_verify_csrf($_POST['csrf_token'] ?? null)
    || $attempt === null
    || !tx_payment_attempt_matches_token($attempt, $access)
    || (string)($attempt['provider'] ?? '') !== 'bank_transfer'
) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

$reference = (string)$attempt['quote_reference'];
try {
    tx_payment_report_transfer($attempt);
    $_SESSION['quote_access'][$reference] = time();
    $state = 'transferencia-reportada';
} catch (Throwable $error) {
    error_log('TECNOXPERT transfer report failed [' . $attemptId . ']: ' . $error->getMessage());
    $state = 'error-pago';
}

header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=' . $state, true, 303);
exit;
