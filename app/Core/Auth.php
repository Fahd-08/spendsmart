<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;
use App\Support\Role;

/**
 * Houdt bij welke gebruiker is ingelogd en welke rol die heeft.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    private static ?array $user = null;

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        self::$user = null;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::clear();
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        if (self::$user === null) {
            self::$user = (new UserRepository(Database::connection()))->findById($id);

            // Account bestaat niet meer: sessie opruimen.
            if (self::$user === null) {
                self::logout();
            }
        }

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string $role): bool
    {
        return (self::user()['role'] ?? null) === $role;
    }

    /**
     * Startpagina na het inloggen, afhankelijk van de rol.
     */
    public static function homePath(): string
    {
        return Role::homePath(self::user()['role'] ?? '');
    }
}
