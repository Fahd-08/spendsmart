<?php

declare(strict_types=1);

namespace App\Support;

/**
 * De rollen in SpendSmart.
 */
final class Role
{
    public const USER = 'user';
    public const CONTENT_MANAGER = 'content_manager';

    public static function label(string $role): string
    {
        return match ($role) {
            self::USER => 'Gebruiker',
            self::CONTENT_MANAGER => 'Contentbeheerder',
            default => 'Onbekend',
        };
    }

    public static function homePath(string $role): string
    {
        return match ($role) {
            self::USER => '/dashboard',
            self::CONTENT_MANAGER => '/content/statistics',
            default => '/',
        };
    }
}
