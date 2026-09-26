<?php

declare(strict_types=1);

namespace App\Security;

final class ReadingTime
{
    private const WORDS_PER_MINUTE = 200;

    public static function forHtml(string $html): int
    {
        $text = Sanitizer::toPlainText($html);
        $words = $text === '' ? 0 : count(preg_split('/\s+/', $text));
        return max(1, (int) round($words / self::WORDS_PER_MINUTE));
    }
}
