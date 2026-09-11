<?php
declare(strict_types=1);

date_default_timezone_set('America/Bogota');

const TX_NAME = 'TECNOXPERT';
const TX_OWNER = 'Hugo Alberto Ardila Molina';
const TX_NIT = '1083903212-3';
const TX_CITY = 'Pitalito, Huila';
const TX_COUNTRY = 'Colombia';
const TX_PHONE_DISPLAY = '311 702 4021';
const TX_PHONE_E164 = '573117024021';
const TX_EMAIL_SALES = 'asesoria@tecnoxpert.com';
const TX_EMAIL_SUPPORT = 'contacto@tecnoxpert.com';
const TX_BASE_URL = 'https://tecnoxpert.com';

if (!defined('TX_PRIVATE_DIR')) {
    $privateDir = getenv('TECNOXPERT_PRIVATE_DIR');
    define('TX_PRIVATE_DIR', $privateDir !== false && $privateDir !== ''
        ? rtrim($privateDir, DIRECTORY_SEPARATOR)
        : '/opt/lampp/var/tecnoxpert');
}

function tx_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tx_url(string $path = '/'): string
{
    return TX_BASE_URL . '/' . ltrim($path, '/');
}

function tx_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function tx_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }
    return $nonce;
}

function tx_send_page_headers(bool $noIndex = false): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-TECNOXPERT-Release: 20260816c');
    $nonce = tx_nonce();
    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "connect-src 'self'",
        "font-src 'self'",
        "form-action 'self' https://checkout.stripe.com",
        "frame-ancestors 'none'",
        "frame-src 'none'",
        "img-src 'self' data:",
        "media-src 'self'",
        "object-src 'none'",
        "script-src 'self' 'nonce-{$nonce}'",
        "style-src 'self'",
        'upgrade-insecure-requests',
    ];

    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (tx_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if ($noIndex) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }
}

function tx_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('tecnoxpert_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => tx_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function tx_csrf_token(): string
{
    tx_start_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function tx_verify_csrf(?string $token): bool
{
    tx_start_session();
    $expected = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && is_string($expected) && $expected !== ''
        && hash_equals($expected, $token);
}

function tx_form_proof(int $timestamp): string
{
    return hash_hmac('sha256', (string)$timestamp, tx_csrf_token());
}

function tx_verify_form_proof(int $timestamp, ?string $proof): bool
{
    if ($timestamp < 1 || !is_string($proof)) {
        return false;
    }
    return hash_equals(tx_form_proof($timestamp), $proof);
}

function tx_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 64);
}

function tx_clean_text(mixed $value, int $maxLength = 2000): string
{
    $text = trim((string)$value);
    $text = str_replace(["\r", "\0"], '', $text);
    return mb_substr($text, 0, $maxLength, 'UTF-8');
}

function tx_private_path(string $relative = ''): string
{
    return TX_PRIVATE_DIR . ($relative !== '' ? DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : '');
}

function tx_ensure_private_directory(string $relative = ''): string
{
    $path = tx_private_path($relative);
    if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException('No fue posible preparar el almacenamiento privado.');
    }
    return $path;
}

function tx_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function tx_normalize_reference(mixed $value): string
{
    $reference = strtoupper(trim((string)$value));
    return preg_match('/^[A-Z0-9][A-Z0-9-]{5,39}$/', $reference) ? $reference : '';
}

function tx_normalize_email(mixed $value): string
{
    return mb_strtolower(trim((string)$value), 'UTF-8');
}

function tx_email_hash(string $email): string
{
    return hash('sha256', tx_normalize_email($email));
}

function tx_load_quotes(): array
{
    $file = tx_private_path('cotizaciones.json');
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        return [];
    }
    $quotes = json_decode($raw, true);
    return is_array($quotes) ? $quotes : [];
}

function tx_find_quote(string $reference): ?array
{
    foreach (tx_load_quotes() as $quote) {
        if (is_array($quote) && (string)($quote['reference'] ?? '') === $reference) {
            return $quote;
        }
    }
    return null;
}

