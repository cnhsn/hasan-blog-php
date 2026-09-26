<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function requireOwner(): void
    {
        if (!Session::isOwner()) {
            Response::redirect(base_url('admin/login'));
        }
    }

    /**
     * CSRF/cookie-auth double-check for state-changing admin requests:
     * the session cookie alone is not enough — the request must also
     * carry a valid CSRF token, so a cross-site request cannot ride the
     * owner's cookie (docs/SECURITY.md "CORS/CSRF allow only configured
     * same-origin production/local URLs").
     */
    public static function requireOwnerAndCsrf(Request $request): void
    {
        self::requireOwner();
        if (!Csrf::verifyRequest($request)) {
            Response::forbidden('Invalid or missing CSRF token.');
        }
    }
}
