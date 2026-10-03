<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Leest instellingen uit het .env-bestand, zodat wachtwoorden en
 * databasegegevens niet in de code (en niet in Git) staan.
 */
final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Een echte omgevingsvariabele gaat voor (zo gebruiken de tests een eigen database).
            if (self::get($key) !== null) {
                continue;
            }

            $_ENV[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }
}
