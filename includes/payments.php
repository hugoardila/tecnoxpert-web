<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function tx_payment_config(): array
{
    $file = tx_private_path('payments.json');
    if (!is_file($file)) {
        return [];
    }
    $decoded = json_decode((string)file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function tx_payment_countries(): array
{
    return [
        'AR' => 'Argentina',
        'BO' => 'Bolivia',
        'BR' => 'Brasil',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'CR' => 'Costa Rica',
        'EC' => 'Ecuador',
        'GT' => 'Guatemala',
        'MX' => 'México',
        'PA' => 'Panamá',
        'PY' => 'Paraguay',
        'PE' => 'Perú',
        'UY' => 'Uruguay',
    ];
}

function tx_payment_normalize_country(mixed $value): string
{
    $country = strtoupper(trim((string)$value));
    return array_key_exists($country, tx_payment_countries()) ? $country : '';
}

function tx_payment_country_currency(string $country): string
{
    return match (tx_payment_normalize_country($country)) {
        'AR' => 'ARS',
        'BO' => 'BOB',
        'BR' => 'BRL',
        'CL' => 'CLP',
        'CO' => 'COP',
        'CR' => 'CRC',
        'EC', 'PA' => 'USD',
        'GT' => 'GTQ',
        'MX' => 'MXN',
        'PY' => 'PYG',
        'PE' => 'PEN',
        'UY' => 'UYU',
        default => '',
    };
}

function tx_payment_currency_decimals(string $currency): int
{
    return in_array(strtoupper($currency), ['CLP', 'COP', 'CRC', 'PYG'], true) ? 0 : 2;
}

function tx_payment_round_amount(float $amount, string $currency): float
{
    return round($amount, tx_payment_currency_decimals($currency));
}

function tx_payment_fx_cache_file(): string
{
    return tx_private_path('fx-rates/rates.json');
}

function tx_payment_fx_rate(string $baseCurrency, string $quoteCurrency): array
{
    $base = strtoupper(trim($baseCurrency));
    $quote = strtoupper(trim($quoteCurrency));
    if (!preg_match('/^[A-Z]{3}$/', $base) || !preg_match('/^[A-Z]{3}$/', $quote)) {
        throw new RuntimeException('La moneda para conversión no es válida.');
    }
    if ($base === $quote) {
        return [
            'base' => $base,
            'quote' => $quote,
            'rate' => 1.0,
            'date' => date('Y-m-d'),
            'source' => 'same_currency',
            'stale' => false,
        ];
    }

    $file = tx_payment_fx_cache_file();
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No fue posible preparar la caché de conversión.');
    }
    $lockFile = $file . '.lock';
    $lock = fopen($lockFile, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('No fue posible bloquear la caché de conversión.');
    }
    $directoryStat = @stat($directory);
    @chmod($lockFile, 0640);
    if (is_array($directoryStat)) {
        @chown($lockFile, (int)$directoryStat['uid']);
        @chgrp($lockFile, (int)$directoryStat['gid']);
    }

    try {
        $cache = [];
        if (is_file($file)) {
            $decoded = json_decode((string)file_get_contents($file), true);
            $cache = is_array($decoded) ? $decoded : [];
        }
        $key = $base . '-' . $quote;
        $cached = is_array($cache[$key] ?? null) ? $cache[$key] : null;
        $cachedAt = $cached !== null ? strtotime((string)($cached['cached_at'] ?? '')) : false;
        if ($cached !== null && $cachedAt !== false && $cachedAt > time() - 21600) {
            $cached['stale'] = false;
            return $cached;
        }

        try {
            $response = tx_payment_http_json(
                'https://api.frankfurter.dev/v2/rate/' . rawurlencode($base) . '/' . rawurlencode($quote)
            );
            $rate = (float)($response['data']['rate'] ?? 0);
            $rateDate = trim((string)($response['data']['date'] ?? ''));
            $rateTimestamp = $rateDate !== '' ? strtotime($rateDate) : false;
            if (
                $response['status'] !== 200
                || $rate <= 0
                || !is_finite($rate)
                || $rateTimestamp === false
                || $rateTimestamp < time() - 604800
                || strtoupper((string)($response['data']['base'] ?? '')) !== $base
                || strtoupper((string)($response['data']['quote'] ?? '')) !== $quote
            ) {
                throw new RuntimeException('La fuente no devolvió una tasa válida.');
            }
            $fresh = [
                'base' => $base,
                'quote' => $quote,
                'rate' => $rate,
                'date' => $rateDate,
                'source' => 'Frankfurter central bank rates',
                'cached_at' => date(DATE_ATOM),
                'stale' => false,
            ];
            $cache[$key] = $fresh;
            tx_payment_write_json_atomic($file, $cache);
            return $fresh;
        } catch (Throwable $error) {
            if ($cached !== null && $cachedAt !== false && $cachedAt > time() - 86400) {
                $cached['stale'] = true;
                return $cached;
            }
            throw new RuntimeException('No fue posible obtener una tasa de cambio vigente.', 0, $error);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function tx_payment_provider_currency(string $provider, string $country): string
{
    return match ($provider) {
        'epayco' => $country === 'CO' ? 'COP' : 'USD',
        'dlocal_go' => tx_payment_country_currency($country),
        'stripe' => tx_payment_country_currency($country),
        'paypal' => 'USD',
        'bank_transfer' => 'COP',
        default => '',
    };
}

function tx_payment_conversion(array $quote, string $country, string $provider): array
{
    $country = tx_payment_normalize_country($country);
    $localCurrency = tx_payment_country_currency($country);
    $chargeCurrency = tx_payment_provider_currency($provider, $country);
    $quoteCurrency = strtoupper((string)($quote['currency'] ?? 'COP'));
    $quoteAmount = (float)($quote['total'] ?? 0);
    if ($country === '' || $localCurrency === '' || $chargeCurrency === '' || $quoteAmount <= 0) {
        throw new RuntimeException('No fue posible preparar la conversión del pago.');
    }

    $localRate = tx_payment_fx_rate($quoteCurrency, $localCurrency);
    $chargeRate = $chargeCurrency === $localCurrency
        ? $localRate
        : tx_payment_fx_rate($quoteCurrency, $chargeCurrency);
    $localAmount = tx_payment_round_amount($quoteAmount * (float)$localRate['rate'], $localCurrency);
    $chargeAmount = tx_payment_round_amount($quoteAmount * (float)$chargeRate['rate'], $chargeCurrency);
    if ($localAmount <= 0 || $chargeAmount <= 0) {
        throw new RuntimeException('La conversión produjo un valor no válido.');
    }

    return [
        'quote_amount' => $quoteAmount,
        'quote_currency' => $quoteCurrency,
        'local_amount' => $localAmount,
        'local_currency' => $localCurrency,
        'charge_amount' => $chargeAmount,
        'charge_currency' => $chargeCurrency,
        'local_rate' => (float)$localRate['rate'],
        'charge_rate' => (float)$chargeRate['rate'],
        'rate_date' => (string)$localRate['date'],
        'rate_source' => (string)$localRate['source'],
        'local_rate_date' => (string)$localRate['date'],
        'local_rate_source' => (string)$localRate['source'],
        'charge_rate_date' => (string)$chargeRate['date'],
        'charge_rate_source' => (string)$chargeRate['source'],
        'rate_stale' => ($localRate['stale'] ?? false) === true || ($chargeRate['stale'] ?? false) === true,
    ];
}

function tx_payment_conversion_preview(array $quote, string $country): array
{
    $country = tx_payment_normalize_country($country);
    if ($country === '') {
        throw new RuntimeException('Selecciona un país válido.');
    }
    $methods = tx_payment_methods($quote, $country);
    $providers = [];
    $local = null;
    foreach ($methods as $provider => $method) {
        if (($method['enabled'] ?? false) !== true) {
            continue;
        }
        $conversion = tx_payment_conversion($quote, $country, $provider);
        $local ??= [
            'amount' => $conversion['local_amount'],
            'currency' => $conversion['local_currency'],
            'formatted' => tx_money($conversion['local_amount'], $conversion['local_currency']),
            'rate_date' => $conversion['rate_date'],
            'rate_source' => $conversion['rate_source'],
            'stale' => $conversion['rate_stale'],
        ];
        $providers[$provider] = [
            'amount' => $conversion['charge_amount'],
            'currency' => $conversion['charge_currency'],
            'formatted' => tx_money($conversion['charge_amount'], $conversion['charge_currency']),
        ];
    }
    if ($local === null || $providers === []) {
        throw new RuntimeException('No hay métodos configurados para calcular el pago.');
    }
    return ['country' => $country, 'local' => $local, 'providers' => $providers];
}

function tx_payment_methods(array $quote, ?string $payerCountry = null): array
{
    $config = tx_payment_config();
    $latinCountries = array_keys(tx_payment_countries());
    $normalizedPayerCountry = $payerCountry === null ? null : tx_payment_normalize_country($payerCountry);
    $methods = [];

    $epayco = is_array($config['epayco'] ?? null) ? $config['epayco'] : [];
    $epaycoConfigured = ($epayco['enabled'] ?? false) === true
        && trim((string)($epayco['customer_id'] ?? '')) !== ''
        && trim((string)($epayco['p_key'] ?? '')) !== ''
        && trim((string)($epayco['public_key'] ?? '')) !== ''
        && trim((string)($epayco['private_key'] ?? '')) !== '';
    $methods['epayco'] = [
        'name' => 'ePayco',
        'short' => 'eP',
        'description' => 'Checkout internacional con conversión a COP o USD según el país.',
        'countries' => $latinCountries,
        'enabled' => $epaycoConfigured,
        'reason' => 'Configuración incompleta.',
    ];

    $dlocal = is_array($config['dlocal_go'] ?? null) ? $config['dlocal_go'] : [];
    $dlocalConfigured = ($dlocal['enabled'] ?? false) === true
        && trim((string)($dlocal['api_key'] ?? '')) !== ''
        && trim((string)($dlocal['api_secret'] ?? '')) !== '';
    $methods['dlocal_go'] = [
        'name' => 'dLocal Go',
        'short' => 'dL',
        'description' => 'Pago con los medios y la moneda local del país seleccionado.',
        'countries' => $latinCountries,
        'enabled' => $dlocalConfigured,
        'reason' => 'Configuración incompleta.',
    ];

    $stripe = is_array($config['stripe'] ?? null) ? $config['stripe'] : [];
    $stripeConfigured = ($stripe['enabled'] ?? false) === true
        && str_starts_with(trim((string)($stripe['secret_key'] ?? '')), 'sk_live_')
        && str_starts_with(trim((string)($stripe['webhook_secret'] ?? '')), 'whsec_');
    $methods['stripe'] = [
        'name' => 'Stripe',
        'short' => 'St',
        'description' => 'Checkout seguro con conversión a la moneda del país seleccionado.',
        'countries' => $latinCountries,
        'enabled' => $stripeConfigured,
        'reason' => 'Configuración incompleta.',
    ];

    $transfer = is_array($config['bank_transfer'] ?? null) ? $config['bank_transfer'] : [];
    $transferConfigured = ($transfer['enabled'] ?? false) === true
        && trim((string)($transfer['bank'] ?? '')) !== ''
        && trim((string)($transfer['account'] ?? '')) !== '';
    $transferCountryAllowed = $normalizedPayerCountry === null || $normalizedPayerCountry === 'CO';
    $methods['bank_transfer'] = [
        'name' => 'Transferencia bancaria local',
        'short' => 'TR',
        'description' => 'Transferencia en COP a una cuenta colombiana.',
        'countries' => ['CO'],
        'enabled' => $transferConfigured && $transferCountryAllowed,
        'reason' => $transferConfigured
            ? 'Solo disponible para pagos desde Colombia.'
            : 'Configuración incompleta.',
        'country_reason' => 'Solo disponible para pagos desde Colombia.',
    ];

    $paypal = is_array($config['paypal'] ?? null) ? $config['paypal'] : [];
    $paypalConfigured = ($paypal['enabled'] ?? false) === true
        && trim((string)($paypal['client_id'] ?? '')) !== ''
        && trim((string)($paypal['client_secret'] ?? '')) !== '';
    $methods['paypal'] = [
        'name' => 'PayPal',
        'short' => 'PP',
        'description' => 'Pago internacional con conversión automática a USD.',
        'countries' => $latinCountries,
        'enabled' => $paypalConfigured,
        'reason' => 'Pendiente de configurar el Client Secret.',
    ];

    return $methods;
}

function tx_payment_http_json(string $url, string $method = 'GET', ?array $payload = null, array $headers = []): array
{
    $handle = curl_init($url);
    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_USERAGENT => 'TECNOXPERT-Payments/1.0',
    ]);
    if ($payload !== null) {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException('No fue posible preparar la solicitud de pago.');
        }
        curl_setopt($handle, CURLOPT_POSTFIELDS, $encoded);
    }
    $raw = curl_exec($handle);
    if ($raw === false) {
        $message = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException('No fue posible conectar con el proveedor: ' . $message);
    }
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    $decoded = json_decode($raw, true);
    return [
        'status' => $status,
        'data' => is_array($decoded) ? $decoded : [],
        'raw' => $raw,
    ];
}

function tx_payment_http_form(string $url, array $fields, array $headers = []): array
{
    $handle = curl_init($url);
    $requestHeaders = array_merge([
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ], $headers);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_USERAGENT => 'TECNOXPERT-Payments/1.0',
    ]);
    $raw = curl_exec($handle);
    if ($raw === false) {
        $message = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException('No fue posible conectar con el proveedor: ' . $message);
    }
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    $decoded = json_decode($raw, true);
    return [
        'status' => $status,
        'data' => is_array($decoded) ? $decoded : [],
        'raw' => $raw,
    ];
}

