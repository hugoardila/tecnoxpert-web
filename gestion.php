<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/admin.php';

tx_send_page_headers(true);
tx_start_session();

function tx_admin_redirect(string $path = '/gestion'): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function tx_admin_date(string $value, string $fallback = 'Sin fecha'): string
{
    $timestamp = strtotime($value);
    return $timestamp === false ? $fallback : date('d/m/Y, g:i a', $timestamp);
}

function tx_admin_excerpt(string $value, int $length = 105): string
{
    $clean = preg_replace('/\s+/u', ' ', trim($value)) ?: '';
    return mb_strlen($clean, 'UTF-8') > $length
        ? mb_substr($clean, 0, $length - 1, 'UTF-8') . '…'
        : $clean;
}

$loginError = '';
if (!tx_admin_is_authenticated()) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($_POST['action'] ?? '') === 'login') {
        if (!tx_verify_csrf($_POST['csrf_token'] ?? null)) {
            $loginError = 'La sesión venció. Recarga la página e intenta nuevamente.';
        } else {
            $result = tx_admin_login(
                tx_clean_text($_POST['username'] ?? '', 60),
                (string)($_POST['password'] ?? '')
            );
            if ($result === 'ok') {
                tx_admin_redirect();
            }
            $loginError = match ($result) {
                'locked' => 'Acceso bloqueado temporalmente por varios intentos fallidos.',
                'not_configured' => 'El acceso administrativo aún no está configurado.',
                default => 'Usuario o contraseña incorrectos.',
            };
        }
    }

    $loginCsrf = tx_csrf_token();
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive">
  <title>Acceso administrativo | TECNOXPERT</title>
  <link rel="icon" href="/favicon.ico?v=20260723c" sizes="any">
  <link rel="stylesheet" href="/assets/css/site.css?v=20260723e">
  <link rel="stylesheet" href="/assets/css/admin.css?v=20260723a">
</head>
<body class="admin-login-body">
  <main class="admin-login-shell">
    <section class="admin-login-panel" aria-labelledby="admin-login-title">
      <a class="admin-login-brand" href="/" aria-label="Ir al sitio de TECNOXPERT">
        <img src="/assets/img/logo.png?v=20260723b" alt="" width="52" height="52">
        <span><strong>TECNOXPERT</strong><small>Administración interna</small></span>
      </a>
      <div class="admin-login-heading">
        <p class="eyebrow">Acceso restringido</p>
        <h1 id="admin-login-title">Gestiona solicitudes y cotizaciones</h1>
        <p>Ingresa con la cuenta administrativa autorizada.</p>
      </div>
      <?php if ($loginError !== ''): ?>
        <p class="status-message is-error" role="alert"><?= tx_e($loginError) ?></p>
      <?php endif; ?>
      <form action="/gestion" method="post" autocomplete="on">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf_token" value="<?= tx_e($loginCsrf) ?>">
        <div class="field">
          <label for="admin_username">Usuario</label>
          <input id="admin_username" name="username" type="text" maxlength="60" autocomplete="username" required autofocus>
        </div>
        <div class="field">
          <label for="admin_password">Contraseña</label>
          <input id="admin_password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button admin-login-submit" type="submit">Ingresar al panel</button>
      </form>
      <a class="admin-back-link" href="/">Volver al sitio principal</a>
    </section>
  </main>
</body>
</html>
    <?php
    exit;
}

