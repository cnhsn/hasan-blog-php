<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Security\PreviewToken;

/**
 * Draft preview entry point. Requires BOTH an authenticated owner
 * session AND a valid, unexpired signed token for this exact document —
 * a leaked token alone is not an indefinite public draft link. The
 * redirect target is derived from a fixed allowlist, never from
 * caller-supplied paths (docs/SECURITY.md "Draft isolation").
 */
final class PreviewController
{
    private const DESTINATIONS = ['posts' => 'blog', 'projects' => 'projects'];

    public function enter(Request $request, array $params): void
    {
        $collection = (string) $request->query('collection', '');
        $slug = (string) $request->query('slug', '');
        $token = (string) $request->query('token', '');

        if (!array_key_exists($collection, self::DESTINATIONS) || $slug === '' || $token === '') {
            Response::forbidden('Missing or invalid preview parameters.');
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            Response::forbidden('Invalid preview slug.');
        }

        if (!Session::isOwner()) {
            http_response_code(401);
            echo 'Preview requires an authenticated owner session.';
            exit;
        }

        if (!PreviewToken::verify($token, $collection, $slug)) {
            http_response_code(401);
            echo 'Invalid or expired preview token.';
            exit;
        }

        Session::set('preview_mode', true);
        Response::redirect(base_url(self::DESTINATIONS[$collection] . '/' . $slug), 307);
    }

    public function exit(Request $request, array $params): void
    {
        Session::remove('preview_mode');

        $to = (string) $request->query('to', '/');
        $isInternal = str_starts_with($to, '/') && !str_starts_with($to, '//') && !str_contains($to, '://');

        Response::redirect(base_url(ltrim($isInternal ? $to : '/', '/')), 307);
    }
}
