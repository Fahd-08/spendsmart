<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Toegangscontrole per route. Wordt door de Router uitgevoerd vóórdat de controller start.
 *  - auth           alleen voor ingelogde gebruikers
 *  - guest          alleen voor bezoekers die niet zijn ingelogd
 *  - role:<rol>     alleen voor accounts met deze rol
 */
final class Middleware
{
    /**
     * Voert één middleware uit, bijv. 'auth' of 'role:user'.
     */
    public static function run(string $name): void
    {
        // 'role:user' splitsen in type 'role' en argument 'user'. Bij 'auth' is het argument leeg.
        [$type, $argument] = array_pad(explode(':', $name, 2), 2, '');

        match ($type) {
            'auth' => self::requireLogin(),
            'guest' => self::requireGuest(),
            'role' => self::requireRole($argument),
            default => throw new InvalidArgumentException("Onbekende middleware: {$name}"),
        };
    }

    /**
     * Niet ingelogd? Dan naar de inlogpagina met een melding.
     */
    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::add('info', 'Log in om deze pagina te bekijken.');
            Response::redirect(url('/login'));
        }
    }

    /**
     * Al ingelogd? Dan heeft de inlog- of registratiepagina geen zin: naar je eigen startpagina.
     */
    private static function requireGuest(): void
    {
        if (Auth::check()) {
            Response::redirect(url(Auth::homePath()));
        }
    }

    /**
     * Verkeerde rol? Dan een 403-foutpagina (geen toegang).
     * Zo kan een gebruiker niet bij de beheerpagina's en een contentbeheerder niet bij persoonlijke gegevens.
     */
    private static function requireRole(string $role): void
    {
        if (!Auth::hasRole($role)) {
            throw new HttpException(403, 'Je hebt met jouw rol geen toegang tot deze pagina of actie.');
        }
    }
}