$csrfToken = tx_csrf_token();
$requests = tx_admin_load_requests();
$states = tx_admin_load_request_states();
$quotes = tx_load_quotes();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!tx_verify_csrf($_POST['csrf_token'] ?? null)) {
        tx_admin_flash('error', 'La sesión venció. Intenta nuevamente.');
        tx_admin_redirect();
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'logout') {
        tx_admin_logout();
        tx_admin_redirect();
    }

    $requestId = tx_clean_text($_POST['request_id'] ?? '', 60);
    $request = tx_admin_find_request($requests, $requestId);
    $returnPath = '/gestion?request=' . rawurlencode($requestId);

    try {
        if ($request === null) {
            throw new RuntimeException('La solicitud seleccionada ya no está disponible.');
        }

        $currentState = tx_admin_request_status($request, $states, $quotes);
        $currentStatus = (string)($currentState['status'] ?? 'new');

        if ($action === 'mark_review') {
            if ($currentStatus === 'quoted') {
                throw new RuntimeException('La solicitud ya fue cotizada.');
            }
            tx_admin_update_request_state($requestId, 'in_review', ['rejection_reason' => null]);
            tx_admin_flash('success', 'La solicitud quedó marcada en revisión.');
        } elseif ($action === 'reject') {
            if ($currentStatus === 'quoted') {
                throw new RuntimeException('No se puede rechazar una solicitud que ya tiene cotización.');
            }
            $reason = tx_clean_text($_POST['rejection_reason'] ?? '', 500);
            if ($reason === '') {
                throw new InvalidArgumentException('Indica el motivo del rechazo.');
            }
            tx_admin_update_request_state($requestId, 'rejected', ['rejection_reason' => $reason]);
            tx_admin_flash('success', 'La solicitud fue rechazada y quedó registrada en el historial.');
        } elseif ($action === 'reopen') {
            if ($currentStatus !== 'rejected') {
                throw new RuntimeException('Solo se pueden reabrir solicitudes rechazadas.');
            }
            tx_admin_update_request_state($requestId, 'in_review', ['rejection_reason' => null]);
            tx_admin_flash('success', 'La solicitud fue reabierta y está nuevamente en revisión.');
        } elseif ($action === 'create_quote') {
            if ($currentStatus === 'quoted') {
                throw new RuntimeException('La solicitud ya tiene una cotización asociada.');
            }
            $created = tx_admin_create_quote($request, $_POST);
            $reference = (string)($created['quote']['reference'] ?? '');
            tx_admin_flash(
                'success',
                'Cotización ' . $reference . ' creada correctamente. Este enlace privado se muestra ahora para que puedas enviarlo al cliente.',
                (string)$created['private_link']
            );
        } else {
            throw new InvalidArgumentException('La acción solicitada no es válida.');
        }
    } catch (Throwable $error) {
        tx_admin_flash('error', $error->getMessage());
    }

    tx_admin_redirect($returnPath);
}

$flash = tx_admin_take_flash();
$view = (string)($_GET['view'] ?? 'requests');
$view = in_array($view, ['requests', 'quotes'], true) ? $view : 'requests';
$filter = (string)($_GET['status'] ?? 'all');
$filter = in_array($filter, ['all', 'new', 'in_review', 'quoted', 'rejected'], true) ? $filter : 'all';
$search = tx_clean_text($_GET['q'] ?? '', 100);

$requestRows = [];
$metrics = ['all' => 0, 'new' => 0, 'in_review' => 0, 'quoted' => 0, 'rejected' => 0];
foreach ($requests as $request) {
    $state = tx_admin_request_status($request, $states, $quotes);
    $status = (string)($state['status'] ?? 'new');
    $metrics['all']++;
    if (array_key_exists($status, $metrics)) {
        $metrics[$status]++;
    }
    $request['_state'] = $state;

    $haystack = mb_strtolower(implode(' ', [
        (string)($request['id'] ?? ''),
        (string)($request['name'] ?? ''),
        (string)($request['email'] ?? ''),
        (string)($request['phone'] ?? ''),
        (string)($request['service'] ?? ''),
        (string)($request['message'] ?? ''),
    ]), 'UTF-8');
    if ($filter !== 'all' && $status !== $filter) {
        continue;
    }
    if ($search !== '' && !str_contains($haystack, mb_strtolower($search, 'UTF-8'))) {
        continue;
    }
    $requestRows[] = $request;
}

$selectedRequestId = tx_clean_text($_GET['request'] ?? '', 60);
$selectedRequest = $selectedRequestId !== '' ? tx_admin_find_request($requests, $selectedRequestId) : null;
if ($selectedRequest === null && $requestRows !== []) {
    $selectedRequest = $requestRows[0];
    $selectedRequestId = (string)$selectedRequest['id'];
}
$selectedState = $selectedRequest !== null
    ? tx_admin_request_status($selectedRequest, $states, $quotes)
    : ['status' => 'new'];
$selectedQuote = $selectedRequest !== null
    ? tx_admin_find_quote_for_request($quotes, (string)$selectedRequest['id'])
    : null;
$defaultExpiry = date('Y-m-d', strtotime('+15 days'));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive">
  <title>Panel administrativo | TECNOXPERT</title>
  <link rel="icon" href="/favicon.ico?v=20260723c" sizes="any">
  <link rel="stylesheet" href="/assets/css/site.css?v=20260723e">
  <link rel="stylesheet" href="/assets/css/admin.css?v=20260723a">
  <script src="/assets/js/admin.js?v=20260723a" defer></script>