function tx_quote_matches_email(array $quote, string $email): bool
{
    $storedHash = (string)($quote['lookup_email_hash'] ?? '');
    return $storedHash !== '' && hash_equals($storedHash, tx_email_hash($email));
}

function tx_quote_matches_token(array $quote, string $token): bool
{
    $storedHash = (string)($quote['access_token_hash'] ?? '');
    return $storedHash !== '' && $token !== ''
        && hash_equals($storedHash, hash('sha256', $token));
}

function tx_quote_is_expired(array $quote): bool
{
    $expiresAt = (string)($quote['expires_at'] ?? '');
    if ($expiresAt === '') {
        return false;
    }
    $timestamp = strtotime($expiresAt);
    return $timestamp !== false && $timestamp < time();
}

function tx_quote_is_payable(array $quote): bool
{
    $status = (string)($quote['status'] ?? '');
    $total = (float)($quote['total'] ?? 0);
    return in_array($status, ['accepted', 'payment_pending'], true)
        && $total > 0
        && !tx_quote_is_expired($quote);
}

function tx_quote_is_demo(array $quote): bool
{
    $reference = (string)($quote['reference'] ?? '');
    return ($quote['is_demo'] ?? false) === true
        && (string)($quote['payment_provider'] ?? '') === 'demo'
        && str_starts_with($reference, 'DEMO-');
}

