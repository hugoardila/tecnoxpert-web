<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/payments.php';

tx_start_session();
$csrfToken = tx_csrf_token();
$quote = null;
$lookupError = '';
$referenceInput = tx_normalize_reference($_GET['ref'] ?? '');
$tokenInput = trim((string)($_GET['token'] ?? ''));

if ($referenceInput !== '' && $tokenInput !== '') {
    $candidate = tx_find_quote($referenceInput);
    if ($candidate !== null && tx_quote_matches_token($candidate, $tokenInput)) {
        $quote = $candidate;
        $_SESSION['quote_access'][$referenceInput] = time();
    } else {
        $lookupError = 'No encontramos una cotización activa con esos datos.';
    }
} elseif (
    $referenceInput !== ''
    && (int)($_SESSION['quote_access'][$referenceInput] ?? 0) > time() - 1800
) {
    $quote = tx_find_quote($referenceInput);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['action'] ?? '') === 'lookup') {
    if (!tx_verify_csrf($_POST['csrf_token'] ?? null)) {
        $lookupError = 'La sesión venció. Recarga la página e intenta nuevamente.';
    } else {
        $referenceInput = tx_normalize_reference($_POST['reference'] ?? '');
        $lookupEmail = tx_normalize_email($_POST['email'] ?? '');
        $candidate = $referenceInput !== '' ? tx_find_quote($referenceInput) : null;
        if (
            $candidate !== null
            && filter_var($lookupEmail, FILTER_VALIDATE_EMAIL)
            && tx_quote_matches_email($candidate, $lookupEmail)
        ) {
            $quote = $candidate;
            $_SESSION['quote_access'][$referenceInput] = time();
        } else {
            $lookupError = 'No encontramos una cotización activa con esos datos.';
        }
    }
}

if ($quote !== null && tx_quote_is_expired($quote)) {
    $lookupError = 'La cotización está vencida. Solicita una actualización antes de pagar.';
    $quote = null;
}

$statusKey = (string)($_GET['estado'] ?? '');
$statusMessage = match ($statusKey) {
    'aceptada' => 'La aceptación quedó registrada correctamente.',
    'pagado' => 'El proveedor confirmó el pago. La cotización ya figura como pagada.',
    'pago-pendiente' => 'El pago está pendiente de confirmación. Actualiza la cotización más tarde antes de intentarlo nuevamente.',
    'pago-rechazado' => 'El proveedor no aprobó el pago. Puedes elegir otro método o intentarlo nuevamente.',
    'pago-cancelado' => 'El proceso de pago fue cancelado y no se registró ningún cobro.',
    'error-pago' => 'No fue posible confirmar el proceso con el proveedor. No se marcará como pagado hasta recibir una validación correcta.',
    'transferencia-reportada' => 'Recibimos el aviso de la transferencia. La cotización quedará pendiente hasta validar el movimiento bancario.',
    'aceptacion-invalida' => 'No fue posible registrar la aceptación. Revisa las casillas e intenta nuevamente.',
    'no-habilitado' => 'La cotización todavía no está habilitada para pago. Contacta a TECNOXPERT para continuar.',
    'invalido' => 'No fue posible validar la solicitud de pago. Consulta nuevamente la cotización.',
    default => '',
};
$sessionPaymentError = trim((string)($_SESSION['payment_error'] ?? ''));
unset($_SESSION['payment_error']);
if ($sessionPaymentError !== '') {
    $statusMessage = $sessionPaymentError;
}
$statusClass = match ($statusKey) {
    'aceptada', 'pagado', 'transferencia-reportada' => 'is-success',
    'pago-pendiente', 'pago-cancelado' => '',
    default => $statusMessage !== '' ? 'is-error' : '',
};

