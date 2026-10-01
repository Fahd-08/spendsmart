<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Een inkomend HTTP-verzoek. Geeft invoer altijd terug als string (of standaardwaarde),
 * zodat arrays of andere onverwachte invoer geen fouten veroorzaken.
 */
final class Request
{
    public function __construct(
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));

        $basePath = self::basePath();
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        return '/' . trim($path, '/');
    }

    /**
     * Invoer uit het formulier (POST).
     */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

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

    public function body(): array
    {
        return $this->body;
    }

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
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $directory = rtrim(dirname($scriptName), '/');

        return $directory === '.' ? '' : $directory;
    }
}
