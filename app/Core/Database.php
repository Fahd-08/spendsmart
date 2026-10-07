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
    /** De verbinding wordt één keer gemaakt en daarna hergebruikt. */
    private static ?PDO $connection = null;

    /**
     * Geeft de databaseverbinding (en maakt die bij de eerste keer aan).
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            // DSN = adres van de database, met utf8mb4 voor alle tekens (ook € en emoji).
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                config('db.host'),
                config('db.port'),
                config('db.name'),
            );

            self::$connection = new PDO($dsn, (string) config('db.user'), (string) config('db.password'), [
                // Bij een databasefout een exception gooien (in plaats van stil doorgaan).
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Rijen teruggeven als array met kolomnamen: $row['name'].
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Echte prepared statements: query en invoer gaan apart naar de database (tegen SQL-injectie).
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }
}
