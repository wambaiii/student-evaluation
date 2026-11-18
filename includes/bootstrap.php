<?php
declare(strict_types=1);

/**
 * Loaded at the top of every page: settings, security headers, session and helpers.
 */
require __DIR__ . '/env.php';
load_env(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/config/database.php';

const APP_NAME = 'Banana University';
const MAX_LOGIN_FAILURES = 5;     // per email or IP ...
const LOGIN_WINDOW_MINUTES = 15;  // ... within this many minutes

if (env('APP_DEBUG') !== '1') {
    ini_set('display_errors', '0');   // never show stack traces to visitors
}

/* ---------- HTTPS awareness (Nginx terminates SSL and proxies to Apache) ---------- */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Set by the Nginx reverse proxy: proxy_set_header X-Forwarded-Proto $scheme;
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/* ---------- Security headers ---------- */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
header(
    "Content-Security-Policy: default-src 'self'; " .
    "script-src 'self' https://cdnjs.cloudflare.com; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src https://fonts.gstatic.com; " .
    "img-src 'self' data: blob: https://picsum.photos https://fastly.picsum.photos; " .
    "connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
);
if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

/* ---------- Session ---------- */
session_name('bu_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,            // JavaScript cannot read the cookie
    'secure'   => is_https(),
    'samesite' => 'Lax',
]);
session_start();

/* ---------- URL helpers (work in a sub-folder like XAMPP/htdocs too) ---------- */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $public  = realpath(__DIR__ . '/../public') ?: '';
    $base = '';
    if ($docroot !== '' && str_starts_with($public, $docroot)) {
        $base = str_replace('\\', '/', substr($public, strlen($docroot)));
    }
    return rtrim($base, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Output escaping ---------- */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- CSRF protection ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent  = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    // Both must be non-empty: hash_equals('', '') is true, which would let a forged form through.
    return is_string($sent) && $sent !== '' && $known !== '' && hash_equals($known, $sent);
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Authentication helpers ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php?next=' . urlencode($next));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('403 - Administrators only.');
    }
    return $user;
}

/** Only allow redirects back to a path on THIS site (blocks open-redirect attacks). */
function safe_next(?string $next): string
{
    if ($next === null || $next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '\\')) {
        return '';
    }
    $base = base_path();
    if ($base !== '' && str_starts_with($next, $base . '/')) {
        $next = substr($next, strlen($base));
    }
    return ltrim($next, '/');
}

function client_ip(): string
{
    // Behind Nginx the real client address arrives in X-Real-IP (set in the proxy config).
    $ip = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Decode a webcam snapshot (data URL), verify it really is an image, and re-encode it
 * with GD (which strips any hidden payload). Returns the stored path or null.
 */
function save_avatar(string $dataUrl): ?string
{
    if (!preg_match('#^data:image/(jpeg|png);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
        return null;
    }
    $raw = base64_decode($m[2], true);
    if ($raw === false || strlen($raw) > 2_000_000) {
        return null;
    }
    $info = @getimagesizefromstring($raw);
    if ($info === false || $info[0] > 1600 || $info[1] > 1600) {
        return null;
    }
    $img = @imagecreatefromstring($raw);
    if ($img === false) {
        return null;
    }
    $dir = dirname(__DIR__) . '/public/uploads/avatars';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.jpg';
    $ok = imagejpeg($img, $dir . '/' . $name, 85);
    imagedestroy($img);
    return $ok ? 'uploads/avatars/' . $name : null;
}
