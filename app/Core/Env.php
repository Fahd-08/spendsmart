<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Leest instellingen uit het .env-bestand, zodat wachtwoorden en
 * databasegegevens niet in de code (en niet in Git) staan.
 *
 * Voorbeeld van een regel in .env:  DB_NAME=spendsmart
 */
final class Env
{
    /**
     * Leest het .env-bestand regel voor regel en bewaart elke instelling in $_ENV.
     */
    public static function load(string $path): void
    {
        // Geen .env-bestand? Dan gebruiken we de standaardwaarden uit config/config.php.
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Lege regels, commentaar (#) en regels zonder '=' overslaan.
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            // 'DB_NAME=spendsmart' splitsen in sleutel 'DB_NAME' en waarde 'spendsmart'.
            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Een echte omgevingsvariabele gaat voor (zo gebruiken de tests een eigen database).
            if (self::get($key) !== null) {
                continue;
            }

            // Eventuele aanhalingstekens om de waarde weghalen.
            $_ENV[$key] = trim($value, "\"'");
        }
    }

    /**
     * Geeft de waarde van een instelling, of $default als die niet bestaat.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }
}
