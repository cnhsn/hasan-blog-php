<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $to, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $to);
        exit;
    }

    /** Permanent redirect for slug-history targets (docs/CONTENT_MODEL.md "Redirects"). */
    public static function permanentRedirect(string $to): never
    {
        self::redirect($to, 308);
    }

    public static function notFound(): void
    {
        http_response_code(404);
        View::render('public/404', [
            'settings' => (new \App\Models\SiteSetting())->get(),
            'pageTitle' => 'Page not found',
            'metaDescription' => 'This page does not exist.',
            'canonical' => base_url('/'),
        ]);
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function noStore(): void
    {
        header('Cache-Control: private, no-store, max-age=0, must-revalidate');
        header('Vary: Cookie');
    }

    /** Baseline security headers, applied to every response (see public/index.php). */
    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    }
}
