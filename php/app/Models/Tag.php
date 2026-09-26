<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Tag
{
    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM tags WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->pdo()->prepare('INSERT INTO tags (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int $id, string $name, string $slug): void
    {
        $stmt = $this->pdo()->prepare('UPDATE tags SET name = :name, slug = :slug WHERE id = :id');
        $stmt->execute(['name' => $name, 'slug' => $slug, 'id' => $id]);
    }

    /** Clears the reference from posts safely — never cascade-deletes posts. */
    public function delete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tags WHERE name = :name' . ($excludeId !== null ? ' AND id != :id' : '');
        $stmt = $this->pdo()->prepare($sql);
        $params = ['name' => $name];
        if ($excludeId !== null) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tags WHERE slug = :slug' . ($excludeId !== null ? ' AND id != :id' : '');
        $stmt = $this->pdo()->prepare($sql);
        $params = ['slug' => $slug];
        if ($excludeId !== null) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
