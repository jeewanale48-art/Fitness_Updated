<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/schema.php';
fittrackEnsureSchema($pdo);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sent) || !is_string($saved) || !hash_equals($saved, $sent)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function takeFlash(): ?array
{
    $value = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($value) ? $value : null;
}

/*
|--------------------------------------------------------------------------
| Compatibility helpers for application pages
|--------------------------------------------------------------------------
*/

if (!function_exists('escapeHtml')) {
    function escapeHtml($value): string
    {
        return e($value);
    }
}

if (!function_exists('requireValidCsrf')) {
    function requireValidCsrf(): void
    {
        verifyCsrf();
    }
}

/*
|--------------------------------------------------------------------------
| Brand: ONE logo used on every page (public, auth, member and admin)
|--------------------------------------------------------------------------
*/

/** Logo + name link. $base is the path back to the project root ('' or '../'). */
function fittrackBrand(string $href, string $base = '', string $class = 'brand'): string
{
    return '<a class="' . e($class) . ' ft-logo" href="' . e($href) . '">'
        . '<img class="ft-logo-img" src="' . e($base) . 'assets/images/fittrack-logo.svg" alt="" width="36" height="36">'
        . '<span class="ft-logo-text">FitTrack</span></a>';
}

/** Favicon tag so the browser tab shows the same logo. */
function fittrackFavicon(string $base = ''): string
{
    return '<link rel="icon" type="image/svg+xml" href="' . e($base) . 'assets/images/fittrack-logo.svg">';
}
