<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Rendert PHP-templates uit app/Views binnen een layout.
 */
final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): string
    {
        $content = self::renderFile($template, $data);

        return self::renderFile($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::renderFile('partials/' . $template, $data);
    }

    private static function renderFile(string $viewName, array $viewData): string
    {
        $viewPath = BASE_PATH . '/app/Views/' . $viewName . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException("View '{$viewName}' bestaat niet.");
        }

        extract($viewData, EXTR_SKIP);
        ob_start();
        require $viewPath;

        return (string) ob_get_clean();
    }
}
