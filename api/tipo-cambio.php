<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/payments.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

tx_start_session();
$reference = tx_normalize_reference($_GET['ref'] ?? '');
$country = tx_payment_normalize_country($_GET['country'] ?? '');
$hasAccess = $reference !== ''
    && (int)($_SESSION['quote_access'][$reference] ?? 0) > time() - 1800;
$quote = $hasAccess ? tx_find_quote($reference) : null;

if ($quote === null || $country === '' || !tx_quote_is_payable($quote)) {
    tx_json_response(['success' => false, 'message' => 'No fue posible validar la cotización.'], 404);
}

try {
    $preview = tx_payment_conversion_preview($quote, $country);
    tx_json_response([
        'success' => true,
        'country' => $preview['country'],
        'local' => $preview['local'],
        'providers' => $preview['providers'],
        'notice' => 'Valor referencial. La pasarela o el banco pueden aplicar su propia tasa y comisiones.',
    ]);
} catch (Throwable $error) {
    error_log('TECNOXPERT FX preview failed [' . $reference . '/' . $country . ']: ' . $error->getMessage());
    tx_json_response([
        'success' => false,
        'message' => 'No fue posible calcular una tasa vigente. Intenta nuevamente.',
    ], 503);
}
