<?php

declare(strict_types=1);

/**
 * Dependency-free PSR-4-ish autoloader. No Composer at deploy time —
 * shared hosting compatibility means "upload files and it works",
 * with no build/install step and no shell access assumed.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