$pageTitle = 'Pagar una cotización | TECNOXPERT';
$pageDescription = 'Consulta y paga únicamente cotizaciones reales emitidas por TECNOXPERT.';
$canonicalPath = '/pagar';
$activeNav = 'pagos';
$noIndex = $quote !== null || $referenceInput !== '';
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><span>Pagar cotización</span></nav>
    <h1>Consulta y pago de cotizaciones</h1>
    <p>El importe se obtiene desde una cotización registrada en el servidor. No puedes escribir ni modificar libremente el valor del cobro.</p>
  </div>
</header>

<section class="section">
  <div class="shell payment-layout">
    <section class="payment-explainer" aria-labelledby="payment-process-title">
      <p class="eyebrow">Pago relacionado con una orden</p>
      <h2 id="payment-process-title">Proceso de pago seguro</h2>
      <ol class="numbered-mini-list">
        <li>Ingresa la referencia y el correo asociado.</li>
        <li>Revisa servicio, valor, moneda y entrega.</li>
        <li>Acepta términos y política de reembolsos.</li>
        <li>Elige el método de pago disponible que prefieras.</li>
        <li>La orden cambia de estado después de la confirmación del proveedor.</li>
      </ol>
      <p>Si aún no tienes una cotización, <a href="/contacto?motivo=cotizacion">solicítala aquí</a>.</p>
    </section>

    <section class="form-panel" aria-labelledby="<?= $quote !== null ? 'quote-summary-title' : 'quote-lookup-title' ?>">
      <?php if ($statusMessage !== ''): ?><p class="status-message <?= tx_e($statusClass) ?>"><?= tx_e($statusMessage) ?></p><?php endif; ?>
      <?php if ($lookupError !== ''): ?><p class="status-message is-error"><?= tx_e($lookupError) ?></p><?php endif; ?>

      <?php if ($quote === null): ?>
        <h2 id="quote-lookup-title">Consultar cotización</h2>
        <p>Necesitas los datos enviados por TECNOXPERT. Por seguridad, la búsqueda no informa cuál de los campos es incorrecto.</p>
        <form action="/pagar" method="post">
          <input type="hidden" name="action" value="lookup">
          <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
          <div class="field">
            <label for="reference">Número de cotización u orden *</label>
            <input id="reference" name="reference" type="text" maxlength="40" autocomplete="off" value="<?= tx_e($referenceInput) ?>" placeholder="COT-2026-0001" required>
          </div>
          <div class="field">
            <label for="lookup_email">Correo asociado *</label>
            <input id="lookup_email" name="email" type="email" maxlength="160" autocomplete="email" required>
          </div>
          <button class="button" type="submit">Consultar resumen</button>
        </form>
      <?php else:
          $currency = (string)($quote['currency'] ?? 'COP');
          $subtotal = (float)($quote['subtotal'] ?? 0);
          $tax = (float)($quote['tax'] ?? 0);
          $total = (float)($quote['total'] ?? 0);
          $isDemo = tx_quote_is_demo($quote);
          $paymentMethods = tx_payment_methods($quote);
          $paymentCountries = tx_payment_countries();
      ?>
        <div class="quote-summary">
          <div class="quote-summary-header">
            <h2 id="quote-summary-title">Resumen de <?= tx_e((string)$quote['reference']) ?></h2>
            <p>Datos obtenidos desde el servidor.</p>
          </div>
          <div class="quote-summary-body">
            <dl class="summary-list">
              <div><dt>Cliente</dt><dd><?= tx_e((string)($quote['customer_name'] ?? 'Cliente registrado')) ?></dd></div>
              <div><dt>Correo</dt><dd><?= tx_e(tx_mask_email((string)($quote['customer_email'] ?? ''))) ?></dd></div>
              <div><dt>Servicio o producto</dt><dd><?= tx_e((string)($quote['title'] ?? 'Servicio cotizado')) ?></dd></div>
              <div><dt>Descripción</dt><dd><?= tx_e((string)($quote['description'] ?? 'Según cotización aceptada')) ?></dd></div>
              <div><dt>Estado</dt><dd><?= tx_e((string)($quote['status_label'] ?? $quote['status'] ?? 'Pendiente')) ?></dd></div>
              <?php if (!empty($quote['accepted_at'])): ?><div><dt>Aceptada el</dt><dd><?= tx_e(date('d/m/Y, g:i a', strtotime((string)$quote['accepted_at']))) ?></dd></div><?php endif; ?>
              <div><dt>Entrega estimada</dt><dd><?= tx_e((string)($quote['delivery_estimate'] ?? 'Según propuesta')) ?></dd></div>
              <div><dt>Subtotal</dt><dd><?= tx_e(tx_money($subtotal, $currency)) ?></dd></div>
              <div><dt>Impuestos</dt><dd><?= $tax > 0 ? tx_e(tx_money($tax, $currency)) : 'No discriminados / según cotización' ?></dd></div>
              <div class="summary-total"><dt>Total</dt><dd><?= tx_e(tx_money($total, $currency)) ?></dd></div>
            </dl>

            <?php if (!empty($quote['items']) && is_array($quote['items'])): ?>
              <h3>Conceptos incluidos</h3>
              <ul>
                <?php foreach ($quote['items'] as $item): ?>
                  <li><?= tx_e((string)($item['description'] ?? 'Concepto')) ?> — <?= tx_e(tx_money((float)($item['amount'] ?? 0), $currency)) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ((string)($quote['status'] ?? '') === 'pending_acceptance'): ?>
              <section class="quote-acceptance" aria-labelledby="quote-acceptance-title">
                <h3 id="quote-acceptance-title">Aceptar esta cotización</h3>
                <p>Confirma únicamente si el servicio, alcance, valor y plazo coinciden con lo acordado.</p>
                <form action="/aceptar-cotizacion" method="post">
                  <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                  <input type="hidden" name="reference" value="<?= tx_e((string)$quote['reference']) ?>">
                  <div class="check-field">
                    <input id="quote_accept" name="quote_accept" type="checkbox" value="1" required>
                    <label for="quote_accept">Acepto el servicio, alcance, valor total y plazo indicados en esta cotización. *</label>
                  </div>
                  <div class="check-field">
                    <input id="terms_accept" name="terms_accept" type="checkbox" value="1" required>
                    <label for="terms_accept">Acepto los <a href="/terminos-condiciones" target="_blank">Términos y condiciones</a>. *</label>
                  </div>
                  <div class="check-field">
                    <input id="refund_accept" name="refund_accept" type="checkbox" value="1" required>
                    <label for="refund_accept">Declaro haber leído la <a href="/politica-reembolsos" target="_blank">Política de reembolsos y cancelaciones</a>. *</label>
                  </div>
                  <button class="button" type="submit">Aceptar cotización</button>
                </form>
              </section>
            <?php elseif ((string)($quote['status'] ?? '') === 'paid'): ?>
              <p class="status-message is-success">La cotización figura como pagada. Contacta a soporte si necesitas el comprobante o el estado de entrega.</p>
            <?php elseif ($isDemo && tx_quote_is_payable($quote)): ?>
              <p class="status-message"><strong>Modo de prueba:</strong> este proceso simula la aprobación y genera un comprobante, pero no transfiere dinero ni contacta una pasarela.</p>
              <form action="/iniciar-pago.php" method="post" data-payment-acceptance>
                <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                <input type="hidden" name="reference" value="<?= tx_e((string)$quote['reference']) ?>">
                <div class="check-field">
                  <input id="terms_accept" name="terms_accept" type="checkbox" value="1" required>
                  <label for="terms_accept">Acepto los <a href="/terminos-condiciones" target="_blank">Términos y condiciones</a> y confirmo que revisé la cotización. *</label>
                </div>
                <div class="check-field">
                  <input id="refund_accept" name="refund_accept" type="checkbox" value="1" required>
                  <label for="refund_accept">Declaro haber leído la <a href="/politica-reembolsos" target="_blank">Política de reembolsos y cancelaciones</a>. *</label>
                </div>
                <button class="button" type="submit" disabled>Continuar al pago de prueba</button>
              </form>
            <?php elseif (tx_quote_is_payable($quote)): ?>
              <section class="payment-choice" aria-labelledby="payment-choice-title">
                <div class="payment-choice-heading">
                  <div>
                    <p class="eyebrow">Cotización aceptada</p>
                    <h3 id="payment-choice-title">Elige cómo pagar</h3>
                  </div>
                  <p class="payment-choice-total"><?= tx_e(tx_money($total, $currency)) ?></p>
                </div>
                <p>El valor original de la cotización se conserva. La web calcula el equivalente según tu país y prepara cada pasarela en una moneda compatible.</p>
                <form
                  action="/seleccionar-pago"
                  method="post"
                  data-payment-selector
                  data-payment-reference="<?= tx_e((string)$quote['reference']) ?>"
                >
                  <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                  <input type="hidden" name="reference" value="<?= tx_e((string)$quote['reference']) ?>">
                  <div class="field payment-country-field">
                    <label for="payer_country">País desde el que realizarás el pago *</label>
                    <select id="payer_country" name="payer_country" required data-payment-country>
                      <option value="">Selecciona tu país</option>
                      <?php foreach ($paymentCountries as $countryCode => $countryName): ?>
                        <option value="<?= tx_e($countryCode) ?>"><?= tx_e($countryName) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <p class="field-help" data-payment-country-help>Selecciona el país para calcular el equivalente en tu moneda.</p>
                  </div>
                  <div class="payment-conversion" data-payment-conversion hidden aria-live="polite">
                    <span>Equivalente estimado en tu moneda</span>
                    <strong data-payment-local-total>Calculando...</strong>
                    <small data-payment-rate-note>Consultando la tasa de referencia vigente.</small>
                  </div>
                  <fieldset class="payment-method-grid">
                    <legend class="sr-only">Métodos de pago disponibles</legend>
                    <?php $firstEnabled = true; ?>
                    <?php foreach ($paymentMethods as $providerId => $method): ?>
                      <?php
                        $methodEnabled = ($method['enabled'] ?? false) === true;
                        $isChecked = $methodEnabled && $firstEnabled;
                        if ($isChecked) {
                            $firstEnabled = false;
                        }
                      ?>
                      <label
                        class="payment-method<?= $methodEnabled ? '' : ' is-disabled' ?>"
                        data-payment-method
                        data-payment-provider="<?= tx_e((string)$providerId) ?>"
                        data-payment-base-enabled="<?= $methodEnabled ? '1' : '0' ?>"
                        data-payment-countries="<?= tx_e(implode(',', (array)($method['countries'] ?? []))) ?>"
                      >
                        <input
                          type="radio"
                          name="provider"
                          value="<?= tx_e((string)$providerId) ?>"
                          <?= $methodEnabled ? '' : 'disabled' ?>
                          <?= $isChecked ? 'checked' : '' ?>
                          required
                        >
                        <span class="payment-method-mark" aria-hidden="true"><?= tx_e((string)$method['short']) ?></span>
                        <span class="payment-method-copy">
                          <strong><?= tx_e((string)$method['name']) ?></strong>
                          <small><?= tx_e((string)$method['description']) ?></small>
                          <?php if ($methodEnabled): ?><small class="payment-method-charge" data-payment-provider-total="<?= tx_e((string)$providerId) ?>"></small><?php endif; ?>
                          <?php if (!$methodEnabled): ?><em><?= tx_e((string)$method['reason']) ?></em><?php endif; ?>
                          <?php if ($methodEnabled && !empty($method['country_reason'])): ?><em data-country-reason hidden><?= tx_e((string)$method['country_reason']) ?></em><?php endif; ?>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </fieldset>
                  <button class="button" type="submit"<?= $firstEnabled ? ' disabled' : '' ?>>Continuar al método seleccionado</button>
                </form>
              </section>
            <?php else: ?>
              <p class="status-message">El pago en línea todavía no está habilitado para esta cotización. No se realizará ningún cobro desde esta página.</p>
              <a class="button button-secondary" href="/contacto?motivo=cotizacion">Contactar a TECNOXPERT</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
