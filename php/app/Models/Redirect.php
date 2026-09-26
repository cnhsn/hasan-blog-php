<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Redirect
{
    private const MAX_FLATTEN_HOPS = 10;

    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM redirects ORDER BY updated_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM redirects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findBySource(string $sourcePath): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM redirects WHERE source_path = :source');
        $stmt->execute(['source' => $sourcePath]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $source, string $target): int
    {
        $stmt = $this->pdo()->prepare('INSERT INTO redirects (source_path, target_path) VALUES (:s, :t)');
        $stmt->execute(['s' => $source, 't' => $target]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, string $source, string $target): void
    {
        $stmt = $this->pdo()->prepare('UPDATE redirects SET source_path = :s, target_path = :t WHERE id = :id');
        $stmt->execute(['s' => $source, 't' => $target, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM redirects WHERE id = :id')->execute(['id' => $id]);
    }

    public function sourceExists(string $source, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM redirects WHERE source_path = :s' . ($excludeId !== null ? ' AND id != :id' : '');
        $stmt = $this->pdo()->prepare($sql);
        $params = ['s' => $source];
        if ($excludeId !== null) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Automatic slug-history bookkeeping, called after a published
     * post/project rename (docs/CONTENT_MODEL.md "Redirects"): flattens
     * any chain so nothing points at another redirect's source, and
     * flattens incoming redirects that targeted the old path.
     */
    public function recordRename(string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }

        $finalTarget = $newPath;
        for ($hop = 0; $hop < self::MAX_FLATTEN_HOPS; $hop++) {
            $next = $this->findBySource($finalTarget);
            if ($next === null || $next['target_path'] === $finalTarget) {
                break;
            }
            $finalTarget = $next['target_path'];
        }

        if ($finalTarget === $oldPath) {
            return; // would be a self-loop
        }

        $stmt = $this->pdo()->prepare('UPDATE redirects SET target_path = :target WHERE target_path = :old AND source_path != :old2');
        $stmt->execute(['target' => $finalTarget, 'old' => $oldPath, 'old2' => $finalTarget]);

        $existing = $this->findBySource($oldPath);
        if ($existing !== null) {
            $this->update((int) $existing['id'], $oldPath, $finalTarget);
        } else {
            $this->create($oldPath, $finalTarget);
        }
    }

    /** Removes redirects that would now point at an unpublished path. */
    public function deactivateTargeting(string $path): void
    {
        $this->pdo()->prepare('DELETE FROM redirects WHERE target_path = :path')->execute(['path' => $path]);
    }
}
