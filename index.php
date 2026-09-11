<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/catalog.php';

$pageTitle = 'TECNOXPERT | Desarrollo web, automatización y soporte tecnológico';
$pageDescription = 'Soluciones tecnológicas para empresas y emprendedores: desarrollo web, automatización de WhatsApp, CRM, software empresarial y soporte técnico.';
$canonicalPath = '/';
$activeNav = 'inicio';
$preloadHero = true;
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'ProfessionalService',
    'name' => TX_NAME,
    'legalName' => TX_OWNER,
    'url' => TX_BASE_URL,
    'logo' => tx_url('/assets/img/logo.png'),
    'image' => tx_url('/assets/img/logo-fondo.webp'),
    'telephone' => '+' . TX_PHONE_E164,
    'email' => TX_EMAIL_SALES,
    'taxID' => TX_NIT,
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'Pitalito',
        'addressRegion' => 'Huila',
        'addressCountry' => 'CO',
    ],
    'areaServed' => [
        '@type' => 'Country',
        'name' => 'Colombia',
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="hero" aria-labelledby="hero-title">
  <div class="shell hero-content">
    <p class="eyebrow">Tecnología aplicada a negocios reales</p>
    <h1 id="hero-title">TECNOXPERT</h1>
    <p class="hero-lead">Soluciones tecnológicas para empresas y emprendedores: desarrollo web, automatización de WhatsApp, CRM, software empresarial y soporte técnico.</p>
    <p class="hero-copy">Diseñamos e implementamos soluciones de acuerdo con las necesidades, el alcance y el presupuesto de cada cliente.</p>
    <div class="button-row">
      <a class="button" href="/contacto?motivo=cotizacion">Solicitar cotización</a>
      <a class="button button-secondary" href="/servicios">Ver servicios</a>
      <a class="button button-whatsapp" href="https://wa.me/<?= TX_PHONE_E164 ?>?text=Hola%20TECNOXPERT%2C%20quiero%20informaci%C3%B3n%20sobre%20sus%20servicios." target="_blank" rel="noopener">Contactar por WhatsApp</a>
    </div>
  </div>
</section>

<section class="trust-band" aria-label="Condiciones principales del servicio">
  <div class="shell trust-grid">
    <div class="trust-item"><strong>Cotización identificada</strong><span>Alcance, precio y plazo antes del pago.</span></div>
    <div class="trust-item"><strong>Pago verificable</strong><span>Cada cobro se relaciona con una orden real.</span></div>
    <div class="trust-item"><strong>Soporte definido</strong><span>Condiciones incluidas en cada propuesta.</span></div>
    <div class="trust-item"><strong>Atención desde Colombia</strong><span>Pitalito, Huila.</span></div>
  </div>
</section>

<section class="section" id="servicios">
  <div class="shell">
    <div class="section-header">
      <div>
        <p class="eyebrow">Servicios principales</p>
        <h2>Servicios disponibles para contratación y pago</h2>
      </div>
      <p>Los servicios personalizados requieren cotización previa. El precio no se define mediante formularios libres ni se modifica desde el navegador.</p>
    </div>
    <div class="service-grid">
      <?php foreach (array_slice($txServices, 0, 6) as $service): ?>
        <article class="service-card">
          <img src="<?= tx_e($service['image']) ?>" alt="<?= tx_e($service['image_alt']) ?>" width="1040" height="640" loading="lazy" decoding="async">
          <div class="service-card-body">
            <p class="eyebrow"><?= tx_e($service['category']) ?></p>
            <h3><?= tx_e($service['name']) ?></h3>
            <p><?= tx_e($service['summary']) ?></p>
            <a class="text-link" href="/servicios#<?= tx_e($service['slug']) ?>">Consultar ficha del servicio</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="button-row">
      <a class="button button-secondary" href="/servicios">Ver todos los servicios y condiciones</a>
    </div>
  </div>
</section>

<section class="section section-soft" aria-labelledby="process-title">
  <div class="shell">
    <div class="section-header">
      <div>
        <p class="eyebrow">Proceso transparente</p>
        <h2 id="process-title">¿Cómo contratar nuestros servicios?</h2>
      </div>
      <p>Los desarrollos personalizados pueden requerir un anticipo. El porcentaje y los hitos se indican en la propuesta.</p>
    </div>
    <ol class="process-list">
      <?php
      $steps = [
          ['Solicitud', 'El cliente describe su necesidad y datos de contacto.'],
          ['Análisis', 'TECNOXPERT revisa requerimientos, accesos y dependencias.'],
          ['Propuesta', 'Se presenta alcance, exclusiones, plazo y precio.'],
          ['Aceptación', 'El cliente abre el enlace privado y acepta alcance, valor, plazo y políticas aplicables.'],
          ['Orden', 'Se genera una cotización u orden identificada.'],
          ['Pago', 'Se realiza el anticipo o pago desde el enlace de la orden.'],
          ['Ejecución', 'Se desarrolla o presta el servicio aprobado.'],
          ['Entrega', 'Se presenta el resultado y su evidencia de entrega.'],
          ['Soporte', 'Comienza el periodo de soporte o garantía acordado.'],
      ];
      foreach ($steps as $index => [$title, $copy]):
      ?>
        <li>
          <span class="process-number"><?= $index + 1 ?></span>
          <h3><?= tx_e($title) ?></h3>
          <p><?= tx_e($copy) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section" aria-labelledby="portfolio-title">
  <div class="shell">
    <div class="section-header">
      <div>
        <p class="eyebrow">Experiencia aplicada</p>
        <h2 id="portfolio-title">Soluciones desarrolladas</h2>
      </div>
      <p>Presentamos tipos de solución implementada sin publicar interfaces, datos ni operaciones privadas de clientes.</p>
    </div>
    <div class="portfolio-grid">
      <article class="portfolio-item">
        <figure class="portfolio-media">
          <img src="/assets/img/services/inventory.webp" alt="Centro logístico con inventario organizado en estanterías." width="1040" height="640" loading="lazy" decoding="async">
          <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
        </figure>
        <div class="portfolio-item-body">
          <span class="portfolio-status">Tipo de solución implementada</span>
          <h3>Inventario, ventas y reportes</h3>
          <p>Sistema para registrar productos, movimientos, pedidos y resultados operativos.</p>
          <a class="text-link" href="/portafolio">Conocer el alcance presentado</a>
        </div>
      </article>
      <article class="portfolio-item">
        <figure class="portfolio-media">
          <img src="/assets/img/services/whatsapp-automation.webp" alt="Gestión de conversaciones desde un celular y un computador." width="1040" height="640" loading="lazy" decoding="async">
          <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
        </figure>
        <div class="portfolio-item-body">
          <span class="portfolio-status">Tipo de solución implementada</span>
          <h3>Atención por WhatsApp y CRM</h3>
          <p>Asignación de conversaciones, roles, archivos, notificaciones y seguimiento interno.</p>
          <a class="text-link" href="/portafolio">Conocer el alcance presentado</a>
        </div>
      </article>
      <article class="portfolio-item">
        <figure class="portfolio-media">
          <img src="/assets/img/services/ecommerce.webp" alt="Panel de comercio electrónico abierto en un portátil." width="1040" height="640" loading="lazy" decoding="async">
          <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
        </figure>
        <div class="portfolio-item-body">
          <span class="portfolio-status">Tipo de solución implementada</span>
          <h3>Comercio y pedidos en línea</h3>
          <p>Catálogos, carritos, costos de envío, pedidos y conexión con pasarelas.</p>
          <a class="text-link" href="/portafolio">Conocer el alcance presentado</a>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section section-ink" aria-labelledby="assurance-title">
  <div class="shell">
    <div class="section-header">
      <div>
        <p class="eyebrow">Garantía y soporte</p>
        <h2 id="assurance-title">Condiciones claras desde la propuesta</h2>
      </div>
      <p>La cobertura depende del servicio contratado y se detalla antes de iniciar.</p>
    </div>
    <div class="assurance-grid">
      <div><h3>Alcance documentado</h3><p>Las funciones, entregables y exclusiones quedan identificadas en la cotización.</p></div>
      <div><h3>Corrección de errores</h3><p>Se atienden errores atribuibles al alcance original durante el periodo acordado.</p></div>
      <div><h3>Cambios adicionales</h3><p>Las nuevas funciones o modificaciones se analizan y cotizan por separado.</p></div>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="legal-title">
  <div class="shell legal-strip">
    <div>
      <h2 id="legal-title">Información legal verificable</h2>
      <p>TECNOXPERT es un establecimiento de comercio propiedad de Hugo Alberto Ardila Molina, identificado con NIT 1083903212-3, domiciliado en Pitalito, Huila, Colombia.</p>
    </div>
    <div class="button-row">
      <a class="button button-secondary" href="/informacion-legal">Ver información del negocio</a>
    </div>
  </div>
</section>

<section class="section section-soft" aria-labelledby="faq-title">
  <div class="shell">
    <div class="section-header">
      <div>
        <p class="eyebrow">Preguntas frecuentes</p>
        <h2 id="faq-title">Antes de contratar</h2>
      </div>
      <p>Respuestas coherentes con nuestras condiciones de servicio, entrega, garantía y reembolso.</p>
    </div>
    <div class="faq-list">
      <?php foreach ($txFaqs as $index => [$question, $answer]): $panelId = 'faq-' . ($index + 1); ?>
        <div class="faq-item">
          <button class="faq-question" type="button" data-accordion-button aria-expanded="false" aria-controls="<?= $panelId ?>"><?= tx_e($question) ?></button>
          <div class="faq-answer" id="<?= $panelId ?>" hidden><p><?= tx_e($answer) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="shell cta-inner">
    <div>
      <h2>Cuéntanos qué necesitas</h2>
      <p>Revisaremos el alcance antes de emitir cualquier cobro.</p>
    </div>
    <a class="button" href="/contacto?motivo=cotizacion">Solicitar cotización</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
