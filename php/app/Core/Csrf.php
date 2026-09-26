<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(string $submitted): bool
    {
        $expected = Session::get(self::SESSION_KEY);
        if (!is_string($expected) || $submitted === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    public static function verifyRequest(Request $request): bool
    {
        return self::verify((string) $request->post('csrf_token', ''));
    }
}
