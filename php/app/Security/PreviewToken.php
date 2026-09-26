<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Config;

/**
 * Short-lived signed token proving "the owner clicked Preview on this
 * exact document a few minutes ago" — not a standalone bearer of
 * access. The /preview route requires BOTH a valid, unexpired token AND
 * an active owner session, so a leaked token alone cannot become an
 * indefinite public draft link (docs/SECURITY.md).
 */
final class PreviewToken
{
    private const TTL_SECONDS = 300;

    public static function create(string $collection, string $slug): string
    {
        $exp = time() + self::TTL_SECONDS;
        $payload = "{$collection}:{$slug}:{$exp}";
        $signature = self::sign($payload);
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $signature;
    }

    public static function verify(string $token, string $collection, string $slug): bool
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$payloadEncoded, $signature] = $parts;

        $payload = base64_decode(strtr($payloadEncoded, '-_', '+/'), true);
        if ($payload === false) {
            return false;
        }

        $expectedSignature = self::sign($payload);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $bits = explode(':', $payload);
        if (count($bits) !== 3) {
            return false;
        }
        [$tokenCollection, $tokenSlug, $expString] = $bits;
        $exp = (int) $expString;

        if (time() > $exp) {
            return false;
        }

        return $tokenCollection === $collection && $tokenSlug === $slug;
    }

    private static function sign(string $payload): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, Config::secretKey(), true)), '+/', '-_'), '=');
    }
}
