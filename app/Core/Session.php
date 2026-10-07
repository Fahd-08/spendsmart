<?php

declare(strict_types=1);

namespace App\Core;

/**
 * De sessie onthoudt gegevens tussen pagina's, zoals wie er is ingelogd.
 * De browser krijgt alleen een cookie met een willekeurig sessie-ID; de gegevens zelf staan op de server.
 */
final class Session
{
    /**
     * Start de sessie met veilige cookie-instellingen.
     */
    public static function start(): void
    {
        // Al gestart? Dan niets doen.
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Draait de site via HTTPS (slotje)? Dan mag de cookie alleen via HTTPS worden verstuurd.
        $isHttps = ($_SERVER['HTTPS'] ?? 'off') !== 'off';

        // Alleen sessie-ID's accepteren die de server zelf heeft gemaakt, en alleen via een cookie (niet in de URL).
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('spendsmart_session');

        session_set_cookie_params([
            'lifetime' => 0,          // cookie verdwijnt als de browser sluit
            'path' => '/',
            'secure' => $isHttps,     // alleen via HTTPS
            'httponly' => true,       // JavaScript kan de cookie niet lezen (tegen diefstal via XSS)
            'samesite' => 'Lax',      // andere sites kunnen geen formulieren met jouw cookie versturen
        ]);

        session_start();
    }

    /**
     * Haalt een waarde uit de sessie, of $default als die er niet is.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Bewaart een waarde in de sessie.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Nieuw sessie-ID na inloggen/uitloggen tegen session fixation
     * (een aanvaller kan dan geen sessie-ID "klaarzetten" en daarna overnemen).
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Alles uit de sessie wissen (bij uitloggen) en een nieuw sessie-ID maken.
     */
    public static function clear(): void
    {
        $_SESSION = [];
        self::regenerate();
    }
}