function tx_payment_stripe_minor_amount(float $amount, string $currency): int
{
    $currency = strtoupper(trim($currency));
    $decimals = in_array($currency, ['CLP', 'PYG'], true) ? 0 : 2;
    $minorAmount = (int)round($amount * (10 ** $decimals));
    if ($minorAmount <= 0) {
        throw new RuntimeException('El importe para Stripe no es válido.');
    }
    return $minorAmount;
}

function tx_payment_attempt_file(): string
{
    return tx_private_path('payment-attempts/attempts.json');
}

function tx_payment_load_attempts(): array
{
    $file = tx_payment_attempt_file();
    if (!is_file($file)) {
        return [];
    }
    $decoded = json_decode((string)file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function tx_payment_write_json_atomic(string $file, array $data): void
{
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No fue posible preparar el registro de pagos.');
    }
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        throw new RuntimeException('No fue posible serializar el registro de pagos.');
    }
    $permissionStat = is_file($file) ? @stat($file) : @stat($directory);
    $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('No fue posible guardar el registro de pagos.');
    }
    @chmod($temporary, 0640);
    if (is_array($permissionStat)) {
        @chown($temporary, (int)$permissionStat['uid']);
        @chgrp($temporary, (int)$permissionStat['gid']);
    }
    if (!rename($temporary, $file)) {
        @unlink($temporary);
        throw new RuntimeException('No fue posible publicar el registro de pagos.');
    }
}

