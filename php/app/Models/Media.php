<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Security\MediaValidator;
use PDO;
use RuntimeException;

final class Media
{
    private const SIZES = ['thumbnail' => 400, 'medium' => 800, 'large' => 1600];

    private function pdo(): PDO
    {
        return Database::connection();
    }

    /**
     * Deliberately outside public/ — the only way to reach a file is
     * through the guarded /media/{filename} route (see
     * Controllers/Public/MediaController), which checks `visibility`
     * (and the owner session for private assets) before streaming
     * bytes. Never assume the webserver's document root protects this;
     * it does not (docs/SECURITY.md: "do not assume CMS access rules
     * protect a public object URL" applies just as much to a local path).
     */
    public function uploadDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    /**
     * @param array $file A single $_FILES entry (already confirmed
     *     is_uploaded_file by the caller).
     */
    public function upload(array $file, string $alt, ?string $caption, string $visibility): int
    {
        $info = MediaValidator::validate($file['tmp_name'], (int) $file['size']);

        $baseFilename = bin2hex(random_bytes(12));
        $originalFilename = "{$baseFilename}.{$info['ext']}";
        $destination = $this->uploadDir() . '/' . $originalFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO media (filename, original_filename, mime_type, filesize, width, height, alt, caption, visibility)
                 VALUES (:filename, :original, :mime, :size, :width, :height, :alt, :caption, :visibility)',
            );
            $stmt->execute([
                'filename' => $originalFilename,
                'original' => basename((string) $file['name']),
                'mime' => $info['mime'],
                'size' => (int) $file['size'],
                'width' => $info['width'],
                'height' => $info['height'],
                'alt' => $alt,
                'caption' => $caption,
                'visibility' => $visibility,
            ]);
            $mediaId = (int) $pdo->lastInsertId();

            foreach (self::SIZES as $sizeName => $targetWidth) {
                $variant = MediaValidator::createVariant(
                    $destination,
                    $info['mime'],
                    $targetWidth,
                    $this->uploadDir(),
                    $baseFilename,
                    $sizeName,
                );
                if ($variant === null) {
                    continue;
                }
                $vStmt = $pdo->prepare(
                    'INSERT INTO media_variants (media_id, size_name, filename, width, height, filesize)
                     VALUES (:media_id, :size_name, :filename, :width, :height, :filesize)',
                );
                $vStmt->execute([
                    'media_id' => $mediaId,
                    'size_name' => $sizeName,
                    'filename' => $variant['filename'],
                    'width' => $variant['width'],
                    'height' => $variant['height'],
                    'filesize' => $variant['filesize'],
                ]);
            }

            $pdo->commit();
            return $mediaId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            @unlink($destination);
            throw $e;
        }
    }

    /**
     * Resolves a requested filename (original or a resized variant) to
     * its parent media record (for the visibility check) and disk path.
     * Used only by the guarded delivery route.
     *
     * @return array{visibility:string,mime_type:string,disk_path:string}|null
     */
    public function findByFilename(string $filename): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT visibility, mime_type, filename FROM media WHERE filename = :f');
        $stmt->execute(['f' => $filename]);
        $row = $stmt->fetch();
        if ($row !== false) {
            return [
                'visibility' => $row['visibility'],
                'mime_type' => $row['mime_type'],
                'disk_path' => $this->uploadDir() . '/' . $row['filename'],
            ];
        }

        $stmt = $this->pdo()->prepare(
            'SELECT m.visibility, m.mime_type, v.filename FROM media_variants v JOIN media m ON m.id = v.media_id WHERE v.filename = :f',
        );
        $stmt->execute(['f' => $filename]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'visibility' => $row['visibility'],
            'mime_type' => $row['mime_type'],
            'disk_path' => $this->uploadDir() . '/' . $row['filename'],
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM media WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $row['variants'] = $this->variantsFor($id);
        return $row;
    }

    public function findPublic(int $id): ?array
    {
        $row = $this->find($id);
        return ($row !== null && $row['visibility'] === 'public') ? $row : null;
    }

    private function variantsFor(int $mediaId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM media_variants WHERE media_id = :id');
        $stmt->execute(['id' => $mediaId]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['size_name']] = $row;
        }
        return $result;
    }

    public function all(): array
    {
        $rows = $this->pdo()->query('SELECT * FROM media ORDER BY created_at DESC')->fetchAll();
        foreach ($rows as &$row) {
            $row['variants'] = $this->variantsFor((int) $row['id']);
        }
        return $rows;
    }

    public function setVisibility(int $id, string $visibility): void
    {
        $this->pdo()->prepare('UPDATE media SET visibility = :v WHERE id = :id')->execute(['v' => $visibility, 'id' => $id]);
    }

    public function update(int $id, string $alt, ?string $caption): void
    {
        $this->pdo()->prepare('UPDATE media SET alt = :alt, caption = :caption WHERE id = :id')
            ->execute(['alt' => $alt, 'caption' => $caption, 'id' => $id]);
    }

    /** @return list<string> Titles of documents still referencing this asset, empty if safe to delete. */
    public function referencingDocuments(int $id): array
    {
        return array_merge((new Post())->isMediaReferenced($id), (new Project())->isMediaReferenced($id));
    }

    public function delete(int $id): void
    {
        $media = $this->find($id);
        if ($media === null) {
            return;
        }
        @unlink($this->uploadDir() . '/' . $media['filename']);
        foreach ($media['variants'] as $variant) {
            @unlink($this->uploadDir() . '/' . $variant['filename']);
        }
        $this->pdo()->prepare('DELETE FROM media WHERE id = :id')->execute(['id' => $id]);
    }

    /** Always goes through the guarded delivery route — never a direct static path. */
    public static function url(array $media, string $size = 'original'): string
    {
        $filename = ($size !== 'original' && isset($media['variants'][$size]))
            ? $media['variants'][$size]['filename']
            : $media['filename'];
        return base_url('media/' . rawurlencode($filename));
    }
}
