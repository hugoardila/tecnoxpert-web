<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

$attemptId = trim((string)($_GET['attempt'] ?? ''));
$access = trim((string)($_GET['access'] ?? ''));
$attempt = tx_payment_find_attempt($attemptId);

if (
    $attempt === null
    || (string)($attempt['provider'] ?? '') !== 'epayco'
    || !tx_payment_attempt_matches_token($attempt, $access)
    || (string)($attempt['provider_id'] ?? '') === ''
) {
    http_response_code(404);
    exit;
}

$quote = tx_find_quote((string)$attempt['quote_reference']);
if (
    $quote === null
    || !tx_quote_is_payable($quote)
    || abs((float)$quote['total'] - (float)($attempt['quote_amount'] ?? $attempt['amount'])) > 0.01
    || strtoupper((string)$quote['currency']) !== strtoupper((string)($attempt['quote_currency'] ?? $attempt['currency']))
) {
    http_response_code(409);
    exit;
}

tx_payment_send_epayco_headers();
$returnUrl = tx_url('/retorno-pago?provider=epayco&attempt=' . rawurlencode($attemptId)
    . '&access=' . rawurlencode($access));
$quoteUrl = tx_url('/pagar?ref=' . rawurlencode((string)$attempt['quote_reference']));
$nonce = tx_nonce();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pago con ePayco | TECNOXPERT</title>
  <link rel="stylesheet" href="/assets/css/site.css?v=20260723e">
</head>
<body class="gateway-page">
  <main class="gateway-shell">
    <img src="/assets/img/logo.png" alt="TECNOXPERT" width="180" height="52">
    <p class="eyebrow">Pago protegido</p>
    <h1>Abriendo ePayco</h1>
    <p>Referencia <strong><?= tx_e((string)$attempt['quote_reference']) ?></strong></p>
    <p class="gateway-amount"><?= tx_e(tx_money((float)$attempt['amount'], (string)$attempt['currency'])) ?></p>
    <p id="gateway-status" role="status">Espera mientras se abre la ventana segura de pago.</p>
    <a class="button button-secondary" href="<?= tx_e($quoteUrl) ?>">Volver a la cotización</a>
  </main>
  <script src="https://checkout.epayco.co/checkout-v2.js"></script>
  <script nonce="<?= tx_e($nonce) ?>">
    (() => {
      const status = document.getElementById('gateway-status');
      const returnUrl = <?= json_encode($returnUrl, JSON_UNESCAPED_SLASHES) ?>;
      const checkout = new ePayco.checkout({
        sessionId: <?= json_encode((string)$attempt['provider_id']) ?>,
        type: 'onpage',
        test: false
      });
      checkout.onResponse((response) => {
        const reference = response && (response.ref_payco || response.x_ref_payco || response.data?.ref_payco);
        window.location.assign(returnUrl + (reference ? '&ref_payco=' + encodeURIComponent(reference) : ''));
      });
      checkout.onErrors(() => {
        status.textContent = 'ePayco no pudo abrir el proceso. Puedes volver e intentarlo nuevamente.';
      });
      checkout.onClosed(() => {
        status.textContent = 'La ventana de pago fue cerrada. No se realizó ningún cobro desde esta página.';
      });
      checkout.open();
    })();
  </script>
</body>
</html>
