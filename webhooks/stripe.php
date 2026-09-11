<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/payments.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

try {
    $payload = (string)file_get_contents('php://input');
    $event = tx_payment_stripe_event($payload, trim((string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '')));
    $type = (string)($event['type'] ?? '');
    $session = $event['data']['object'] ?? null;
    if (!is_array($session) || !str_starts_with($type, 'checkout.session.')) {
        http_response_code(200);
        echo 'IGNORED';
        exit;
    }
    $metadata = is_array($session['metadata'] ?? null) ? $session['metadata'] : [];
    if ((string)($metadata['app'] ?? '') !== 'tecnoxpert') {
        http_response_code(200);
        echo 'IGNORED';
        exit;
    }
    $attemptId = trim((string)($metadata['attempt_id'] ?? ''));
    $sessionId = trim((string)($session['id'] ?? ''));
    $attempt = tx_payment_find_attempt($attemptId);
    if (
        $attempt === null
        || (string)($attempt['provider'] ?? '') !== 'stripe'
        || (string)($attempt['quote_reference'] ?? '') !== (string)($metadata['quote_reference'] ?? '')
        || (string)($attempt['provider_id'] ?? '') !== $sessionId
    ) {
        throw new RuntimeException('Intento Stripe desconocido.');
    }
    if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        tx_payment_verify_stripe($attempt, $sessionId);
    } elseif (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
        tx_payment_update_attempt($attemptId, ['status' => 'declined']);
    }
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
} catch (Throwable $error) {
    error_log('TECNOXPERT Stripe webhook rejected: ' . $error->getMessage());
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'INVALID';
}
