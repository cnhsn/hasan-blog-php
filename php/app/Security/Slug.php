<?php

declare(strict_types=1);

namespace App\Security;

final class Slug
{
    private const RESERVED = [
        'admin', 'install', 'assets', 'preview', 'blog', 'projects',
        'sitemap.xml', 'robots.txt', 'rss.xml', 'health',
    ];

    /**
     * Latin letters that Unicode normalization does not decompose into
     * an ASCII base letter plus a combining mark. Turkish dotless "ı" is
     * the one that matters most for this project's own content —
     * without this, "Başlık" slugifies to "basl-k".
     */
    private const TRANSLITERATIONS = [
        'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'Ş' => 's',
        'ğ' => 'g', 'Ğ' => 'g', 'ç' => 'c', 'Ç' => 'c',
        'ö' => 'o', 'Ö' => 'o', 'ü' => 'u', 'Ü' => 'u',
        'ß' => 'ss', 'æ' => 'ae', 'Æ' => 'ae', 'œ' => 'oe', 'Œ' => 'oe',
        'ø' => 'o', 'Ø' => 'o', 'đ' => 'd', 'Đ' => 'd', 'ł' => 'l', 'Ł' => 'l',
    ];

    public static function slugify(string $input): string
    {
        $input = strtr($input, self::TRANSLITERATIONS);

        if (function_exists('transliterator_transliterate')) {
            $transliterated = \transliterator_transliterate('Any-Latin; Latin-ASCII', $input);
            if ($transliterated !== false) {
                $input = $transliterated;
            }
        } else {
            // intl extension unavailable (rare on shared hosting): fall
            // back to iconv, which still normalizes most Latin diacritics.
            $iconv = @iconv('UTF-8', 'ASCII//TRANSLIT', $input);
            if ($iconv !== false) {
                $input = $iconv;
            }
        }

        $slug = strtolower(trim($input));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return mb_substr($slug, 0, 120);
    }

    /** @return true|string true if valid, else an error message */
    public static function validate(?string $value): bool|string
    {
        if ($value === null || $value === '') {
            return 'Slug is required.';
        }
        if (mb_strlen($value) > 120) {
            return 'Slug must be 120 characters or fewer.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            return 'Slug must be lowercase ASCII letters, numbers and single hyphens only.';
        }
        if (in_array($value, self::RESERVED, true)) {
            return "\"{$value}\" is a reserved path and cannot be used as a slug.";
        }
        return true;
    }

    private const RESERVED_TOP_LEVEL = ['admin', 'install', 'assets', 'preview'];

    /** Validates a redirect source/target path (docs/CONTENT_MODEL.md "Redirects"). */
    public static function validateInternalPath(?string $value): bool|string
    {
        if ($value === null || $value === '') {
            return 'Path is required.';
        }
        if (!str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return 'Path must be an internal absolute path starting with a single "/".';
        }
        if (str_contains($value, '://') || stripos($value, 'javascript:') !== false) {
            return 'Path must not reference an external URL or script scheme.';
        }
        $segments = explode('/', $value);
        $first = $segments[1] ?? '';
        if (in_array($first, self::RESERVED_TOP_LEVEL, true)) {
            return "\"/{$first}\" is a reserved path and cannot be used here.";
        }
        return true;
    }
}
