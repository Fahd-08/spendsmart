<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Een inkomend HTTP-verzoek. Geeft invoer altijd terug als string (of standaardwaarde),
 * zodat arrays of andere onverwachte invoer geen fouten veroorzaken.
 */
final class Request
{
    /**
     * @param array $query  waarden uit de URL (?month=2026-09), oftewel $_GET
     * @param array $body   waarden uit een formulier, oftewel $_POST
     * @param array $server gegevens over het verzoek (URL, methode, IP-adres), oftewel $_SERVER
     */
    public function __construct(
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
    ) {
    }

    /**
     * Maakt een Request van de echte gegevens die PHP van de browser kreeg.
     */
    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    /**
     * 'GET' (pagina opvragen) of 'POST' (formulier versturen).
     */
    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * Het pad van de URL zonder map en zonder ?query, bijv. '/transactions'.
     */
    public function path(): string
    {
        // '/Examen%20portfolio/public/transactions?month=2026-09' -> '/Examen portfolio/public/transactions'
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));

        // De map waarin de app staat eraf halen (op XAMPP '/Examen portfolio/public').
        $basePath = self::basePath();
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        // Altijd met één / beginnen en zonder / aan het eind: '/transactions'.
        return '/' . trim($path, '/');
    }

    /**
     * Invoer uit het formulier (POST).
     */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

        // Alleen tekst accepteren; een array (bijv. name="email[]") wordt genegeerd.
        return is_string($value) ? $value : $default;
    }

    /**
     * Invoer uit de URL (GET), bijvoorbeeld filters.
     */
    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Alle formulierinvoer in één keer (voor de Validator).
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * IP-adres van de bezoeker (gebruikt voor de blokkade na te veel inlogpogingen).
     */
    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * De map waarin de app draait, bijvoorbeeld "/Examen portfolio/public" op XAMPP
     * of "" als public/ de document root is (Plesk).
     */
    public static function basePath(): string
    {
        // SCRIPT_NAME is bijv. '/Examen portfolio/public/index.php'; de map daarvan is de basis.
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $directory = rtrim(dirname($scriptName), '/');

        return $directory === '.' ? '' : $directory;
    }
}
