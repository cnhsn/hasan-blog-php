<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Applies committed .sql files from migrations/ exactly once, tracked in
 * schema_migrations. Safe to re-run — already-applied files are skipped.
 */
final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $migrationsDir)
    {
    }

    /** @return list<string> Versions applied during this call. */
    public function migrate(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (' .
            'version VARCHAR(50) PRIMARY KEY, ' .
            'applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        );

        $applied = $this->appliedVersions();
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files);

        $newlyApplied = [];

        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException("Could not read migration file: {$file}");
            }

            // No explicit transaction here: MySQL/MariaDB DDL statements
            // (CREATE TABLE etc.) cause an implicit commit, so wrapping
            // this in beginTransaction()/commit() does not make it
            // atomic anyway — it only makes commit() fail with "no
            // active transaction" once the first DDL statement runs.
            try {
                foreach ($this->splitStatements($sql) as $statement) {
                    if (trim($statement) === '') {
                        continue;
                    }
                    $this->pdo->exec($statement);
                }

                $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
                $stmt->execute(['version' => $version]);
                $newlyApplied[] = $version;
            } catch (\Throwable $e) {
                throw new \RuntimeException("Migration {$version} failed: " . $e->getMessage(), 0, $e);
            }
        }

        return $newlyApplied;
    }

    /** @return list<string> */
    private function appliedVersions(): array
    {
        $stmt = $this->pdo->query('SELECT version FROM schema_migrations');
        return $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * Splits a .sql file into individual statements on semicolons that
     * end a line, which is sufficient for this project's own migration
     * files (no stored procedures/triggers with embedded semicolons).
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $lines = explode("\n", $sql);
        $statements = [];
        $buffer = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with($trimmed, ';')) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
