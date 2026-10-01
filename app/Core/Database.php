<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Eén gedeelde PDO-verbinding. Echte prepared statements (geen emulatie)
 * beschermen tegen SQL-injectie.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                config('db.host'),
                config('db.port'),
                config('db.name'),
            );

            self::$connection = new PDO($dsn, (string) config('db.user'), (string) config('db.password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }
}
