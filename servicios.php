<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/catalog.php';

$localServiceSlugs = ['reparacion-computadores', 'reparacion-celulares'];
$remoteServices = array_values(array_filter(
    $txServices,
    static fn(array $service): bool => !in_array($service['slug'], $localServiceSlugs, true)
));
$localServices = array_values(array_filter(
    $txServices,
    static fn(array $service): bool => in_array($service['slug'], $localServiceSlugs, true)
));
$serviceGroups = [
    [
        'id' => 'servicios-digitales',
        'eyebrow' => 'Servicios digitales y remotos',
        'title' => 'Soluciones para clientes nacionales e internacionales',
        'description' => 'Desarrollo, automatización e infraestructura que se contratan, ejecutan y entregan por medios digitales. La moneda, el alcance y el medio de pago se definen en cada cotización.',
        'services' => $remoteServices,
        'class' => 'section',
    ],
    [
        'id' => 'servicios-locales',
        'eyebrow' => 'Servicios técnicos locales',
        'title' => 'Atención presencial en Pitalito, Huila',
        'description' => 'Diagnóstico, reparación y mantenimiento para equipos recibidos presencialmente en Pitalito. El precio se informa después del diagnóstico y antes de intervenir.',
        'services' => $localServices,
        'class' => 'section section-soft',
    ],
];

$pageTitle = 'Servicios tecnológicos | TECNOXPERT';
$pageDescription = 'Fichas de servicios de desarrollo web, software, automatización, servidores y soporte técnico con cotización previa.';
$canonicalPath = '/servicios';
$activeNav = 'servicios';
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><span>Servicios</span></nav>
    <h1>Servicios disponibles para contratación y pago</h1>
    <p>Cada servicio se formaliza mediante una cotización con alcance, exclusiones, precio, plazo y condiciones. No se cobran valores libres ni trabajos personalizados sin definición previa.</p>
  </div>
</header>

<?php foreach ($serviceGroups as $groupIndex => $group): ?>
  <section class="<?= tx_e($group['class']) ?>" id="<?= tx_e($group['id']) ?>">
    <div class="shell">
      <?php if ($groupIndex === 0): ?>
        <div class="legal-note payment-policy-note">
          <strong>Todos los pagos corresponden a una orden identificada.</strong>
          <p>TECNOXPERT no recibe pagos sin una cotización previamente emitida. Cada cobro queda vinculado con un servicio, un cliente, una referencia, un valor y una entrega acordada.</p>
        </div>
      <?php endif; ?>
      <div class="service-group-header">
        <p class="eyebrow"><?= tx_e($group['eyebrow']) ?></p>
        <h2><?= tx_e($group['title']) ?></h2>
        <p><?= tx_e($group['description']) ?></p>
      </div>
      <div class="detail-grid">
        <?php foreach ($group['services'] as $service): ?>
          <article class="service-detail" id="<?= tx_e($service['slug']) ?>">
            <div class="service-detail-header">
              <img src="<?= tx_e($service['image']) ?>" alt="<?= tx_e($service['image_alt']) ?>" width="1040" height="640" loading="lazy" decoding="async">
              <div>
                <p class="eyebrow"><?= tx_e($service['category']) ?></p>
                <h3><?= tx_e($service['name']) ?></h3>
                <p><?= tx_e($service['summary']) ?></p>
              </div>
            </div>
            <dl class="service-facts">
              <div><dt>Alcance</dt><dd><?= tx_e($service['scope']) ?></dd></div>
              <div><dt>Incluye</dt><dd><?= tx_e($service['includes']) ?></dd></div>
              <div><dt>No incluye</dt><dd><?= tx_e($service['excludes']) ?></dd></div>
              <div><dt>Precio</dt><dd><?= tx_e($service['price']) ?></dd></div>
              <div><dt>Entrega estimada</dt><dd><?= tx_e($service['delivery']) ?></dd></div>
              <div><dt>Requisitos</dt><dd><?= tx_e($service['requirements']) ?></dd></div>
              <div><dt>Soporte y garantía</dt><dd><?= tx_e($service['support']) ?></dd></div>
              <div><dt>Cancelación</dt><dd><?= tx_e($service['cancellation']) ?></dd></div>
            </dl>
            <div class="service-detail-actions">
              <a class="button button-small" href="/contacto?servicio=<?= rawurlencode($service['slug']) ?>">Solicitar cotización</a>
              <a class="button button-small button-whatsapp" href="https://wa.me/<?= TX_PHONE_E164 ?>?text=Hola%20TECNOXPERT%2C%20quiero%20cotizar%20<?= rawurlencode($service['name']) ?>." target="_blank" rel="noopener">Consultar por WhatsApp</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endforeach; ?>

<section class="cta-band">
  <div class="shell cta-inner">
    <div><h2>¿Tu proyecto no encaja exactamente aquí?</h2><p>Podemos revisar un desarrollo personalizado sin asumir funciones ni precios antes del análisis.</p></div>
    <a class="button" href="/contacto?motivo=cotizacion">Describir mi proyecto</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