function tx_mark_demo_quote_paid(string $reference): ?array
{
    $file = tx_private_path('cotizaciones.json');
    $lockHandle = fopen($file . '.lock', 'c');
    if ($lockHandle === false) {
        throw new RuntimeException('No fue posible bloquear el archivo de cotizaciones.');
    }

    try {
        if (!flock($lockHandle, LOCK_EX)) {
            throw new RuntimeException('No fue posible bloquear el archivo de cotizaciones.');
        }

        $raw = is_file($file) ? file_get_contents($file) : false;
        $quotes = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($quotes)) {
            throw new RuntimeException('El archivo de cotizaciones no es válido.');
        }

        $updatedQuote = null;
        foreach ($quotes as $index => $quote) {
            if (!is_array($quote) || (string)($quote['reference'] ?? '') !== $reference) {
                continue;
            }
            if (!tx_quote_is_demo($quote)) {
                return null;
            }
            if ((string)($quote['status'] ?? '') === 'paid') {
                return $quote;
            }
            if (!tx_quote_is_payable($quote)) {
                return null;
            }

            $quotes[$index]['status'] = 'paid';
            $quotes[$index]['status_label'] = 'Pago de prueba confirmado';
            $quotes[$index]['provider_payment_id'] = 'DEMO-' . strtoupper(bin2hex(random_bytes(5)));
            $quotes[$index]['paid_at'] = date(DATE_ATOM);
            $updatedQuote = $quotes[$index];
            break;
        }

        if ($updatedQuote === null) {
            return null;
        }

        $encoded = json_encode($quotes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException('No fue posible serializar las cotizaciones.');
        }

        $permissionStat = is_file($file) ? @stat($file) : @stat(dirname($file));
        $temporary = $file . '.tmp';
        if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible actualizar la cotización de prueba.');
        }
        @chmod($temporary, 0640);
        if (is_array($permissionStat)) {
            @chown($temporary, (int)$permissionStat['uid']);
            @chgrp($temporary, (int)$permissionStat['gid']);
        }
        if (!rename($temporary, $file)) {
            throw new RuntimeException('No fue posible publicar el estado de la cotización.');
        }

        return $updatedQuote;
    } finally {
        @flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function tx_accept_quote(string $reference): ?array
{
    $file = tx_private_path('cotizaciones.json');
    $lockHandle = fopen($file . '.lock', 'c');
    if ($lockHandle === false) {
        throw new RuntimeException('No fue posible bloquear el archivo de cotizaciones.');
    }

    try {
        if (!flock($lockHandle, LOCK_EX)) {
            throw new RuntimeException('No fue posible bloquear el archivo de cotizaciones.');
        }

        $raw = is_file($file) ? file_get_contents($file) : false;
        $quotes = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($quotes)) {
            throw new RuntimeException('El archivo de cotizaciones no es válido.');
        }

        $acceptedQuote = null;
        foreach ($quotes as $index => $quote) {
            if (!is_array($quote) || (string)($quote['reference'] ?? '') !== $reference) {
                continue;
            }

            $status = (string)($quote['status'] ?? '');
            if (in_array($status, ['accepted', 'payment_pending', 'paid'], true)) {
                return $quote;
            }
            if ($status !== 'pending_acceptance' || tx_quote_is_expired($quote)) {
                return null;
            }

            $hasPaymentUrl = tx_valid_payment_url($quote) !== null;
            $quotes[$index]['status'] = $hasPaymentUrl ? 'payment_pending' : 'accepted';
            $quotes[$index]['status_label'] = $hasPaymentUrl ? 'Aceptada, pendiente de pago' : 'Aceptada por el cliente';
            $quotes[$index]['accepted_at'] = date(DATE_ATOM);
            $quotes[$index]['acceptance_ip_hash'] = hash('sha256', tx_client_ip());
            $quotes[$index]['acceptance_user_agent_hash'] = hash(
                'sha256',
                mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300, 'UTF-8')
            );
            $acceptedQuote = $quotes[$index];
            break;
        }

        if ($acceptedQuote === null) {
            return null;
        }

        $encoded = json_encode($quotes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException('No fue posible serializar las cotizaciones.');
        }

        $permissionStat = is_file($file) ? @stat($file) : @stat(dirname($file));
        $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible registrar la aceptación.');
        }
        @chmod($temporary, 0640);
        if (is_array($permissionStat)) {
            @chown($temporary, (int)$permissionStat['uid']);
            @chgrp($temporary, (int)$permissionStat['gid']);
        }
        if (!rename($temporary, $file)) {
            @unlink($temporary);
            throw new RuntimeException('No fue posible publicar la aceptación.');
        }

        $eventDirectory = tx_ensure_private_directory('quote-events');
        $event = [
            'event' => 'quote_accepted',
            'reference' => $reference,
            'occurred_at' => (string)$acceptedQuote['accepted_at'],
            'ip_hash' => (string)$acceptedQuote['acceptance_ip_hash'],
            'user_agent_hash' => (string)$acceptedQuote['acceptance_user_agent_hash'],
        ];
        file_put_contents(
            $eventDirectory . DIRECTORY_SEPARATOR . 'events.jsonl',
            json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        @chmod($eventDirectory . DIRECTORY_SEPARATOR . 'events.jsonl', 0640);

        return $acceptedQuote;
    } finally {
        @flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function tx_valid_payment_url(array $quote): ?string
{
    $url = trim((string)($quote['payment_url'] ?? ''));
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https') {
        return null;
    }
    $host = strtolower((string)($parts['host'] ?? ''));
    $allowedHosts = [
        'checkout.dlocalgo.com',
        'pay.dlocalgo.com',
        'secure.payco.co',
        'checkout.epayco.co',
        'checkout.wompi.co',
    ];
    foreach ($allowedHosts as $allowedHost) {
        if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
            return $url;
        }
    }
    return null;
}

function tx_money(float $value, string $currency = 'COP'): string
{
    $decimals = in_array(strtoupper($currency), ['CLP', 'COP', 'CRC', 'PYG'], true) ? 0 : 2;
    return '$' . number_format($value, $decimals, ',', '.') . ' ' . $currency;
}

function tx_mask_email(string $email): string
{
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) {
        return 'correo verificado';
    }
    $local = $parts[0];
    $visible = mb_substr($local, 0, min(2, mb_strlen($local, 'UTF-8')), 'UTF-8');
    return $visible . str_repeat('*', max(3, mb_strlen($local, 'UTF-8') - 2)) . '@' . $parts[1];
}
