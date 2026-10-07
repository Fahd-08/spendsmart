<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Rendert PHP-templates uit app/Views binnen een layout.
 * De controller geeft data mee; de template maakt er HTML van.
 */
final class View
{
    /** Data die in alle templates beschikbaar is (bijv. de huidige pagina voor het menu). */
    private static array $shared = [];

    /**
     * Zet een waarde klaar voor alle templates.
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Haalt een gedeelde waarde op.
     */
    public static function shared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    /**
     * Rendert eerst de pagina zelf en zet die daarna in de layout (header, menu, footer).
     */
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): string
    {
        $content = self::renderFile($template, $data);

        // De layout krijgt dezelfde data plus de HTML van de pagina in $content.
        return self::renderFile($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Rendert een klein, herbruikbaar stukje HTML uit app/Views/partials (bijv. een melding).
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::renderFile('partials/' . $template, $data);
    }

    /**
     * Voert een template uit en geeft de HTML terug als tekst.
     */
    private static function renderFile(string $viewName, array $viewData): string
    {
        $viewPath = BASE_PATH . '/app/Views/' . $viewName . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException("View '{$viewName}' bestaat niet.");
        }

        // ['title' => 'Dashboard'] wordt de variabele $title in de template.
        extract($viewData, EXTR_SKIP);

        // Output opvangen in plaats van direct naar de browser sturen.
        ob_start();
        require $viewPath;

        return (string) ob_get_clean();
    }
}
