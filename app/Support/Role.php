<?php

declare(strict_types=1);

namespace App\Support;

/**
 * De rollen in SpendSmart. Constanten voorkomen typfouten: Role::USER in plaats van 'user'.
 */
final class Role
{
    /** Student die eigen inkomsten, uitgaven en spaardoelen bijhoudt. */
    public const USER = 'user';

    /** Medewerker van MoneyMinds die leerteksten, voorstellen en statistieken beheert. */
    public const CONTENT_MANAGER = 'content_manager';

    /**
     * Naam van de rol zoals de gebruiker hem ziet (rechtsboven in het menu).
     */
    public static function label(string $role): string
    {
        return match ($role) {
            self::USER => 'Gebruiker',
            self::CONTENT_MANAGER => 'Contentbeheerder',
            default => 'Onbekend',
        };
    }

    /**
     * Startpagina na het inloggen, per rol.
     */
    public static function homePath(string $role): string
    {
        return match ($role) {
            self::USER => '/dashboard',
            self::CONTENT_MANAGER => '/content/statistics',
            default => '/',
        };
    }
}
