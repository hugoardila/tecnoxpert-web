<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

$attemptId = trim((string)($_GET['attempt'] ?? ''));
$access = trim((string)($_GET['access'] ?? ''));
$attempt = tx_payment_find_attempt($attemptId);
if (
    $attempt === null
    || (string)($attempt['provider'] ?? '') !== 'bank_transfer'
    || !tx_payment_attempt_matches_token($attempt, $access)
) {
    http_response_code(404);
    exit;
}

$quote = tx_find_quote((string)$attempt['quote_reference']);
if ($quote === null || !tx_quote_is_payable($quote)) {
    http_response_code(409);
    exit;
}

tx_start_session();
$_SESSION['quote_access'][(string)$attempt['quote_reference']] = time();
$csrfToken = tx_csrf_token();
$bank = tx_payment_config()['bank_transfer'] ?? [];
$payerCountry = tx_payment_normalize_country($attempt['payer_country'] ?? '');
$payerCountryName = tx_payment_countries()[$payerCountry] ?? 'País seleccionado';
$quoteAmount = (float)($attempt['quote_amount'] ?? $attempt['amount']);
$quoteCurrency = (string)($attempt['quote_currency'] ?? $attempt['currency']);
$localAmount = (float)($attempt['local_amount'] ?? $attempt['amount']);
$localCurrency = (string)($attempt['local_currency'] ?? $attempt['currency']);
$pageTitle = 'Transferencia bancaria | TECNOXPERT';
$pageDescription = 'Datos para pagar una cotización de TECNOXPERT mediante transferencia bancaria.';
$canonicalPath = '/pagar';
$activeNav = 'pagos';
$noIndex = true;
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero page-hero-compact">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><a href="/pagar">Cotización</a><span>/</span><span>Transferencia</span></nav>
    <h1>Transferencia bancaria</h1>
    <p>Transfiere el valor exacto e incluye la referencia de la cotización.</p>
  </div>
</header>

<section class="section">
  <div class="shell transfer-layout">
    <section class="transfer-details" aria-labelledby="transfer-title">
      <p class="eyebrow">Datos bancarios</p>
      <h2 id="transfer-title"><?= tx_e((string)($bank['bank'] ?? 'Cuenta registrada')) ?></h2>
      <dl class="summary-list">
        <div><dt>Tipo de cuenta</dt><dd><?= tx_e((string)($bank['account_type'] ?? 'Cuenta de ahorros')) ?></dd></div>
        <div><dt>Número de cuenta</dt><dd><strong><?= tx_e((string)($bank['account'] ?? '')) ?></strong></dd></div>
        <div><dt>Titular</dt><dd><?= tx_e((string)($bank['holder'] ?? TX_NAME)) ?></dd></div>
        <div><dt>Referencia</dt><dd><strong><?= tx_e((string)$attempt['quote_reference']) ?></strong></dd></div>
        <div><dt>País del cliente</dt><dd><?= tx_e($payerCountryName) ?></dd></div>
        <div><dt>Cotización original</dt><dd><?= tx_e(tx_money($quoteAmount, $quoteCurrency)) ?></dd></div>
        <?php if (strtoupper($localCurrency) !== strtoupper((string)$attempt['currency'])): ?>
          <div><dt>Equivalente referencial</dt><dd><?= tx_e(tx_money($localAmount, $localCurrency)) ?></dd></div>
        <?php endif; ?>
        <div class="summary-total"><dt>Valor exacto</dt><dd><?= tx_e(tx_money((float)$attempt['amount'], (string)$attempt['currency'])) ?></dd></div>
      </dl>
      <p class="field-help">La transferencia se recibe en pesos colombianos. El equivalente local es informativo y tu banco puede aplicar su propia tasa o comisión.</p>
    </section>

    <section class="form-panel" aria-labelledby="report-title">
      <h2 id="report-title">Reportar transferencia</h2>
      <p>Después de transferir, registra el aviso. El pago quedará pendiente hasta que TECNOXPERT valide el movimiento bancario.</p>
      <form action="/reportar-transferencia" method="post">
        <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
        <input type="hidden" name="attempt" value="<?= tx_e($attemptId) ?>">
        <input type="hidden" name="access" value="<?= tx_e($access) ?>">
        <button class="button" type="submit">Ya realicé la transferencia</button>
      </form>
      <a class="button button-secondary" href="/pagar?ref=<?= rawurlencode((string)$attempt['quote_reference']) ?>">Volver a la cotización</a>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