</head>
<body class="admin-body">
  <header class="admin-topbar">
    <div class="admin-topbar-inner">
      <a class="admin-brand" href="/gestion">
        <img src="/assets/img/logo.png?v=20260723b" alt="" width="42" height="42">
        <span><strong>TECNOXPERT</strong><small>Panel administrativo</small></span>
      </a>
      <nav class="admin-nav" aria-label="Secciones administrativas">
        <a href="/gestion"<?= $view === 'requests' ? ' aria-current="page"' : '' ?>>Solicitudes <span><?= $metrics['all'] ?></span></a>
        <a href="/gestion?view=quotes"<?= $view === 'quotes' ? ' aria-current="page"' : '' ?>>Cotizaciones <span><?= count($quotes) ?></span></a>
      </nav>
      <div class="admin-account">
        <span><?= tx_e(tx_admin_username()) ?></span>
        <form action="/gestion" method="post">
          <input type="hidden" name="action" value="logout">
          <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
          <button class="admin-logout" type="submit">Cerrar sesión</button>
        </form>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <?php if ($flash !== null): ?>
      <div class="admin-flash <?= ($flash['type'] ?? '') === 'error' ? 'is-error' : 'is-success' ?>" role="status">
        <div>
          <strong><?= ($flash['type'] ?? '') === 'error' ? 'No se pudo completar' : 'Proceso completado' ?></strong>
          <p><?= tx_e((string)($flash['message'] ?? '')) ?></p>
        </div>
        <?php if (!empty($flash['link'])): ?>
          <div class="admin-flash-link">
            <input type="text" readonly value="<?= tx_e((string)$flash['link']) ?>" aria-label="Enlace privado de la cotización">
            <button class="button button-small" type="button" data-copy-value="<?= tx_e((string)$flash['link']) ?>">Copiar enlace</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($view === 'quotes'): ?>
      <section class="admin-page-heading">
        <div>
          <p class="eyebrow">Control comercial</p>
          <h1>Cotizaciones emitidas</h1>
          <p>Consulta valores, clientes y estados registrados en el servidor.</p>
        </div>
        <a class="button button-secondary" href="/gestion">Volver a solicitudes</a>
      </section>

      <section class="admin-table-section" aria-labelledby="quotes-table-title">
        <div class="admin-section-title">
          <h2 id="quotes-table-title"><?= count($quotes) ?> cotizaciones</h2>
          <p>Los enlaces de consulta requieren la referencia y el correo del cliente.</p>
        </div>
        <?php if ($quotes === []): ?>
          <div class="admin-empty">
            <h2>Aún no hay cotizaciones</h2>
            <p>Aprueba una solicitud y asigna su precio para crear la primera.</p>
            <a class="button" href="/gestion">Revisar solicitudes</a>
          </div>
        <?php else: ?>
          <div class="admin-table-scroll">
            <table class="admin-table">
              <thead><tr><th>Referencia</th><th>Cliente</th><th>Servicio</th><th>Estado</th><th>Total</th><th>Creada</th><th>Consulta</th></tr></thead>
              <tbody>
                <?php foreach (array_reverse($quotes) as $quote):
                    if (!is_array($quote)) {
                        continue;
                    }
                    $quoteStatus = (string)($quote['status'] ?? 'accepted');
                ?>
                  <tr>
                    <td><strong><?= tx_e((string)($quote['reference'] ?? 'Sin referencia')) ?></strong></td>
                    <td><?= tx_e((string)($quote['customer_name'] ?? 'Cliente')) ?><small><?= tx_e((string)($quote['customer_email'] ?? '')) ?></small></td>
                    <td><?= tx_e(tx_admin_excerpt((string)($quote['title'] ?? 'Servicio'), 55)) ?></td>
                    <td><span class="admin-status status-<?= tx_e($quoteStatus) ?>"><?= tx_e((string)($quote['status_label'] ?? $quoteStatus)) ?></span></td>
                    <td><strong><?= tx_e(tx_money((float)($quote['total'] ?? 0), (string)($quote['currency'] ?? 'COP'))) ?></strong></td>
                    <td><?= tx_e(tx_admin_date((string)($quote['created_at'] ?? ''), 'Registro anterior')) ?></td>
                    <td><a class="admin-text-action" href="/pagar?ref=<?= rawurlencode((string)($quote['reference'] ?? '')) ?>" target="_blank">Abrir</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <section class="admin-page-heading">
        <div>
          <p class="eyebrow">Bandeja comercial</p>
          <h1>Solicitudes recibidas</h1>
          <p>Revisa cada caso, asigna un valor y genera la cotización para el cliente.</p>
        </div>
        <div class="admin-live-indicator"><span></span> Datos privados del servidor</div>
      </section>

      <section class="admin-metrics" aria-label="Resumen de solicitudes">
        <a href="/gestion" class="<?= $filter === 'all' ? 'is-active' : '' ?>"><span>Total</span><strong><?= $metrics['all'] ?></strong></a>
        <a href="/gestion?status=new" class="<?= $filter === 'new' ? 'is-active' : '' ?>"><span>Nuevas</span><strong><?= $metrics['new'] ?></strong></a>
        <a href="/gestion?status=in_review" class="<?= $filter === 'in_review' ? 'is-active' : '' ?>"><span>En revisión</span><strong><?= $metrics['in_review'] ?></strong></a>
        <a href="/gestion?status=quoted" class="<?= $filter === 'quoted' ? 'is-active' : '' ?>"><span>Cotizadas</span><strong><?= $metrics['quoted'] ?></strong></a>
        <a href="/gestion?status=rejected" class="<?= $filter === 'rejected' ? 'is-active' : '' ?>"><span>Rechazadas</span><strong><?= $metrics['rejected'] ?></strong></a>
      </section>

      <form class="admin-filters" action="/gestion" method="get">
        <div class="field">
          <label for="admin_search">Buscar solicitud</label>
          <input id="admin_search" name="q" type="search" value="<?= tx_e($search) ?>" placeholder="Cliente, teléfono, correo o servicio">
        </div>
        <div class="field">
          <label for="admin_status">Estado</label>
          <select id="admin_status" name="status">
            <option value="all"<?= $filter === 'all' ? ' selected' : '' ?>>Todos los estados</option>
            <option value="new"<?= $filter === 'new' ? ' selected' : '' ?>>Nuevas</option>
            <option value="in_review"<?= $filter === 'in_review' ? ' selected' : '' ?>>En revisión</option>
            <option value="quoted"<?= $filter === 'quoted' ? ' selected' : '' ?>>Cotizadas</option>
            <option value="rejected"<?= $filter === 'rejected' ? ' selected' : '' ?>>Rechazadas</option>
          </select>
        </div>
        <button class="button button-secondary" type="submit">Aplicar filtros</button>
      </form>

      <div class="admin-workspace">
        <aside class="admin-request-list" aria-label="Listado de solicitudes">
          <div class="admin-list-heading">
            <strong><?= count($requestRows) ?> resultados</strong>
            <?php if ($search !== '' || $filter !== 'all'): ?><a href="/gestion">Limpiar</a><?php endif; ?>
          </div>
          <?php if ($requestRows === []): ?>
            <div class="admin-empty compact">
              <h2>Sin resultados</h2>
              <p>No hay solicitudes que coincidan con estos filtros.</p>
            </div>
          <?php else: ?>
            <?php foreach ($requestRows as $request):
                $requestState = $request['_state'];
                $requestStatus = (string)($requestState['status'] ?? 'new');
                $requestId = (string)($request['id'] ?? '');
                $requestUrl = '/gestion?request=' . rawurlencode($requestId)
                    . ($filter !== 'all' ? '&status=' . rawurlencode($filter) : '')
                    . ($search !== '' ? '&q=' . rawurlencode($search) : '');
            ?>
              <a class="admin-request-item<?= $selectedRequestId === $requestId ? ' is-selected' : '' ?>" href="<?= tx_e($requestUrl) ?>">
                <span class="admin-request-line">
                  <strong><?= tx_e((string)($request['name'] ?? 'Cliente')) ?></strong>
                  <span class="admin-status status-<?= tx_e($requestStatus) ?>"><?= tx_e(tx_admin_status_label($requestStatus)) ?></span>
                </span>
                <span class="admin-request-service"><?= tx_e((string)($request['service'] ?? 'Solicitud general')) ?></span>
                <span class="admin-request-message"><?= tx_e(tx_admin_excerpt((string)($request['message'] ?? ''))) ?></span>
                <span class="admin-request-date"><?= tx_e(tx_admin_date((string)($request['received_at'] ?? ''))) ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </aside>

        <section class="admin-detail" aria-live="polite">
          <?php if ($selectedRequest === null): ?>
            <div class="admin-empty">
              <h2>Selecciona una solicitud</h2>
              <p>Aquí aparecerán los datos del cliente y las opciones para procesarla.</p>
            </div>
          <?php else:
              $selectedStatus = (string)($selectedState['status'] ?? 'new');
              $selectedId = (string)$selectedRequest['id'];
          ?>
            <header class="admin-detail-header">
              <div>
                <span class="admin-status status-<?= tx_e($selectedStatus) ?>"><?= tx_e(tx_admin_status_label($selectedStatus)) ?></span>
                <h2><?= tx_e((string)($selectedRequest['name'] ?? 'Cliente')) ?></h2>
                <p><?= tx_e($selectedId) ?> · <?= tx_e(tx_admin_date((string)($selectedRequest['received_at'] ?? ''))) ?></p>
              </div>
              <?php if ($selectedStatus === 'new'): ?>
                <form action="/gestion" method="post">
                  <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                  <input type="hidden" name="action" value="mark_review">
                  <input type="hidden" name="request_id" value="<?= tx_e($selectedId) ?>">
                  <button class="button button-secondary" type="submit">Marcar en revisión</button>
                </form>
              <?php elseif ($selectedStatus === 'rejected'): ?>
                <form action="/gestion" method="post">
                  <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                  <input type="hidden" name="action" value="reopen">
                  <input type="hidden" name="request_id" value="<?= tx_e($selectedId) ?>">
                  <button class="button button-secondary" type="submit">Reabrir solicitud</button>
                </form>
              <?php endif; ?>
            </header>

            <dl class="admin-contact-grid">
              <div><dt>Correo</dt><dd><a href="mailto:<?= tx_e((string)$selectedRequest['email']) ?>"><?= tx_e((string)$selectedRequest['email']) ?></a></dd></div>
              <div><dt>Teléfono</dt><dd><a href="https://wa.me/<?= tx_e(preg_replace('/\D+/', '', (string)$selectedRequest['phone']) ?: '') ?>" target="_blank"><?= tx_e((string)$selectedRequest['phone']) ?></a></dd></div>
              <div><dt>Ciudad</dt><dd><?= tx_e((string)($selectedRequest['city'] ?? 'No indicada')) ?></dd></div>
              <div><dt>Servicio</dt><dd><?= tx_e((string)($selectedRequest['service'] ?? 'Solicitud general')) ?></dd></div>
              <?php if (!empty($selectedRequest['reference'])): ?>
                <div><dt>Referencia relacionada</dt><dd><?= tx_e((string)$selectedRequest['reference']) ?></dd></div>
              <?php endif; ?>
            </dl>

            <section class="admin-message-block" aria-labelledby="request-message-title">
              <h3 id="request-message-title">Descripción del cliente</h3>
              <p><?= nl2br(tx_e((string)($selectedRequest['message'] ?? 'Sin descripción.'))) ?></p>
            </section>

            <?php if ($selectedStatus === 'quoted' && $selectedQuote !== null): ?>
              <section class="admin-quote-result">
                <div class="admin-section-title">
                  <p class="eyebrow">Solicitud procesada</p>
                  <h3>Cotización <?= tx_e((string)$selectedQuote['reference']) ?></h3>
                  <p><?= tx_e((string)($selectedQuote['title'] ?? 'Servicio cotizado')) ?></p>
                  <span class="admin-status status-<?= tx_e((string)($selectedQuote['status'] ?? 'pending_acceptance')) ?>"><?= tx_e((string)($selectedQuote['status_label'] ?? 'Pendiente de aceptación')) ?></span>
                </div>
                <dl class="admin-quote-values">
                  <div><dt>Subtotal</dt><dd><?= tx_e(tx_money((float)($selectedQuote['subtotal'] ?? 0), (string)($selectedQuote['currency'] ?? 'COP'))) ?></dd></div>
                  <div><dt>Impuestos</dt><dd><?= tx_e(tx_money((float)($selectedQuote['tax'] ?? 0), (string)($selectedQuote['currency'] ?? 'COP'))) ?></dd></div>
                  <div class="is-total"><dt>Total aprobado</dt><dd><?= tx_e(tx_money((float)($selectedQuote['total'] ?? 0), (string)($selectedQuote['currency'] ?? 'COP'))) ?></dd></div>
                </dl>
                <div class="admin-action-row">
                  <a class="button" href="/pagar?ref=<?= rawurlencode((string)$selectedQuote['reference']) ?>" target="_blank">Abrir cotización</a>
                  <button class="button button-secondary" type="button" data-copy-value="<?= tx_e(tx_url('/pagar?ref=' . rawurlencode((string)$selectedQuote['reference']))) ?>">Copiar consulta</button>
                </div>
                <p class="field-help">El enlace de consulta solicita el correo del cliente. El enlace privado directo solo se muestra inmediatamente después de crear la cotización.</p>
              </section>
            <?php else: ?>
              <?php if ($selectedStatus === 'rejected'): ?>
                <div class="admin-rejection-note">
                  <strong>Motivo del rechazo</strong>
                  <p><?= tx_e((string)($selectedState['rejection_reason'] ?? 'No se registró un motivo.')) ?></p>
                </div>
              <?php else: ?>
                <section class="admin-processing" aria-labelledby="quote-form-title">
                  <div class="admin-section-title">
                    <p class="eyebrow">Aprobar y valorar</p>
                    <h3 id="quote-form-title">Crear cotización</h3>
                    <p>El total se calcula en el servidor y el cliente no podrá modificarlo.</p>
                  </div>
                  <form action="/gestion" method="post" data-admin-quote-form>
                    <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="create_quote">
                    <input type="hidden" name="request_id" value="<?= tx_e($selectedId) ?>">
                    <div class="field">
                      <label for="quote_title">Servicio o producto *</label>
                      <input id="quote_title" name="title" type="text" maxlength="180" value="<?= tx_e((string)($selectedRequest['service'] ?? '')) ?>" required>
                    </div>
                    <div class="field">
                      <label for="quote_description">Alcance aprobado *</label>
                      <textarea id="quote_description" name="description" maxlength="3000" required><?= tx_e((string)($selectedRequest['message'] ?? '')) ?></textarea>
                    </div>
                    <div class="admin-price-grid">
                      <div class="field">
                        <label for="quote_subtotal">Subtotal *</label>
                        <input id="quote_subtotal" name="subtotal" type="number" min="1" step="1" inputmode="numeric" placeholder="0" required data-quote-subtotal>
                      </div>
                      <div class="field">
                        <label for="quote_tax">Impuestos</label>
                        <input id="quote_tax" name="tax" type="number" min="0" step="1" inputmode="numeric" value="0" required data-quote-tax>
                      </div>
                      <div class="field">
                        <label for="quote_currency">Moneda</label>
                        <select id="quote_currency" name="currency" data-quote-currency>
                          <option value="COP">COP</option>
                          <option value="USD">USD</option>
                        </select>
                        <p class="field-help">Puedes cotizar en COP o USD. El cliente verá el equivalente en la moneda de su país y podrá elegir cualquiera de los métodos configurados.</p>
                      </div>
                      <div class="admin-calculated-total">
                        <span>Total</span>
                        <strong data-quote-total>$ 0 COP</strong>
                      </div>
                    </div>
                    <div class="field-grid">
                      <div class="field">
                        <label for="quote_delivery">Plazo o entrega *</label>
                        <input id="quote_delivery" name="delivery" type="text" maxlength="180" placeholder="Ejemplo: 5 días hábiles" required>
                      </div>
                      <div class="field">
                        <label for="quote_expires">Válida hasta</label>
                        <input id="quote_expires" name="expires" type="date" min="<?= date('Y-m-d') ?>" value="<?= tx_e($defaultExpiry) ?>">
                      </div>
                    </div>
                    <div class="field">
                      <label for="quote_payment_url">Enlace externo adicional</label>
                      <input id="quote_payment_url" name="payment_url" type="url" maxlength="1000" placeholder="Opcional: solo para enlaces emitidos anteriormente">
                      <p class="field-help">Déjalo vacío para usar el selector automático de ePayco, dLocal Go, transferencia local y PayPal cuando esté habilitado.</p>
                    </div>
                    <button class="button" type="submit">Aprobar y crear cotización</button>
                  </form>
                </section>

                <details class="admin-reject-panel">
                  <summary>Rechazar solicitud</summary>
                  <form action="/gestion" method="post">
                    <input type="hidden" name="csrf_token" value="<?= tx_e($csrfToken) ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" value="<?= tx_e($selectedId) ?>">
                    <div class="field">
                      <label for="rejection_reason">Motivo del rechazo *</label>
                      <textarea id="rejection_reason" name="rejection_reason" maxlength="500" required placeholder="Deja una razón breve para el historial interno."></textarea>
                    </div>
                    <button class="button admin-danger-button" type="submit">Confirmar rechazo</button>
                  </form>
                </details>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </section>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
