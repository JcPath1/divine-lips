<?php
// Glimmer Bliss - shared setup loaded by every API and admin page.
// This folder (glimmerbliss_app) sits OUTSIDE public_html on the server,
// so nobody can open these files in a browser.

declare(strict_types=1);

date_default_timezone_set('UTC');

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    $configFile = __DIR__ . '/config.local.php';
}
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Shop is not set up yet (missing config).');
}
$GLOBALS['GB_CONFIG'] = require $configFile;

require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/stock.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/emails.php';
require_once __DIR__ . '/paypal.php';
require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/reviews.php';

function cfg(string $path, $default = null)
{
    $value = $GLOBALS['GB_CONFIG'];
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('gbsess');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
    session_start();
}

function money(int $cents): string
{
    return '$' . number_format($cents / 100, 2);
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

// ---------- JSON API helpers ----------

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): never
{
    json_out(['ok' => false, 'error' => $message], $status);
}

// Only accept JSON bodies. Browsers will not send application/json to
// another site without asking first, which blocks forged requests.
function json_in(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_error('Use POST.', 405);
    }
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') !== 0) {
        json_error('Expected JSON.', 415);
    }
    $raw = file_get_contents('php://input', false, null, 0, 65536);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        json_error('Bad request.');
    }
    return $data;
}

function clean_text($value, int $max): string
{
    $s = trim(preg_replace('/\s+/u', ' ', (string)$value) ?? '');
    return mb_substr($s, 0, $max);
}

function clean_email($value): ?string
{
    $s = strtolower(trim((string)$value));
    if (strlen($s) > 190 || !filter_var($s, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $s;
}

// Counts recent tries so nobody can hammer login or order forms.
function too_many(string $kind, string $key, int $max, int $seconds): bool
{
    $since = gmdate('Y-m-d H:i:s', time() - $seconds);
    $st = db()->prepare('SELECT COUNT(*) FROM attempts WHERE kind = ? AND akey = ? AND created_at > ?');
    $st->execute([$kind, $key, $since]);
    return (int)$st->fetchColumn() >= $max;
}

function note_attempt(string $kind, string $key): void
{
    db()->prepare('INSERT INTO attempts (kind, akey, created_at) VALUES (?, ?, ?)')
        ->execute([$kind, $key, now()]);
}
