<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hulpmiddelen voor het antwoord dat naar de browser gaat.
 */
final class Response
{
    /**
     * Stuurt door naar een andere pagina. De RedirectException wordt in public/index.php afgehandeld.
     * Return type 'never': na deze aanroep gaat de code niet verder.
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
        // Browser mag niet raden wat voor bestand iets is (voorkomt dat tekst als script wordt uitgevoerd).
        header('X-Content-Type-Options: nosniff');

        // De site mag niet in een iframe van een andere site worden getoond (tegen clickjacking).
        header('X-Frame-Options: DENY');

        // Bij links naar andere sites wordt niet doorgegeven van welke pagina je kwam.
        header('Referrer-Policy: same-origin');

        // Content-Security-Policy: scripts, styles en afbeeldingen mogen alleen van onze eigen site komen.
        // Ook als iemand toch HTML weet in te voegen, voert de browser geen vreemde scripts uit (extra bescherming tegen XSS).
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}
