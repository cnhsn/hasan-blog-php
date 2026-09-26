<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Editing a published post and saving as "draft" stages the change in
 * pending_draft_json instead of touching the live columns, so visitors
 * keep seeing the last published revision until the owner explicitly
 * republishes (docs/PROJECT_SPEC.md flow 5; docs/ACCEPTANCE.md A07).
 *
 * Restoring a version (see restoreVersion()) always writes into
 * pending_draft_json too, regardless of current status — it never
 * silently replaces the live published row (docs/CONTENT_MODEL.md,
 * docs/ACCEPTANCE.md A28).
 */
final class Post
{
    public const PAGE_SIZE = 9;

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /** Public listing: published only, newest first, optional category filter. */
    public function listPublished(int $page, ?string $categorySlug = null): array
    {
        $page = max(1, $page);
        $offset = (self::PAGE_SIZE) * ($page - 1);

        $where = "p.status = 'published'";
        $params = [];
        if ($categorySlug !== null) {
            $where .= ' AND EXISTS (SELECT 1 FROM post_categories pc JOIN categories c ON c.id = pc.category_id WHERE pc.post_id = p.id AND c.slug = :slug)';
            $params['slug'] = $categorySlug;
        }

        $countStmt = $this->pdo()->prepare("SELECT COUNT(*) FROM posts p WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo()->prepare(
            "SELECT p.* FROM posts p WHERE {$where} ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset",
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', self::PAGE_SIZE, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['categories'] = $this->categoriesFor((int) $row['id']);
            $row['tags'] = $this->tagsFor((int) $row['id']);
        }

        return [
            'docs' => $rows,
            'total' => $total,
            'totalPages' => (int) max(1, ceil($total / self::PAGE_SIZE)),
            'page' => $page,
        ];
    }

    /**
     * Public detail read. `$asOwner` includes the current draft/pending
     * state; a plain anonymous call only ever sees the published row.
     */
    public function findBySlug(string $slug, bool $asOwner = false): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM posts WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        if (!$asOwner) {
            return $row['status'] === 'published' ? $this->withRelations($row) : null;
        }

