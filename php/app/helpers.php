<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;

/**
 * Global helper functions for views (deliberately not namespaced, so
 * plain-PHP view templates can call them without a `use` statement).
 */

/** HTML-escapes a value for safe output. Use on every dynamic value in a view. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    return Config::baseUrl() . '/' . ltrim($path, '/');
}

/** Cache-busted static asset URL. */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return base_url($path) . '?v=' . $version;
}

function csrf_field(): string
{
    return Csrf::field();
}

/** Re-populates a form field from the previous submission (see FlashOld in Controllers). */
function old(string $key, string $default = ''): string
{
    static $data = null;
    if ($data === null) {
        $data = App\Core\Session::get('old_input', []);
        App\Core\Session::remove('old_input');
    }
    return (string) ($data[$key] ?? $default);
}

function flash_get(string $key): ?string
{
    $value = App\Core\Session::get('flash_' . $key);
    App\Core\Session::remove('flash_' . $key);
    return $value;
}

/** Renders app/Views/public/_partial.php with $data extracted, returning the HTML as a string. */
function render_partial(string $partial, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/app/Views/public/' . $partial . '.php';
    return (string) ob_get_clean();
}
