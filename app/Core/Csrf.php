<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bescherming tegen Cross-Site Request Forgery: elk formulier bevat een geheim token
 * dat bij het verzenden wordt vergeleken met het token in de sessie.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function verify(string $token): bool
    {
        return $token !== '' && hash_equals(self::token(), $token);
    }
}