        return $this->withRelations($this->applyPendingDraft($row));
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->withRelations($this->applyPendingDraft($row));
    }

    private function applyPendingDraft(array $row): array
    {
        if (!empty($row['pending_draft_json'])) {
            $pending = json_decode((string) $row['pending_draft_json'], true) ?: [];
            $row = array_merge($row, $pending);
            $row['status'] = 'draft'; // the editable state is a draft, by definition
        }
        return $row;
    }

    private function withRelations(array $row): array
    {
        $row['categories'] = $this->categoriesFor((int) $row['id']);
        $row['tags'] = $this->tagsFor((int) $row['id']);
        return $row;
    }

    private function categoriesFor(int $postId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT c.* FROM categories c JOIN post_categories pc ON pc.category_id = c.id WHERE pc.post_id = :id ORDER BY c.name',
        );
        $stmt->execute(['id' => $postId]);
        return $stmt->fetchAll();
    }

    private function tagsFor(int $postId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = :id ORDER BY t.name',
        );
        $stmt->execute(['id' => $postId]);
        return $stmt->fetchAll();
    }

    /** Related posts sharing a category or tag, excluding the current post. */
    public function related(int $postId, array $categoryIds, array $tagIds, int $limit = 3): array
    {
        if ($categoryIds === [] && $tagIds === []) {
            return [];
        }

        $conditions = [];
        $params = ['id' => $postId];
        if ($categoryIds !== []) {
            $placeholders = [];
            foreach ($categoryIds as $i => $catId) {
                $key = "cat{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $catId;
            }
            $conditions[] = 'p.id IN (SELECT post_id FROM post_categories WHERE category_id IN (' . implode(',', $placeholders) . '))';
        }
        if ($tagIds !== []) {
            $placeholders = [];
            foreach ($tagIds as $i => $tagId) {
                $key = "tag{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $tagId;
            }
            $conditions[] = 'p.id IN (SELECT post_id FROM post_tags WHERE tag_id IN (' . implode(',', $placeholders) . '))';
        }

        $sql = 'SELECT p.* FROM posts p WHERE p.status = \'published\' AND p.id != :id AND (' . implode(' OR ', $conditions) . ') ORDER BY p.published_at DESC LIMIT ' . (int) $limit;
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row = $this->withRelations($row);
        }
        return $rows;
    }

    /**
     * @param array<string,mixed> $input Live-column fields, already validated/sanitized.
     * @param list<int> $categoryIds
     * @param list<int> $tagIds
     */
    public function save(?int $id, array $input, array $categoryIds, array $tagIds, string $intendedStatus): int
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $existing = $id !== null ? $this->rawFind($id) : null;

            if ($existing !== null) {
                $this->snapshotVersion($existing);
            }

            $stageAsPending = $existing !== null && $existing['status'] === 'published' && $intendedStatus === 'draft';

            if ($stageAsPending) {
                $pending = array_merge($input, [
                    'category_ids' => $categoryIds,
                    'tag_ids' => $tagIds,
                ]);
                $stmt = $pdo->prepare(
                    'UPDATE posts SET pending_draft_json = :data, pending_draft_updated_at = NOW() WHERE id = :id',
                );
                $stmt->execute(['data' => json_encode($pending, JSON_UNESCAPED_UNICODE), 'id' => $id]);
                $pdo->commit();
                return $id;
            }

            $publishedAt = $existing['published_at'] ?? null;
            if ($intendedStatus === 'published' && $publishedAt === null) {
                $publishedAt = date('Y-m-d H:i:s');
            }

            $columns = array_merge($input, [
                'status' => $intendedStatus,
                'published_at' => $publishedAt,
                'pending_draft_json' => null,
                'pending_draft_updated_at' => null,
            ]);

            if ($id === null) {
                $columns['created_at'] = date('Y-m-d H:i:s');
                $fields = array_keys($columns);
                $sql = 'INSERT INTO posts (' . implode(',', $fields) . ') VALUES (' . implode(',', array_map(static fn ($f) => ":{$f}", $fields)) . ')';
                $pdo->prepare($sql)->execute($columns);
                $id = (int) $pdo->lastInsertId();
            } else {
                $sets = implode(', ', array_map(static fn ($f) => "{$f} = :{$f}", array_keys($columns)));
                $columns['id'] = $id;
                $pdo->prepare("UPDATE posts SET {$sets} WHERE id = :id")->execute($columns);
            }

            $this->syncCategories($id, $categoryIds);
            $this->syncTags($id, $tagIds);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * The genuine live row, with no pending_draft_json overlay applied.
     * Callers deciding "was this document live/published before this
     * save" (slug-history redirects, unpublish bookkeeping) must use
     * this, not find() — find() relabels status as 'draft' whenever a
     * pending overlay exists, which is correct for the editor's display
     * but wrong for "what do visitors currently see".
     */
    public function findRaw(int $id): ?array
    {
        return $this->rawFind($id);
    }

    private function rawFind(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function snapshotVersion(array $row): void
    {
        $row['categories'] = $this->categoriesFor((int) $row['id']);
        $row['tags'] = $this->tagsFor((int) $row['id']);
        $stmt = $this->pdo()->prepare(
            'INSERT INTO post_versions (post_id, data_json, status) VALUES (:post_id, :data, :status)',
        );
        $stmt->execute([
            'post_id' => $row['id'],
            'data' => json_encode($row, JSON_UNESCAPED_UNICODE),
            'status' => $row['status'],
        ]);
    }

    private function syncCategories(int $postId, array $categoryIds): void
    {
        $this->pdo()->prepare('DELETE FROM post_categories WHERE post_id = :id')->execute(['id' => $postId]);
        $stmt = $this->pdo()->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (:post_id, :category_id)');
        foreach (array_unique($categoryIds) as $categoryId) {
            $stmt->execute(['post_id' => $postId, 'category_id' => $categoryId]);
        }
    }

    private function syncTags(int $postId, array $tagIds): void
    {
        $this->pdo()->prepare('DELETE FROM post_tags WHERE post_id = :id')->execute(['id' => $postId]);
        $stmt = $this->pdo()->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)');
        foreach (array_unique($tagIds) as $tagId) {
            $stmt->execute(['post_id' => $postId, 'tag_id' => $tagId]);
        }
    }

    public function versions(int $postId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM post_versions WHERE post_id = :id ORDER BY created_at DESC');
        $stmt->execute(['id' => $postId]);
        return $stmt->fetchAll();
    }

    /** Restores a version into pending_draft_json — never touches the live published row. */
    public function restoreVersion(int $postId, int $versionId): void
    {
        $stmt = $this->pdo()->prepare('SELECT data_json FROM post_versions WHERE id = :id AND post_id = :post_id');
        $stmt->execute(['id' => $versionId, 'post_id' => $postId]);
        $json = $stmt->fetchColumn();
        if ($json === false) {
            throw new \RuntimeException('Version not found.');
        }

        $data = json_decode((string) $json, true);
        unset($data['id'], $data['status'], $data['published_at'], $data['created_at'], $data['updated_at']);
        $data['category_ids'] = array_column($data['categories'] ?? [], 'id');
        $data['tag_ids'] = array_column($data['tags'] ?? [], 'id');
        unset($data['categories'], $data['tags']);

        $update = $this->pdo()->prepare(
            'UPDATE posts SET pending_draft_json = :data, pending_draft_updated_at = NOW() WHERE id = :id',
        );
        $update->execute(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'id' => $postId]);
    }

    public function delete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM posts WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Explicit unpublish: flips the LIVE row to draft immediately,
     * distinct from "save edits as draft" (which stages into
     * pending_draft_json and leaves the live published row alone).
     * Public detail/listing/RSS/sitemap all read the live `status`
     * column, so this takes the post down right away
     * (docs/ACCEPTANCE.md A09).
     */
    public function unpublish(int $id): void
    {
        $this->pdo()->prepare("UPDATE posts SET status = 'draft' WHERE id = :id")->execute(['id' => $id]);
    }

    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM posts ORDER BY created_at DESC')->fetchAll();
    }

    public function isMediaReferenced(int $mediaId): array
    {
        // Real (non-emulated) prepared statements do not allow reusing
        // the same named placeholder twice in one query — each
        // occurrence needs its own bound parameter.
        $stmt = $this->pdo()->prepare(
            'SELECT title FROM posts WHERE cover_media_id = :id1 OR seo_share_media_id = :id2',
        );
        $stmt->execute(['id1' => $mediaId, 'id2' => $mediaId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
