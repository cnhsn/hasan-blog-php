<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = Config::get('db');

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['name'],
                $config['charset'],
            );

            try {
                self::$connection = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
            }
        }

        return self::$connection;
    }

    /**
     * Connects without selecting a database — used by the installer to
     * test credentials and create the database before it exists.
     */
    public static function connectWithoutDatabase(string $host, int $port, string $user, string $pass): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /** Injects an already-open connection (installer, before config.php exists). */
    public static function setConnection(PDO $pdo): void
    {
        self::$connection = $pdo;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
