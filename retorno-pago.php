<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

$provider = trim((string)($_GET['provider'] ?? ''));
$attemptId = trim((string)($_GET['attempt'] ?? ''));
$access = trim((string)($_GET['access'] ?? ''));
$attempt = tx_payment_find_attempt($attemptId);

if (
    $attempt === null
    || (string)($attempt['provider'] ?? '') !== $provider
    || !tx_payment_attempt_matches_token($attempt, $access)
) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

$reference = (string)$attempt['quote_reference'];
tx_start_session();
$_SESSION['quote_access'][$reference] = time();

try {
    $result = match ($provider) {
        'epayco' => tx_payment_verify_epayco_reference($attempt, trim((string)($_GET['ref_payco'] ?? ''))),
        'dlocal_go' => tx_payment_verify_dlocal($attempt),
        'stripe' => tx_payment_verify_stripe($attempt, trim((string)($_GET['session_id'] ?? ''))),
        'paypal' => (
            trim((string)($_GET['token'] ?? '')) === (string)($attempt['provider_id'] ?? '')
                ? tx_payment_capture_paypal($attempt)
                : throw new RuntimeException('La orden PayPal no coincide.')
        ),
        default => throw new RuntimeException('Proveedor de pago no reconocido.'),
    };
    $state = $result === 'paid'
        ? 'pagado'
        : ($result === 'declined' ? 'pago-rechazado' : 'pago-pendiente');
} catch (Throwable $error) {
    error_log('TECNOXPERT payment return failed [' . $attemptId . ']: ' . $error->getMessage());
    $state = 'error-pago';
}

header('Location: /pagar?ref=' . rawurlencode($reference) . '&estado=' . $state, true, 303);
exit;
