<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = ($_SERVER['HTTPS'] ?? 'off') !== 'off';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('spendsmart_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Nieuw sessie-ID na inloggen/uitloggen tegen session fixation.
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function clear(): void
    {
        $_SESSION = [];
        self::regenerate();
    }
}