function tx_payment_mutate_attempts(callable $callback): mixed
{
    $file = tx_payment_attempt_file();
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No fue posible preparar el registro de pagos.');
    }
    $lock = fopen($file . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('No fue posible bloquear el registro de pagos.');
    }
    try {
        $attempts = tx_payment_load_attempts();
        $result = $callback($attempts);
        tx_payment_write_json_atomic($file, $attempts);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function tx_payment_create_attempt(array $quote, string $provider, string $payerCountry, array $conversion): array
{
    $accessToken = bin2hex(random_bytes(24));
    $attempt = [
        'id' => 'TXP-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3))),
        'quote_reference' => (string)$quote['reference'],
        'provider' => $provider,
        'amount' => (float)$conversion['charge_amount'],
        'currency' => (string)$conversion['charge_currency'],
        'quote_amount' => (float)$conversion['quote_amount'],
        'quote_currency' => (string)$conversion['quote_currency'],
        'local_amount' => (float)$conversion['local_amount'],
        'local_currency' => (string)$conversion['local_currency'],
        'local_rate' => (float)$conversion['local_rate'],
        'charge_rate' => (float)$conversion['charge_rate'],
        'rate_date' => (string)$conversion['rate_date'],
        'rate_source' => (string)$conversion['rate_source'],
        'local_rate_date' => (string)$conversion['local_rate_date'],
        'local_rate_source' => (string)$conversion['local_rate_source'],
        'charge_rate_date' => (string)$conversion['charge_rate_date'],
        'charge_rate_source' => (string)$conversion['charge_rate_source'],
        'rate_stale' => ($conversion['rate_stale'] ?? false) === true,
        'customer_name' => (string)($quote['customer_name'] ?? 'Cliente'),
        'customer_email' => (string)($quote['customer_email'] ?? ''),
        'payer_country' => $payerCountry,
        'access_token_hash' => hash('sha256', $accessToken),
        'status' => $provider === 'bank_transfer' ? 'awaiting_transfer' : 'created',
        'provider_id' => null,
        'created_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM),
    ];
    tx_payment_mutate_attempts(static function (array &$attempts) use ($attempt): void {
        $attempts[$attempt['id']] = $attempt;
    });
    $attempt['access_token'] = $accessToken;
    return $attempt;
}

function tx_payment_update_attempt(string $attemptId, array $changes): ?array
{
    return tx_payment_mutate_attempts(static function (array &$attempts) use ($attemptId, $changes): ?array {
        if (!is_array($attempts[$attemptId] ?? null)) {
            return null;
        }
        unset(
            $changes['id'],
            $changes['quote_reference'],
            $changes['amount'],
            $changes['currency'],
            $changes['quote_amount'],
            $changes['quote_currency'],
            $changes['provider'],
            $changes['payer_country'],
            $changes['local_amount'],
            $changes['local_currency'],
            $changes['local_rate'],
            $changes['charge_rate'],
            $changes['rate_date'],
            $changes['rate_source'],
            $changes['local_rate_date'],
            $changes['local_rate_source'],
            $changes['charge_rate_date'],
            $changes['charge_rate_source'],
            $changes['rate_stale']
        );
        $attempts[$attemptId] = array_merge($attempts[$attemptId], $changes, ['updated_at' => date(DATE_ATOM)]);
        return $attempts[$attemptId];
    });
}

function tx_payment_find_attempt(string $attemptId): ?array
{
    $attempts = tx_payment_load_attempts();
    return is_array($attempts[$attemptId] ?? null) ? $attempts[$attemptId] : null;
}

function tx_payment_find_attempt_by_provider_id(string $provider, string $providerId): ?array
{
    foreach (tx_payment_load_attempts() as $attempt) {
        if (
            is_array($attempt)
            && (string)($attempt['provider'] ?? '') === $provider
            && (string)($attempt['provider_id'] ?? '') === $providerId
        ) {
            return $attempt;
        }
    }
    return null;
}

function tx_payment_attempt_matches_token(array $attempt, string $token): bool
{
    $stored = (string)($attempt['access_token_hash'] ?? '');
    return $stored !== '' && $token !== '' && hash_equals($stored, hash('sha256', $token));
}

function tx_payment_start(array $quote, string $provider, string $payerCountry): array
{
    $status = (string)($quote['status'] ?? '');
    if (!in_array($status, ['accepted', 'payment_pending'], true) || tx_quote_is_expired($quote)) {
        throw new RuntimeException('La cotización debe estar aceptada antes de pagar.');
    }
    $payerCountry = tx_payment_normalize_country($payerCountry);
    if ($payerCountry === '') {
        throw new RuntimeException('Selecciona un país válido antes de continuar.');
    }
    $methods = tx_payment_methods($quote, $payerCountry);
    if (!is_array($methods[$provider] ?? null) || ($methods[$provider]['enabled'] ?? false) !== true) {
        throw new RuntimeException('El método de pago seleccionado no está disponible para esta cotización.');
    }

    $conversion = tx_payment_conversion($quote, $payerCountry, $provider);
    $attempt = tx_payment_create_attempt($quote, $provider, $payerCountry, $conversion);
    $access = (string)$attempt['access_token'];
    try {
        if ($provider === 'bank_transfer') {
            return [
                'attempt' => $attempt,
                'redirect_url' => tx_url('/pago-transferencia?attempt=' . rawurlencode($attempt['id']) . '&access=' . rawurlencode($access)),
            ];
        }
        if ($provider === 'epayco') {
            return tx_payment_start_epayco($attempt);
        }
        if ($provider === 'dlocal_go') {
            return tx_payment_start_dlocal($attempt);
        }
        if ($provider === 'stripe') {
            return tx_payment_start_stripe($attempt);
        }
        if ($provider === 'paypal') {
            return tx_payment_start_paypal($attempt);
        }
    } catch (Throwable $error) {
        tx_payment_update_attempt($attempt['id'], [
            'status' => 'gateway_error',
            'error_code' => get_class($error),
        ]);
        throw $error;
    }
    throw new RuntimeException('Proveedor de pago no reconocido.');
}

function tx_payment_start_epayco(array $attempt): array
{
    $config = tx_payment_config()['epayco'] ?? [];
    $login = tx_payment_http_json(
        'https://apify.epayco.co/login',
        'POST',
        null,
        ['Content-Type: application/json', 'Authorization: Basic ' . base64_encode(
            (string)$config['public_key'] . ':' . (string)$config['private_key']
        )]
    );
    $token = trim((string)($login['data']['token'] ?? ''));
    if ($login['status'] < 200 || $login['status'] >= 300 || $token === '') {
        throw new RuntimeException('ePayco rechazó la autenticación del comercio.');
    }

    $returnUrl = tx_url('/retorno-pago?provider=epayco&attempt=' . rawurlencode((string)$attempt['id'])
        . '&access=' . rawurlencode((string)$attempt['access_token']));
    $payload = [
        'checkout_version' => '2',
        'name' => TX_NAME,
        'description' => 'Cotización ' . $attempt['quote_reference'],
        'lang' => 'ES',
        'country' => (string)$attempt['payer_country'],
        'currency' => $attempt['currency'],
        'amount' => $attempt['amount'],
        'invoice' => $attempt['quote_reference'],
        'extra1' => $attempt['id'],
        'response' => $returnUrl,
        'confirmation' => tx_url('/webhooks/epayco.php'),
        'method' => 'GET',
        'forceResponse' => true,
        'uniqueTransactionPerBill' => true,
        'billing' => [
            'email' => $attempt['customer_email'],
            'name' => $attempt['customer_name'],
        ],
    ];
    $session = tx_payment_http_json(
        'https://apify.epayco.co/payment/session/create',
        'POST',
        $payload,
        ['Content-Type: application/json', 'Authorization: Bearer ' . $token]
    );
    $sessionId = trim((string)($session['data']['data']['sessionId'] ?? ''));
    if ($session['status'] < 200 || $session['status'] >= 300 || $sessionId === '') {
        throw new RuntimeException('ePayco no pudo crear la sesión de pago.');
    }
    tx_payment_update_attempt((string)$attempt['id'], [
        'status' => 'pending',
        'provider_id' => $sessionId,
    ]);
    return [
        'attempt' => $attempt,
        'redirect_url' => tx_url('/checkout-epayco?attempt=' . rawurlencode((string)$attempt['id'])
            . '&access=' . rawurlencode((string)$attempt['access_token'])),
    ];
}

function tx_payment_start_dlocal(array $attempt): array
{
    $config = tx_payment_config()['dlocal_go'] ?? [];
    $endpoint = ($config['environment'] ?? 'live') === 'sandbox'
        ? 'https://api-sbx.dlocalgo.com/v1/payments/'
        : 'https://api.dlocalgo.com/v1/payments/';
    $nameParts = preg_split('/\s+/u', trim((string)$attempt['customer_name'])) ?: [];
    $firstName = array_shift($nameParts) ?: 'Cliente';
    $lastName = trim(implode(' ', $nameParts));
    $returnUrl = tx_url('/retorno-pago?provider=dlocal_go&attempt=' . rawurlencode((string)$attempt['id'])
        . '&access=' . rawurlencode((string)$attempt['access_token']));
    $payload = [
        'country' => (string)$attempt['payer_country'],
        'currency' => $attempt['currency'],
        'amount' => $attempt['amount'],
        'order_id' => $attempt['quote_reference'],
        'payer' => array_filter([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $attempt['customer_email'],
            'user_reference' => $attempt['quote_reference'],
        ]),
        'description' => 'Cotización ' . $attempt['quote_reference'],
        'success_url' => $returnUrl,
        'notification_url' => tx_url('/webhooks/dlocal-go.php'),
        'back_url' => tx_url('/pagar?ref=' . rawurlencode((string)$attempt['quote_reference'])),
        'url_source' => TX_BASE_URL,
    ];
    $response = tx_payment_http_json(
        $endpoint,
        'POST',
        $payload,
        [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . (string)$config['api_key'] . ':' . (string)$config['api_secret'],
            'Referer: ' . TX_BASE_URL,
        ]
    );
    $redirectUrl = trim((string)($response['data']['redirect_url'] ?? ''));
    $providerId = trim((string)($response['data']['id'] ?? ''));
    $host = strtolower((string)(parse_url($redirectUrl, PHP_URL_HOST) ?: ''));
    if (
        $response['status'] < 200
        || $response['status'] >= 300
        || $providerId === ''
        || $redirectUrl === ''
        || !($host === 'dlocalgo.com' || str_ends_with($host, '.dlocalgo.com'))
    ) {
        throw new RuntimeException('dLocal Go no pudo crear el checkout.');
    }
    tx_payment_update_attempt((string)$attempt['id'], [
        'status' => 'pending',
        'provider_id' => $providerId,
    ]);
    return ['attempt' => $attempt, 'redirect_url' => $redirectUrl];
}

function tx_payment_start_stripe(array $attempt): array
{
    $config = tx_payment_config()['stripe'] ?? [];
    $returnUrl = tx_url('/retorno-pago?provider=stripe&attempt=' . rawurlencode((string)$attempt['id'])
        . '&access=' . rawurlencode((string)$attempt['access_token'])
        . '&session_id={CHECKOUT_SESSION_ID}');
    $cancelUrl = tx_url('/pagar?ref=' . rawurlencode((string)$attempt['quote_reference'])
        . '&estado=pago-cancelado');
    $metadata = [
        'app' => 'tecnoxpert',
        'attempt_id' => (string)$attempt['id'],
        'quote_reference' => (string)$attempt['quote_reference'],
    ];
    $fields = [
        'mode' => 'payment',
        'locale' => 'es',
        'submit_type' => 'pay',
        'client_reference_id' => (string)$attempt['id'],
        'success_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower((string)$attempt['currency']),
                'unit_amount' => tx_payment_stripe_minor_amount(
                    (float)$attempt['amount'],
                    (string)$attempt['currency']
                ),
                'product_data' => [
                    'name' => 'Cotización ' . (string)$attempt['quote_reference'],
                    'description' => 'Servicios TECNOXPERT',
                ],
            ],
        ]],
        'metadata' => $metadata,
        'payment_intent_data' => ['metadata' => $metadata],
    ];
    $email = trim((string)($attempt['customer_email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fields['customer_email'] = $email;
    }
    $response = tx_payment_http_form(
        'https://api.stripe.com/v1/checkout/sessions',
        $fields,
        [
            'Authorization: Bearer ' . (string)$config['secret_key'],
            'Idempotency-Key: tx-checkout-' . (string)$attempt['id'],
        ]
    );
    $sessionId = trim((string)($response['data']['id'] ?? ''));
    $redirectUrl = trim((string)($response['data']['url'] ?? ''));
    $host = strtolower((string)(parse_url($redirectUrl, PHP_URL_HOST) ?: ''));
    if (
        $response['status'] < 200
        || $response['status'] >= 300
        || !preg_match('/^cs_(?:live|test)_[A-Za-z0-9]+$/', $sessionId)
        || $host !== 'checkout.stripe.com'
    ) {
        throw new RuntimeException('Stripe no pudo crear la sesión de pago.');
    }
    tx_payment_update_attempt((string)$attempt['id'], [
        'status' => 'pending',
        'provider_id' => $sessionId,
    ]);
    return ['attempt' => $attempt, 'redirect_url' => $redirectUrl];
}

function tx_payment_paypal_access_token(array $config): string
{
    $base = ($config['environment'] ?? 'live') === 'sandbox'
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
    $handle = curl_init($base . '/v1/oauth2/token');
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => (string)$config['client_id'] . ':' . (string)$config['client_secret'],
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: es_CO'],
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $token = is_array($decoded) ? trim((string)($decoded['access_token'] ?? '')) : '';
    if ($status !== 200 || $token === '') {
        throw new RuntimeException('PayPal rechazó la autenticación del comercio.');
    }
    return $token;
}

function tx_payment_start_paypal(array $attempt): array
{
    $config = tx_payment_config()['paypal'] ?? [];
    $token = tx_payment_paypal_access_token($config);
    $base = ($config['environment'] ?? 'live') === 'sandbox'
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
    $returnUrl = tx_url('/retorno-pago?provider=paypal&attempt=' . rawurlencode((string)$attempt['id'])
        . '&access=' . rawurlencode((string)$attempt['access_token']));
    $response = tx_payment_http_json(
        $base . '/v2/checkout/orders',
        'POST',
        [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $attempt['id'],
                'invoice_id' => $attempt['quote_reference'],
                'custom_id' => $attempt['quote_reference'],
                'description' => 'Cotización ' . $attempt['quote_reference'],
                'amount' => [
                    'currency_code' => $attempt['currency'],
                    'value' => number_format((float)$attempt['amount'], 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => TX_NAME,
                'locale' => 'es-CO',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => tx_url('/pagar?ref=' . rawurlencode((string)$attempt['quote_reference']) . '&estado=pago-cancelado'),
            ],
        ],
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . $attempt['id'],
            'Prefer: return=representation',
        ]
    );
    $orderId = trim((string)($response['data']['id'] ?? ''));
    $approveUrl = '';
    foreach (($response['data']['links'] ?? []) as $link) {
        if (is_array($link) && (string)($link['rel'] ?? '') === 'approve') {
            $approveUrl = (string)($link['href'] ?? '');
            break;
        }
    }
    $host = strtolower((string)(parse_url($approveUrl, PHP_URL_HOST) ?: ''));
    if (
        !in_array($response['status'], [200, 201], true)
        || $orderId === ''
        || $approveUrl === ''
        || !($host === 'paypal.com' || str_ends_with($host, '.paypal.com'))
    ) {
        throw new RuntimeException('PayPal no pudo crear la orden.');
    }
    tx_payment_update_attempt((string)$attempt['id'], [
        'status' => 'pending',
        'provider_id' => $orderId,
    ]);
    return ['attempt' => $attempt, 'redirect_url' => $approveUrl];
}

function tx_payment_mark_quote_paid(array $attempt, string $providerPaymentId): array
{
    $file = tx_private_path('cotizaciones.json');
    $lock = fopen($file . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('No fue posible bloquear la cotización.');
    }
    try {
        $quotes = json_decode((string)file_get_contents($file), true);
        if (!is_array($quotes)) {
            throw new RuntimeException('El archivo de cotizaciones no es válido.');
        }
        $paidQuote = null;
        foreach ($quotes as $index => $quote) {
            if (!is_array($quote) || (string)($quote['reference'] ?? '') !== (string)$attempt['quote_reference']) {
                continue;
            }
            if (
                abs((float)($quote['total'] ?? 0) - (float)($attempt['quote_amount'] ?? $attempt['amount'])) > 0.01
                || strtoupper((string)($quote['currency'] ?? '')) !== strtoupper(
                    (string)($attempt['quote_currency'] ?? $attempt['currency'])
                )
            ) {
                throw new RuntimeException('El pago no coincide con la cotización.');
            }
            if ((string)($quote['status'] ?? '') === 'paid') {
                return $quote;
            }
            if (!in_array((string)($quote['status'] ?? ''), ['accepted', 'payment_pending'], true)) {
                throw new RuntimeException('La cotización no está habilitada para pago.');
            }
            $quotes[$index]['status'] = 'paid';
            $quotes[$index]['status_label'] = 'Pago confirmado';
            $quotes[$index]['payment_provider'] = (string)$attempt['provider'];
            $quotes[$index]['provider_payment_id'] = $providerPaymentId;
            $quotes[$index]['paid_at'] = date(DATE_ATOM);
            $paidQuote = $quotes[$index];
            break;
        }
        if ($paidQuote === null) {
            throw new RuntimeException('No se encontró la cotización asociada.');
        }
        tx_payment_write_json_atomic($file, $quotes);
        $attemptChanges = [
            'status' => 'paid',
            'provider_transaction_id' => $providerPaymentId,
            'paid_at' => date(DATE_ATOM),
        ];
        if ((string)($attempt['provider'] ?? '') !== 'stripe') {
            $attemptChanges['provider_id'] = $providerPaymentId;
        }
        tx_payment_update_attempt((string)$attempt['id'], $attemptChanges);
        return $paidQuote;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function tx_payment_process_epayco(array $data): string
{
    $config = tx_payment_config()['epayco'] ?? [];
    $required = ['x_signature', 'x_cust_id_cliente', 'x_ref_payco', 'x_transaction_id', 'x_amount', 'x_currency_code'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            throw new RuntimeException('Respuesta incompleta de ePayco.');
        }
    }
    if (!hash_equals((string)$config['customer_id'], (string)$data['x_cust_id_cliente'])) {
        throw new RuntimeException('Comercio ePayco no válido.');
    }
    $signatureSource = implode('^', [
        (string)$data['x_cust_id_cliente'],
        (string)$config['p_key'],
        (string)$data['x_ref_payco'],
        (string)$data['x_transaction_id'],
        (string)$data['x_amount'],
        (string)$data['x_currency_code'],
    ]);
    if (!hash_equals(strtolower((string)$data['x_signature']), hash('sha256', $signatureSource))) {
        throw new RuntimeException('Firma ePayco no válida.');
    }
    $attemptId = trim((string)($data['x_extra1'] ?? ''));
    if ($attemptId === '') {
        $attemptId = trim((string)($data['x_id_invoice'] ?? ''));
    }
    $attempt = tx_payment_find_attempt($attemptId);
    if ($attempt === null || (string)($attempt['provider'] ?? '') !== 'epayco') {
        throw new RuntimeException('Referencia ePayco desconocida.');
    }
    if (
        abs((float)$data['x_amount'] - (float)$attempt['amount']) > 0.01
        || strtoupper((string)$data['x_currency_code']) !== strtoupper((string)$attempt['currency'])
    ) {
        throw new RuntimeException('El importe ePayco no coincide.');
    }
    $responseCode = (string)($data['x_cod_response'] ?? '');
    if ($responseCode === '1') {
        tx_payment_mark_quote_paid($attempt, (string)$data['x_transaction_id']);
        return 'paid';
    }
    $status = in_array($responseCode, ['2', '4', '6', '9', '10', '11'], true) ? 'declined' : 'pending';
    tx_payment_update_attempt($attemptId, [
        'status' => $status,
        'provider_transaction_id' => (string)$data['x_transaction_id'],
    ]);
    return $status;
}

function tx_payment_verify_epayco_reference(array $attempt, string $refPayco): string
{
    if ($refPayco === '') {
        return 'pending';
    }
    $response = tx_payment_http_json(
        'https://secure.epayco.co/validation/v1/reference/' . rawurlencode($refPayco)
    );
    $data = $response['data']['data'] ?? $response['data'];
    if ($response['status'] !== 200 || !is_array($data)) {
        throw new RuntimeException('No fue posible validar la referencia de ePayco.');
    }
    return tx_payment_process_epayco($data);
}

function tx_payment_verify_dlocal(array $attempt, ?string $paymentId = null): string
{
    $config = tx_payment_config()['dlocal_go'] ?? [];
    $providerId = $paymentId ?: (string)($attempt['provider_id'] ?? '');
    if ($providerId === '' || !preg_match('/^[A-Za-z0-9_-]{3,100}$/', $providerId)) {
        throw new RuntimeException('Identificador dLocal Go no válido.');
    }
    $base = ($config['environment'] ?? 'live') === 'sandbox'
        ? 'https://api-sbx.dlocalgo.com'
        : 'https://api.dlocalgo.com';
    $response = tx_payment_http_json(
        $base . '/v1/payments/' . rawurlencode($providerId),
        'GET',
        null,
        ['Authorization: Bearer ' . (string)$config['api_key'] . ':' . (string)$config['api_secret']]
    );
    $data = $response['data'];
    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new RuntimeException('No fue posible verificar el pago con dLocal Go.');
    }
    if (
        (string)($data['order_id'] ?? '') !== (string)$attempt['quote_reference']
        || abs((float)($data['amount'] ?? 0) - (float)$attempt['amount']) > 0.01
        || strtoupper((string)($data['currency'] ?? '')) !== strtoupper((string)$attempt['currency'])
    ) {
        throw new RuntimeException('El pago dLocal Go no coincide con la cotización.');
    }
    $status = strtoupper((string)($data['status'] ?? 'PENDING'));
    if ($status === 'PAID') {
        tx_payment_mark_quote_paid($attempt, $providerId);
        return 'paid';
    }
    $normalized = in_array($status, ['REJECTED', 'CANCELLED', 'EXPIRED'], true) ? 'declined' : 'pending';
    tx_payment_update_attempt((string)$attempt['id'], ['status' => $normalized]);
    return $normalized;
}

function tx_payment_stripe_session(string $sessionId): array
{
    if (!preg_match('/^cs_(?:live|test)_[A-Za-z0-9]+$/', $sessionId)) {
        throw new RuntimeException('Identificador Stripe no válido.');
    }
    $config = tx_payment_config()['stripe'] ?? [];
    $response = tx_payment_http_json(
        'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId),
        'GET',
        null,
        ['Authorization: Bearer ' . (string)($config['secret_key'] ?? '')]
    );
    if ($response['status'] < 200 || $response['status'] >= 300 || !is_array($response['data'])) {
        throw new RuntimeException('No fue posible verificar el pago con Stripe.');
    }
    return $response['data'];
}

function tx_payment_verify_stripe(array $attempt, ?string $sessionId = null): string
{
    $storedSessionId = trim((string)($attempt['provider_id'] ?? ''));
    $sessionId = trim((string)($sessionId ?? $storedSessionId));
    if ($storedSessionId === '' || $sessionId === '' || !hash_equals($storedSessionId, $sessionId)) {
        throw new RuntimeException('La sesión Stripe no coincide.');
    }
    $session = tx_payment_stripe_session($sessionId);
    $metadata = is_array($session['metadata'] ?? null) ? $session['metadata'] : [];
    if (
        (string)($session['id'] ?? '') !== $sessionId
        || (string)($session['client_reference_id'] ?? '') !== (string)$attempt['id']
        || (string)($metadata['app'] ?? '') !== 'tecnoxpert'
        || (string)($metadata['attempt_id'] ?? '') !== (string)$attempt['id']
        || (string)($metadata['quote_reference'] ?? '') !== (string)$attempt['quote_reference']
        || (int)($session['amount_total'] ?? -1) !== tx_payment_stripe_minor_amount(
            (float)$attempt['amount'],
            (string)$attempt['currency']
        )
        || strtoupper((string)($session['currency'] ?? '')) !== strtoupper((string)$attempt['currency'])
    ) {
        throw new RuntimeException('El pago Stripe no coincide con la cotización.');
    }
    if ((string)($session['payment_status'] ?? '') === 'paid') {
        $paymentId = trim((string)($session['payment_intent'] ?? '')) ?: $sessionId;
        tx_payment_mark_quote_paid($attempt, $paymentId);
        return 'paid';
    }
    $status = (string)($session['status'] ?? 'open') === 'expired' ? 'declined' : 'pending';
    tx_payment_update_attempt((string)$attempt['id'], ['status' => $status]);
    return $status;
}

function tx_payment_stripe_event(string $payload, string $signatureHeader): array
{
    if ($payload === '' || strlen($payload) > 1048576 || $signatureHeader === '') {
        throw new RuntimeException('Evento Stripe incompleto.');
    }
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't' && ctype_digit($value)) {
            $timestamp = (int)$value;
        } elseif ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $value)) {
            $signatures[] = strtolower($value);
        }
    }
    if ($timestamp === null || abs(time() - $timestamp) > 300 || $signatures === []) {
        throw new RuntimeException('Firma Stripe no válida.');
    }
    $secret = trim((string)((tx_payment_config()['stripe'] ?? [])['webhook_secret'] ?? ''));
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $valid = false;
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        throw new RuntimeException('Firma Stripe no válida.');
    }
    $event = json_decode($payload, true);
    if (!is_array($event) || ($event['livemode'] ?? false) !== true) {
        throw new RuntimeException('Evento Stripe no válido.');
    }
    return $event;
}

