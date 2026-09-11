<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/payments.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    $payload = $_POST;
}
$paymentId = trim((string)($payload['payment_id'] ?? $payload['id'] ?? ''));

try {
    if ($paymentId === '') {
        throw new RuntimeException('Falta el identificador del pago.');
    }
    $attempt = tx_payment_find_attempt_by_provider_id('dlocal_go', $paymentId);
    if ($attempt === null) {
        throw new RuntimeException('Pago dLocal Go desconocido.');
    }
    tx_payment_verify_dlocal($attempt, $paymentId);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
} catch (Throwable $error) {
    error_log('TECNOXPERT dLocal Go webhook rejected: ' . $error->getMessage());
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'INVALID';
}
