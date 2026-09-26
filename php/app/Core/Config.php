<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Config
{
    private static ?array $data = null;

    public static function isInstalled(): bool
    {
        return is_file(self::configPath());
    }

    public static function configPath(): string
    {
        return dirname(__DIR__) . '/config.php';
    }

    /** @return array<string,mixed> */
    private static function load(): array
    {
        if (self::$data === null) {
            if (!self::isInstalled()) {
                throw new RuntimeException('The application is not installed yet. Visit /install.php.');
            }
            /** @var array<string,mixed> $config */
            $config = require self::configPath();
            self::$data = $config;
        }

        return self::$data;
    }

    /** @return mixed */
    public static function get(string $key, mixed $default = null): mixed
    {
        $data = self::load();
        return $data[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return self::get('app')['env'] === 'production';
    }

    public static function baseUrl(): string
    {
        return rtrim(self::get('app')['base_url'], '/');
    }

    public static function secretKey(): string
    {
        return self::get('app')['secret_key'];
    }

    /** For tests only: inject config without a real config.php file. */
    public static function setForTesting(array $data): void
    {
        self::$data = $data;
    }
}
