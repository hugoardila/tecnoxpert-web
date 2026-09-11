<?php
declare(strict_types=1);

$pageTitle = 'Portafolio de soluciones | TECNOXPERT';
$pageDescription = 'Tipos de soluciones tecnológicas implementadas por TECNOXPERT sin publicar información privada de clientes.';
$canonicalPath = '/portafolio';
$activeNav = 'portafolio';
require __DIR__ . '/includes/header.php';
?>
<header class="page-hero">
  <div class="shell">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><span>Portafolio</span></nav>
    <h1>Soluciones implementadas</h1>
    <p>Esta sección describe tipos de solución implementada. Para proteger la información de clientes, usamos imágenes ilustrativas y presentamos demostraciones reales únicamente bajo solicitud.</p>
  </div>
</header>

<section class="section">
  <div class="shell portfolio-grid">
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/inventory.webp" alt="Centro logístico con inventario organizado en estanterías." width="1040" height="640" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de solución implementada</span>
        <h2>Inventario y gestión de ventas</h2>
        <p>Registro de productos, costos, precios, cantidades, compras, gastos, ventas, reportes y comprobantes. Los accesos operativos permanecen restringidos.</p>
      </div>
    </article>
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/whatsapp-automation.webp" alt="Gestión de conversaciones desde un celular y un computador." width="1040" height="640" loading="lazy" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de solución implementada</span>
        <h2>CRM para atención por WhatsApp</h2>
        <p>Administración de conversaciones, asignación de clientes, roles de asesores, archivos multimedia, plantillas y alertas.</p>
      </div>
    </article>
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/ecommerce.webp" alt="Panel de comercio electrónico abierto en un portátil." width="1040" height="640" loading="lazy" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de solución implementada</span>
        <h2>Tienda virtual y pedidos</h2>
        <p>Catálogo, múltiples imágenes por producto, carrito, métodos de entrega, consulta de pedidos y administración interna.</p>
      </div>
    </article>
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/servers.webp" alt="Gabinetes de servidores y cableado en un centro de datos." width="1040" height="640" loading="lazy" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de solución implementada</span>
        <h2>Migración y aislamiento de servicios</h2>
        <p>Despliegue de aplicaciones y servicios en contenedores, configuración de dominios, certificados, copias de seguridad y monitoreo básico.</p>
      </div>
    </article>
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/ai-integration.webp" alt="Robot de asistencia con pantalla táctil en funcionamiento." width="1040" height="640" loading="lazy" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de solución implementada</span>
        <h2>Automatización de procesos</h2>
        <p>Flujos para pedidos, notificaciones, asignaciones, seguimiento y tareas repetitivas, sujetos a la disponibilidad de proveedores externos.</p>
      </div>
    </article>
    <article class="portfolio-item">
      <figure class="portfolio-media">
        <img src="/assets/img/services/computer-repair.webp" alt="Componentes internos iluminados de un computador." width="1040" height="640" loading="lazy" decoding="async">
        <figcaption>Imagen ilustrativa. Por privacidad no se publican datos ni interfaces de clientes.</figcaption>
      </figure>
      <div class="portfolio-item-body">
        <span class="portfolio-status">Tipo de servicio prestado</span>
        <h2>Soporte y mantenimiento técnico</h2>
        <p>Diagnóstico, configuración y reparación de equipos con autorización del cliente y condiciones informadas antes de intervenir.</p>
      </div>
    </article>
  </div>
</section>

<section class="section section-soft">
  <div class="shell legal-strip">
    <div><h2>Demostraciones y accesos</h2><p>Las demostraciones privadas se presentan bajo solicitud y no procesan pagos durante una evaluación comercial. Los sistemas en operación no se publican como demos para proteger la información de sus usuarios.</p></div>
    <div class="button-row"><a class="button" href="/contacto?motivo=demostracion">Solicitar demostración</a></div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
