<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

tx_start_session();
$reference = tx_normalize_reference($_POST['reference'] ?? $_GET['ref'] ?? '');
$quote = $reference !== '' ? tx_find_quote($reference) : null;
$accessTime = (int)($_SESSION['quote_access'][$reference] ?? 0);
$intentTime = (int)($_SESSION['demo_payment_intents'][$reference] ?? 0);
$hasAccess = $accessTime > time() - 1800;

if ($quote === null || !$hasAccess || !tx_quote_is_demo($quote)) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

if ((string)($quote['status'] ?? '') === 'paid') {
    header('Location: /comprobante?ref=' . rawurlencode($reference), true, 303);
    exit;
}

if ($intentTime < time() - 600 || !tx_quote_is_payable($quote)) {
    header('Location: /pagar?estado=invalido', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (
        !tx_verify_csrf($_POST['csrf_token'] ?? null)
        || (string)($_POST['action'] ?? '') !== 'confirm_demo'
    ) {
        header('Location: /pagar?estado=invalido', true, 303);
        exit;
    }

    try {
        $updatedQuote = tx_mark_demo_quote_paid($reference);
        if ($updatedQuote === null) {
            throw new RuntimeException('La cotización de prueba no pudo actualizarse.');
        }
        unset($_SESSION['demo_payment_intents'][$reference]);
        header('Location: /comprobante?ref=' . rawurlencode($reference), true, 303);
        exit;
    } catch (Throwable $error) {
        error_log('TECNOXPERT demo payment error: ' . $error->getMessage());
        header('Location: /pagar?estado=invalido', true, 303);
        exit;
    }
}

$currency = (string)($quote['currency'] ?? 'COP');
$pageTitle = 'Pago de prueba | TECNOXPERT';
$pageDescription = 'Simulación privada del proceso de pago de una cotización TECNOXPERT.';
$canonicalPath = '/pago-demo';
$activeNav = 'pagos';
$noIndex = true;
$csrfToken = tx_csrf_token();
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><a href="/pagar">Pagar cotización</a><span>/</span><span>Prueba</span></nav>
    <h1>Simulación de pasarela de pago</h1>
    <p>Esta pantalla permite revisar el proceso completo sin enviar dinero ni conectarse con un proveedor financiero.</p>
  </div>
</header>

<section class="section">
  <div class="shell narrow">
    <div class="form-panel">
      <p class="status-message"><strong>Entorno demostrativo:</strong> confirmar esta operación solo cambiará el estado de la cotización de prueba y generará un comprobante simulado.</p>
      <h2>Confirmar pago de prueba</h2>
      <dl class="summary-list">
        <div><dt>Referencia</dt><dd><?= tx_e($reference) ?></dd></div>
        <div><dt>Concepto</dt><dd><?= tx_e((string)($quote['title'] ?? 'Demostración')) ?></dd></div>
        <div><dt>Cliente</dt><dd><?= tx_e((string)($quote['customer_name'] ?? 'Cliente de prueba')) ?></dd></div>
        <div class="summary-total"><dt>Total simulado</dt><dd><?= tx_e(tx_money((float)($quote['total'] ?? 0), $currency)) ?></dd></div>
      </dl>
      <form action="/pago-demo" method="post">
        <input type="hidden" name="action" value="confirm_demo">
        <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
        <input type="hidden" name="reference" value="<?= tx_e($reference) ?>">
        <button class="button" type="submit">Simular pago aprobado</button>
        <a class="button button-secondary" href="/pagar?ref=<?= rawurlencode($reference) ?>">Volver al resumen</a>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
