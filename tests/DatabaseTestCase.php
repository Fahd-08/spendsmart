<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

/**
 * Basis voor tests die de database gebruiken. Voor elke test wordt de demodata opnieuw geladen,
 * zodat tests elkaar niet beïnvloeden.
 */
abstract class DatabaseTestCase extends TestCase
{
    // ID's uit database/seed.sql
    protected const CONTENT_MANAGER_ID = 1;
    protected const SAM_ID = 2;
    protected const SANNE_ID = 3;
    protected const PASSWORD = 'Welkom123!';

    // Categorieën van Sam
    protected const SAM_BIJBAAN = 1;
    protected const SAM_BOODSCHAPPEN = 3;
    protected const SAM_VERVOER = 4;
    protected const SAM_CADEAUS = 7;

    // Categorie van Sanne
    protected const SANNE_HUUR = 10;

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::reset();
    }

    protected function db(): PDO
    {
        return Database::connection();
    }

    /**
     * Eén waarde uit de database, bijvoorbeeld een COUNT(*).
     */
    protected function value(string $sql, array $parameters = []): mixed
    {
        $statement = $this->db()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn();
    }

    protected function row(string $sql, array $parameters = []): ?array
    {
        $statement = $this->db()->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function countRows(string $table, string $where = '1', array $parameters = []): int
    {
        return (int) $this->value("SELECT COUNT(*) FROM {$table} WHERE {$where}", $parameters);
    }
}
