<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Media;

/**
 * Guarded media delivery route. Files live outside the public webroot
 * (see Models/Media::uploadDir) specifically so that this check cannot
 * be bypassed by requesting the file directly — the visibility flag is
 * enforced here, every time, not just at upload time
 * (docs/SECURITY.md "do not assume CMS access rules protect a public
 * object URL").
 */
final class MediaController
{
    public function deliver(Request $request, array $params): void
    {
        $filename = basename($params['filename']); // defeats path traversal

        $media = (new Media())->findByFilename($filename);
        if ($media === null || !is_file($media['disk_path'])) {
            Response::notFound();
            return;
        }

        if ($media['visibility'] === 'private' && !Session::isOwner()) {
            Response::forbidden('This asset is private.');
        }

        // V1 delivery is always no-store: a visibility reversal (public
        // -> private) cannot otherwise guarantee already-cached copies
        // are purged (docs/SECURITY.md, explicitly allowed as the V1
        // choice when cache revocation cannot be guaranteed).
        header('Cache-Control: private, no-store');
        header('Content-Type: ' . $media['mime_type']);
        header('Content-Length: ' . filesize($media['disk_path']));
        header('X-Content-Type-Options: nosniff');
        readfile($media['disk_path']);
        exit;
    }
}
