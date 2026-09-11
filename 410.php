<?php
declare(strict_types=1);

http_response_code(410);
$pageTitle = 'Contenido retirado | TECNOXPERT';
$pageDescription = 'El contenido solicitado fue retirado permanentemente.';
$canonicalPath = '/410';
$activeNav = '';
$noIndex = true;
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="shell narrow">
    <p class="eyebrow">Estado 410</p>
    <h1>Contenido retirado permanentemente</h1>
    <p>La dirección solicitada ya no forma parte de este sitio y no tiene una ruta de reemplazo.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

