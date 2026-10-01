<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Toegangscontrole per route:
 *  - auth           alleen voor ingelogde gebruikers
 *  - guest          alleen voor bezoekers die niet zijn ingelogd
 *  - role:<rol>     alleen voor accounts met deze rol
 */
final class Middleware
{
    public static function run(string $name): void
    {
        [$type, $argument] = array_pad(explode(':', $name, 2), 2, '');

        match ($type) {
            'auth' => self::requireLogin(),
            'guest' => self::requireGuest(),
            'role' => self::requireRole($argument),
            default => throw new InvalidArgumentException("Onbekende middleware: {$name}"),
        };
    }

    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::add('info', 'Log in om deze pagina te bekijken.');
            Response::redirect(url('/login'));
        }
    }

    private static function requireGuest(): void
    {
        if (Auth::check()) {
            Response::redirect(url(Auth::homePath()));
        }
    }

    private static function requireRole(string $role): void
    {
        if (!Auth::hasRole($role)) {
            throw new HttpException(403, 'Je hebt met jouw rol geen toegang tot deze pagina of actie.');
        }
    }
}
