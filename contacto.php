<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/catalog.php';

tx_start_session();
$csrfToken = tx_csrf_token();
$formTimestamp = time();
$formProof = tx_form_proof($formTimestamp);

$requestedService = (string)($_GET['servicio'] ?? '');
$validService = tx_service_by_slug($txServices, $requestedService);
$reason = (string)($_GET['motivo'] ?? '');
if ($validService !== null) {
    $selectedReason = $validService['name'];
} elseif ($reason === 'demostracion') {
    $selectedReason = 'Solicitar demostración privada';
} elseif ($reason === 'cotizacion') {
    $selectedReason = 'Solicitar cotización';
} else {
    $selectedReason = '';
}

$pageTitle = 'Contacto y cotizaciones | TECNOXPERT';
$pageDescription = 'Canales oficiales y formulario para solicitar cotizaciones, soporte o información a TECNOXPERT.';
$canonicalPath = '/contacto';
$activeNav = 'contacto';
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><span>Contacto</span></nav>
    <h1>Contacto y solicitudes</h1>
    <p>Describe tu necesidad. Antes de cualquier cobro revisaremos el alcance, los requisitos y el plazo estimado.</p>
  </div>
</header>

<section class="section section-soft">
  <div class="shell contact-layout">
    <section class="contact-details" aria-labelledby="contact-details-title">
      <p class="eyebrow">Canales oficiales</p>
      <h2 id="contact-details-title">TECNOXPERT</h2>
      <p>Establecimiento de comercio propiedad de Hugo Alberto Ardila Molina, NIT 1083903212-3.</p>
      <ul class="contact-list">
        <li><strong>Ciudad y país</strong><span>Pitalito, Huila, Colombia</span></li>
        <li><strong>Ventas y cotizaciones</strong><a href="mailto:<?= TX_EMAIL_SALES ?>"><?= TX_EMAIL_SALES ?></a></li>
        <li><strong>Soporte y tratamiento de datos</strong><a href="mailto:<?= TX_EMAIL_SUPPORT ?>"><?= TX_EMAIL_SUPPORT ?></a></li>
        <li><strong>Teléfono y WhatsApp</strong><a href="https://wa.me/<?= TX_PHONE_E164 ?>" target="_blank" rel="noopener"><?= TX_PHONE_DISPLAY ?></a></li>
        <li><strong>Horario de atención</strong><span>Atención con cita previa. Los canales digitales reciben solicitudes en cualquier momento y se responden en orden de recepción.</span></li>
      </ul>
      <div class="button-row">
        <a class="button button-whatsapp" href="https://wa.me/<?= TX_PHONE_E164 ?>?text=Hola%20TECNOXPERT%2C%20quiero%20solicitar%20una%20cotizaci%C3%B3n." target="_blank" rel="noopener">Abrir WhatsApp</a>
      </div>
    </section>

    <section class="form-panel" aria-labelledby="contact-form-title">
      <h2 id="contact-form-title">Enviar una solicitud</h2>
      <p>Los campos marcados con * son obligatorios.</p>
      <form action="/api/contacto.php" method="post" data-contact-form>
        <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
        <input type="hidden" name="form_timestamp" value="<?= $formTimestamp ?>">
        <input type="hidden" name="form_proof" value="<?= tx_e($formProof) ?>">
        <div class="honeypot" aria-hidden="true">
          <label for="company_website">Sitio web de la empresa</label>
          <input id="company_website" name="company_website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="nombre">Nombre completo *</label>
            <input id="nombre" name="nombre" type="text" maxlength="120" autocomplete="name" required>
          </div>
          <div class="field">
            <label for="email">Correo electrónico *</label>
            <input id="email" name="email" type="email" maxlength="160" autocomplete="email" required>
          </div>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="telefono">Teléfono o WhatsApp *</label>
            <input id="telefono" name="telefono" type="tel" maxlength="30" autocomplete="tel" required>
          </div>
          <div class="field">
            <label for="ciudad">Ciudad *</label>
            <input id="ciudad" name="ciudad" type="text" maxlength="100" autocomplete="address-level2" required>
          </div>
        </div>
        <div class="field">
          <label for="servicio">Motivo o servicio *</label>
          <select id="servicio" name="servicio" required>
            <option value="">Selecciona una opción</option>
            <option value="Solicitar cotización"<?= $selectedReason === 'Solicitar cotización' ? ' selected' : '' ?>>Solicitar cotización</option>
            <?php foreach ($txServices as $service): ?>
              <option value="<?= tx_e($service['name']) ?>"<?= $selectedReason === $service['name'] ? ' selected' : '' ?>><?= tx_e($service['name']) ?></option>
            <?php endforeach; ?>
            <option value="Solicitar demostración privada"<?= $selectedReason === 'Solicitar demostración privada' ? ' selected' : '' ?>>Solicitar demostración privada</option>
            <option value="Soporte de un servicio">Soporte de un servicio</option>
            <option value="Garantía, entrega o reembolso">Garantía, entrega o reembolso</option>
            <option value="Privacidad y datos personales">Privacidad y datos personales</option>
          </select>
        </div>
        <div class="field">
          <label for="referencia">Cotización u orden relacionada</label>
          <input id="referencia" name="referencia" type="text" maxlength="40" autocomplete="off" placeholder="Ejemplo: COT-2026-0001">
          <p class="field-help">Déjalo vacío si todavía no tienes una cotización.</p>
        </div>
        <div class="field">
          <label for="mensaje">Descripción *</label>
          <textarea id="mensaje" name="mensaje" maxlength="3000" required placeholder="Cuéntanos qué necesitas, para quién y cuál es el resultado esperado."></textarea>
        </div>
        <div class="check-field">
          <input id="privacy_accept" name="privacy_accept" type="checkbox" value="1" required>
          <label for="privacy_accept">Autorizo el tratamiento de mis datos para atender esta solicitud y declaro haber leído la <a href="/tratamiento-datos" target="_blank">Política de tratamiento de datos personales</a>. *</label>
        </div>
        <button class="button" type="submit">Enviar solicitud</button>
        <p class="form-feedback" data-form-feedback role="status" aria-live="polite"></p>
      </form>
    </section>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

