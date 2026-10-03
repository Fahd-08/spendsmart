<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Beheert de testdatabase. Elke test begint met dezelfde demodata uit database/seed.sql.
 */
final class TestDatabase
{
    private const TABLES = [
        'login_attempts',
        'transactions',
        'savings_goals',
        'categories',
        'category_suggestions',
        'tips',
        'users',
    ];

    public static function prepare(): void
    {
        $name = (string) config('db.name');

        // Veiligheid: nooit de echte database leegmaken.
        if (!str_ends_with($name, '_test')) {
            throw new RuntimeException("De tests mogen alleen een database gebruiken die eindigt op '_test' (nu: '{$name}').");
        }

        $server = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', config('db.host'), config('db.port')),
            (string) config('db.user'),
            (string) config('db.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        self::runFile(BASE_PATH . '/database/schema.sql');
    }

    /**
     * Alle tabellen leegmaken en de demodata opnieuw laden.
     */
    public static function reset(): void
    {
        $db = Database::connection();

        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::TABLES as $table) {
            $db->exec("TRUNCATE TABLE {$table}");
        }
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');

        self::runFile(BASE_PATH . '/database/seed.sql');
    }

    private static function runFile(string $path): void
    {
        $db = Database::connection();
        $sql = (string) file_get_contents($path);

        // Commentaarregels weghalen en per statement uitvoeren.
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

        foreach (preg_split('/;\s*$/m', $sql) ?: [] as $statement) {
            if (trim($statement) !== '') {
                $db->exec($statement);
            }
        }
    }
}
