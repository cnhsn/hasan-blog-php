<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Project
{
    public const PAGE_SIZE = 9;

    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function listPublished(int $page): array
    {
        $page = max(1, $page);
        $offset = self::PAGE_SIZE * ($page - 1);

        $total = (int) $this->pdo()->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn();

        $stmt = $this->pdo()->prepare(
            "SELECT * FROM projects WHERE status = 'published' ORDER BY start_date DESC LIMIT :limit OFFSET :offset",
        );
        $stmt->bindValue('limit', self::PAGE_SIZE, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['technologies'] = json_decode((string) ($row['technologies_json'] ?? '[]'), true) ?: [];
        }

        return [
            'docs' => $rows,
            'total' => $total,
            'totalPages' => (int) max(1, ceil($total / self::PAGE_SIZE)),
            'page' => $page,
        ];
    }

    public function findBySlug(string $slug, bool $asOwner = false): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM projects WHERE slug = :slug');
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
        $stmt = $this->pdo()->prepare('SELECT * FROM projects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->withRelations($this->applyPendingDraft($row));
    }

    private function applyPendingDraft(array $row): array
    {
        if (!empty($row['pending_draft_json'])) {
            $pending = json_decode((string) $row['pending_draft_json'], true) ?: [];
            $row = array_merge($row, $pending);
            $row['status'] = 'draft';
        }
        return $row;
    }

    private function withRelations(array $row): array
    {
        $row['technologies'] = json_decode((string) ($row['technologies_json'] ?? '[]'), true) ?: [];
        $stmt = $this->pdo()->prepare(
            'SELECT g.*, m.filename, m.alt as media_alt, m.width as media_width, m.height as media_height, m.visibility
             FROM project_gallery g JOIN media m ON m.id = g.media_id
             WHERE g.project_id = :id ORDER BY g.sort_order',
        );
        $stmt->execute(['id' => $row['id']]);
        $row['gallery'] = $stmt->fetchAll();
        return $row;
    }

    /** @param list<int> $galleryMediaIds ordered; captions keyed by media id */
    public function save(?int $id, array $input, array $galleryMediaIds, array $galleryCaptions, string $intendedStatus): int
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
                    'gallery_media_ids' => $galleryMediaIds,
                    'gallery_captions' => $galleryCaptions,
                ]);
                $stmt = $pdo->prepare('UPDATE projects SET pending_draft_json = :data, pending_draft_updated_at = NOW() WHERE id = :id');
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
                $sql = 'INSERT INTO projects (' . implode(',', $fields) . ') VALUES (' . implode(',', array_map(static fn ($f) => ":{$f}", $fields)) . ')';
                $pdo->prepare($sql)->execute($columns);
                $id = (int) $pdo->lastInsertId();
            } else {
                $sets = implode(', ', array_map(static fn ($f) => "{$f} = :{$f}", array_keys($columns)));
                $columns['id'] = $id;
                $pdo->prepare("UPDATE projects SET {$sets} WHERE id = :id")->execute($columns);
            }

            $this->syncGallery($id, $galleryMediaIds, $galleryCaptions);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** The genuine live row, with no pending_draft_json overlay applied. See Post::findRaw(). */
    public function findRaw(int $id): ?array
    {
        return $this->rawFind($id);
    }

    private function rawFind(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM projects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function snapshotVersion(array $row): void
    {
        $full = $this->withRelations($row);
        $stmt = $this->pdo()->prepare('INSERT INTO project_versions (project_id, data_json, status) VALUES (:id, :data, :status)');
        $stmt->execute([
            'id' => $row['id'],
            'data' => json_encode($full, JSON_UNESCAPED_UNICODE),
            'status' => $row['status'],
        ]);
    }

    private function syncGallery(int $projectId, array $mediaIds, array $captions): void
    {
        $this->pdo()->prepare('DELETE FROM project_gallery WHERE project_id = :id')->execute(['id' => $projectId]);
        $stmt = $this->pdo()->prepare(
            'INSERT INTO project_gallery (project_id, media_id, caption, sort_order) VALUES (:project_id, :media_id, :caption, :sort_order)',
        );
        foreach (array_values($mediaIds) as $order => $mediaId) {
            $stmt->execute([
                'project_id' => $projectId,
                'media_id' => $mediaId,
                'caption' => $captions[$mediaId] ?? null,
                'sort_order' => $order,
            ]);
        }
    }

    public function versions(int $projectId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM project_versions WHERE project_id = :id ORDER BY created_at DESC');
        $stmt->execute(['id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function restoreVersion(int $projectId, int $versionId): void
    {
        $stmt = $this->pdo()->prepare('SELECT data_json FROM project_versions WHERE id = :id AND project_id = :project_id');
        $stmt->execute(['id' => $versionId, 'project_id' => $projectId]);
        $json = $stmt->fetchColumn();
        if ($json === false) {
            throw new \RuntimeException('Version not found.');
        }

        $data = json_decode((string) $json, true);
        unset($data['id'], $data['status'], $data['published_at'], $data['created_at'], $data['updated_at']);
        $data['gallery_media_ids'] = array_column($data['gallery'] ?? [], 'media_id');
        $captions = [];
        foreach ($data['gallery'] ?? [] as $item) {
            $captions[$item['media_id']] = $item['caption'] ?? null;
        }
        $data['gallery_captions'] = $captions;
        unset($data['gallery'], $data['technologies']);

        $update = $this->pdo()->prepare('UPDATE projects SET pending_draft_json = :data, pending_draft_updated_at = NOW() WHERE id = :id');
        $update->execute(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'id' => $projectId]);
    }

    public function delete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM projects WHERE id = :id')->execute(['id' => $id]);
    }

    /** Explicit unpublish: flips the LIVE row to draft immediately. See Post::unpublish(). */
    public function unpublish(int $id): void
    {
        $this->pdo()->prepare("UPDATE projects SET status = 'draft' WHERE id = :id")->execute(['id' => $id]);
    }

    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();
    }

    public function isMediaReferenced(int $mediaId): array
    {
        // Real (non-emulated) prepared statements do not allow reusing
        // the same named placeholder twice in one query.
        $stmt = $this->pdo()->prepare(
            'SELECT title FROM projects WHERE cover_media_id = :id1 OR seo_share_media_id = :id2
             UNION SELECT p.title FROM projects p JOIN project_gallery g ON g.project_id = p.id WHERE g.media_id = :id3',
        );
        $stmt->execute(['id1' => $mediaId, 'id2' => $mediaId, 'id3' => $mediaId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
