<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/legal-content.php';

$documentKey = (string)($_GET['documento'] ?? '');
if (!isset($txLegalDocuments[$documentKey])) {
    require __DIR__ . '/404.php';
    exit;
}

$document = $txLegalDocuments[$documentKey];
$pageTitle = $document['title'] . ' | TECNOXPERT';
$pageDescription = $document['description'];
$canonicalPath = '/' . $documentKey;
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<article class="legal-page">
  <div class="shell narrow">
    <nav class="breadcrumbs" aria-label="Migas de pan"><a href="/">Inicio</a><span>/</span><span><?= tx_e($document['title']) ?></span></nav>
    <h1><?= tx_e($document['title']) ?></h1>
    <p class="updated">Última actualización: 15 de agosto de 2026.</p>
    <?= $document['content'] ?>
  </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
