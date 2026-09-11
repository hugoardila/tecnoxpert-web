<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/payments.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

try {
    tx_payment_process_epayco($_POST);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
} catch (Throwable $error) {
    error_log('TECNOXPERT ePayco webhook rejected: ' . $error->getMessage());
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'INVALID';
}