function tx_payment_capture_paypal(array $attempt): string
{
    $config = tx_payment_config()['paypal'] ?? [];
    $orderId = (string)($attempt['provider_id'] ?? '');
    $token = tx_payment_paypal_access_token($config);
    $base = ($config['environment'] ?? 'live') === 'sandbox'
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com';
    $response = tx_payment_http_json(
        $base . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
        'POST',
        [],
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: capture-' . $attempt['id'],
            'Prefer: return=representation',
        ]
    );
    $data = $response['data'];
    $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (!is_array($capture)) {
        throw new RuntimeException('PayPal no devolvió una captura verificable.');
    }
    if (
        (string)($data['id'] ?? '') !== $orderId
        || (string)($data['purchase_units'][0]['invoice_id'] ?? '') !== (string)$attempt['quote_reference']
        || abs((float)($capture['amount']['value'] ?? 0) - (float)$attempt['amount']) > 0.01
        || strtoupper((string)($capture['amount']['currency_code'] ?? '')) !== strtoupper((string)$attempt['currency'])
    ) {
        throw new RuntimeException('El pago PayPal no coincide con la cotización.');
    }
    $status = strtoupper((string)($capture['status'] ?? 'PENDING'));
    if ($status === 'COMPLETED') {
        tx_payment_mark_quote_paid($attempt, (string)$capture['id']);
        return 'paid';
    }
    tx_payment_update_attempt((string)$attempt['id'], ['status' => 'pending']);
    return 'pending';
}

function tx_payment_report_transfer(array $attempt): void
{
    if ((string)($attempt['provider'] ?? '') !== 'bank_transfer') {
        throw new RuntimeException('El intento no corresponde a una transferencia.');
    }
    tx_payment_update_attempt((string)$attempt['id'], [
        'status' => 'reported',
        'reported_at' => date(DATE_ATOM),
    ]);
}

function tx_payment_send_epayco_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    $nonce = tx_nonce();
    header('Content-Security-Policy: '
        . "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; "
        . "script-src 'self' 'nonce-{$nonce}' https://checkout.epayco.co; "
        . "connect-src 'self' https://checkout.epayco.co https://apify.epayco.co https://secure.epayco.co; "
        . "frame-src https://checkout.epayco.co https://secure.epayco.co; "
        . "img-src 'self' data: https://checkout.epayco.co https://secure.epayco.co; "
        . "style-src 'self' 'unsafe-inline'; object-src 'none'; upgrade-insecure-requests");
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (tx_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
