<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bescherming tegen Cross-Site Request Forgery: elk formulier bevat een geheim token
 * dat bij het verzenden wordt vergeleken met het token in de sessie.
 *
 * Zonder dit zou een andere website een verborgen formulier naar SpendSmart kunnen sturen
 * (bijv. "verwijder mijn account") terwijl jij ingelogd bent. Die site kent het token niet.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Geeft het token van deze sessie. Bestaat het nog niet, dan wordt een willekeurig token gemaakt.
     * Wordt via csrf_field() als verborgen veld in elk formulier gezet.
     */
    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            // 32 willekeurige bytes = 64 tekens, niet te raden.
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Klopt het meegestuurde token met het token in de sessie?
     * hash_equals vergelijkt in vaste tijd, zodat de vergelijking zelf niets verraadt.
     */
    public static function verify(string $token): bool
    {
        return $token !== '' && hash_equals(self::token(), $token);
    }
}
