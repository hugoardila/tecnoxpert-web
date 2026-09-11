<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

tx_start_session();
$reference = tx_normalize_reference($_GET['ref'] ?? '');
$quote = $reference !== '' ? tx_find_quote($reference) : null;
$hasAccess = isset($_SESSION['quote_access'][$reference]) && (int)$_SESSION['quote_access'][$reference] > time() - 1800;
$visible = $quote !== null && $hasAccess && (string)($quote['status'] ?? '') === 'paid';
$isDemo = $visible && tx_quote_is_demo($quote);

$pageTitle = 'Comprobante de orden | TECNOXPERT';
$pageDescription = 'Consulta privada de la confirmación asociada a una orden de TECNOXPERT.';
$canonicalPath = '/comprobante';
$activeNav = '';
$noIndex = true;
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="shell narrow">
    <?php if ($visible): ?>
      <p class="status-message is-success"><?= $isDemo ? 'Simulación completada. No se transfirió dinero ni se contactó una pasarela.' : 'Pago confirmado para la orden ' . tx_e($reference) . '.' ?></p>
      <h1><?= $isDemo ? 'Comprobante de pago de prueba' : 'Comprobante de orden' ?></h1>
      <dl class="summary-list">
        <div><dt>Referencia</dt><dd><?= tx_e($reference) ?></dd></div>
        <div><dt>Concepto</dt><dd><?= tx_e((string)($quote['title'] ?? 'Servicio cotizado')) ?></dd></div>
        <div><dt>Total</dt><dd><?= tx_e(tx_money((float)($quote['total'] ?? 0), (string)($quote['currency'] ?? 'COP'))) ?></dd></div>
        <div><dt>Estado</dt><dd><?= $isDemo ? 'Aprobado en modo de prueba' : 'Pago confirmado' ?></dd></div>
        <div><dt>Identificador del proveedor</dt><dd><?= tx_e((string)($quote['provider_payment_id'] ?? 'Registrado en la orden')) ?></dd></div>
      </dl>
    <?php else: ?>
      <p class="status-message is-error">No fue posible mostrar un comprobante con la sesión y referencia actuales.</p>
      <a class="button" href="/pagar">Consultar cotización</a>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
