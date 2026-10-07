<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;
use App\Support\Role;

/**
 * Houdt bij welke gebruiker is ingelogd en welke rol die heeft.
 * In de sessie staat alleen het ID van de gebruiker; de rest wordt uit de database gehaald.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    /**
     * Gegevens van de ingelogde gebruiker, één keer per verzoek opgehaald (zodat we niet steeds de database vragen).
     */
    private static ?array $user = null;

    /**
     * Logt een gebruiker in: nieuw sessie-ID en het gebruikers-ID in de sessie zetten.
     */
    public static function login(array $user): void
    {
        Session::regenerate(); // tegen session fixation
        Session::set(self::SESSION_KEY, (int) $user['id']);
        self::$user = null;
    }

    /**
     * Logt uit: alles uit de sessie wissen.
     */
    public static function logout(): void
    {
        self::$user = null;
        Session::clear();
    }

    /**
     * ID van de ingelogde gebruiker, of null als niemand is ingelogd.
     */
    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    /**
     * Gegevens van de ingelogde gebruiker (id, naam, e-mail, rol), of null.
     */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        // Nog niet opgehaald in dit verzoek? Dan nu uit de database halen.
        if (self::$user === null) {
            self::$user = (new UserRepository(Database::connection()))->findById($id);

            // Account bestaat niet meer (bijv. verwijderd): sessie opruimen.
            if (self::$user === null) {
                self::logout();
            }
        }

        return self::$user;
    }

    /**
     * Is er iemand ingelogd?
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Heeft de ingelogde gebruiker deze rol ('user' of 'content_manager')?
     */
    public static function hasRole(string $role): bool
    {
        return (self::user()['role'] ?? null) === $role;
    }

    /**
     * Startpagina na het inloggen, afhankelijk van de rol.
     * Gebruiker -> /dashboard, contentbeheerder -> /content/statistics.
     */
    public static function homePath(): string
    {
        return Role::homePath(self::user()['role'] ?? '');
    }
}
