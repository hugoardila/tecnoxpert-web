<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pageTitle = $pageTitle ?? TX_NAME;
$pageDescription = $pageDescription ?? 'Soluciones tecnológicas para empresas y emprendedores.';
$canonicalPath = $canonicalPath ?? '/';
$activeNav = $activeNav ?? '';
$noIndex = (bool)($noIndex ?? false);
$preloadHero = (bool)($preloadHero ?? false);
$structuredData = $structuredData ?? null;

tx_send_page_headers($noIndex);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= tx_e($pageTitle) ?></title>
  <meta name="description" content="<?= tx_e($pageDescription) ?>">
  <meta name="robots" content="<?= $noIndex ? 'noindex,nofollow,noarchive' : 'index,follow,max-image-preview:large' ?>">
  <link rel="canonical" href="<?= tx_e(tx_url($canonicalPath)) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="es_CO">
  <meta property="og:site_name" content="<?= TX_NAME ?>">
  <meta property="og:title" content="<?= tx_e($pageTitle) ?>">
  <meta property="og:description" content="<?= tx_e($pageDescription) ?>">
  <meta property="og:url" content="<?= tx_e(tx_url($canonicalPath)) ?>">
  <meta property="og:image" content="<?= tx_e(tx_url('/assets/img/logo-fondo.webp')) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#06152f">
  <link rel="icon" href="/favicon.ico?v=20260723c" sizes="any">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/favicon.png?v=20260723c">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png?v=20260723c">
  <?php if ($preloadHero): ?>
    <link rel="preload" as="image" href="/assets/img/logo-fondo.webp" type="image/webp" fetchpriority="high">
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/css/site.css?v=20260815b">
  <?php if (is_array($structuredData)): ?>
    <script type="application/ld+json" nonce="<?= tx_e(tx_nonce()) ?>"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
  <script src="/assets/js/site.js?v=20260723d" defer></script>
</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido principal</a>
  <header class="site-header">
    <div class="shell header-inner">
      <a class="brand" href="/" aria-label="Ir al inicio de TECNOXPERT">
        <img src="/assets/img/logo.png?v=20260723b" alt="" width="42" height="42">
        <span>
          <strong>TECNOXPERT</strong>
          <small>Soluciones tecnológicas</small>
        </span>
      </a>
      <button class="menu-toggle" type="button" aria-controls="main-navigation" aria-expanded="false" aria-label="Abrir menú">
        <span></span><span></span><span></span>
      </button>
      <nav id="main-navigation" class="main-nav" aria-label="Navegación principal">
        <a<?= $activeNav === 'inicio' ? ' aria-current="page"' : '' ?> href="/">Inicio</a>
        <a<?= $activeNav === 'servicios' ? ' aria-current="page"' : '' ?> href="/servicios">Servicios</a>
        <a<?= $activeNav === 'portafolio' ? ' aria-current="page"' : '' ?> href="/portafolio">Portafolio</a>
        <a<?= $activeNav === 'pagos' ? ' aria-current="page"' : '' ?> href="/pagar">Pagar cotización</a>
        <a<?= $activeNav === 'contacto' ? ' aria-current="page"' : '' ?> href="/contacto">Contacto</a>
      </nav>
      <a class="button button-small header-cta" href="/contacto?motivo=cotizacion">Solicitar cotización</a>
    </div>
  </header>
  <main id="contenido">
