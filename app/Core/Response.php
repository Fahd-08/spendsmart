<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * Stuurt door naar een andere pagina. De RedirectException wordt in public/index.php afgehandeld.
     */
    public static function redirect(string $url): never
    {
        throw new RedirectException($url);
    }

    /**
     * Beveiligingsheaders die bij elke pagina worden meegestuurd.
     */
    public static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}
