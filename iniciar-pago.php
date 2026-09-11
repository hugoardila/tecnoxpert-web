<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

tx_send_page_headers(true);
tx_start_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

if (
    !tx_verify_csrf($_POST['csrf_token'] ?? null)
    || (string)($_POST['terms_accept'] ?? '') !== '1'
    || (string)($_POST['refund_accept'] ?? '') !== '1'
) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

$access = $_SESSION['quote_access'] ?? [];
$reference = tx_normalize_reference($_POST['reference'] ?? '');
$accessTime = $reference !== '' ? (int)($access[$reference] ?? 0) : 0;
if ($reference === '' || $accessTime < time() - 1800) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

$quote = tx_find_quote($reference);
$paymentUrl = $quote !== null ? tx_valid_payment_url($quote) : null;
$isDemo = $quote !== null && tx_quote_is_demo($quote);
if ($quote === null || !tx_quote_is_payable($quote) || (!$isDemo && $paymentUrl === null)) {
    header('Location: /pagar?estado=no-habilitado', true, 303);
    exit;
}

try {
    $directory = tx_ensure_private_directory('payment-intents');
    $record = [
        'id' => 'TXP-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)),
        'created_at' => date(DATE_ATOM),
        'reference' => $reference,
        'amount' => (float)($quote['total'] ?? 0),
        'currency' => (string)($quote['currency'] ?? 'COP'),
        'provider' => $isDemo ? 'demo' : (string)($quote['payment_provider'] ?? 'external'),
        'terms_accepted' => true,
        'refund_policy_accepted' => true,
        'ip_hash' => hash('sha256', tx_client_ip()),
    ];
    file_put_contents(
        $directory . DIRECTORY_SEPARATOR . 'intents.jsonl',
        json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
} catch (Throwable $error) {
    error_log('TECNOXPERT payment intent error: ' . $error->getMessage());
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

header('Cache-Control: no-store, max-age=0');
if ($isDemo) {
    $_SESSION['demo_payment_intents'][$reference] = time();
    header('Location: /pago-demo?ref=' . rawurlencode($reference), true, 303);
    exit;
}
header('Location: ' . $paymentUrl, true, 303);
exit;
