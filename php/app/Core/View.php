<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $viewsPath = '';

    public static function init(string $viewsPath): void
    {
        self::$viewsPath = rtrim($viewsPath, '/');
    }

    /**
     * Renders a template (and optionally wraps it in a layout) and
     * prints the result. Templates are plain PHP; use e() for output
     * escaping — nothing here auto-escapes for you.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/public'): void
    {
        $content = self::capture($template, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        echo self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /** @param array<string,mixed> $data */
    private static function capture(string $template, array $data): string
    {
        $path = self::$viewsPath . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
