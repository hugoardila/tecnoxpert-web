<?php
declare(strict_types=1);

http_response_code(404);
$pageTitle = 'Página no encontrada | TECNOXPERT';
$pageDescription = 'La página solicitada no existe o ya no está disponible.';
$canonicalPath = '/404';
$activeNav = '';
$noIndex = true;
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="shell narrow">
    <p class="eyebrow">Error 404</p>
    <h1>Página no encontrada</h1>
    <p>La dirección puede estar incompleta o el contenido pudo ser retirado. Puedes volver al inicio o consultar nuestros servicios.</p>
    <div class="button-row">
      <a class="button" href="/">Volver al inicio</a>
      <a class="button button-secondary" href="/servicios">Ver servicios</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

