<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Basisklasse voor alle repositories: de enige plek waar SQL staat.
 * Alle queries gebruiken prepared statements met parameters.
 *
 * Prepared statement: de query (met :naam als plekhouder) en de invoer gaan los naar de database.
 * De database ziet invoer daardoor altijd als data en nooit als SQL-code (bescherming tegen SQL-injectie).
 */
abstract class Repository
{
    public function __construct(protected readonly PDO $db)
    {
    }

    /**
     * Eén rij ophalen, of null als er niets gevonden is.
     */
    protected function fetchOne(string $sql, array $parameters = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Alle rijen ophalen als lijst.
     */
    protected function fetchAll(string $sql, array $parameters = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * Eén waarde ophalen, bijv. het resultaat van COUNT(*).
     */
    protected function fetchValue(string $sql, array $parameters = []): mixed
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn();
    }

    /**
     * Voert een INSERT/UPDATE/DELETE uit en geeft het aantal geraakte rijen terug.
     */
    protected function execute(string $sql, array $parameters = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement->rowCount();
    }

    /**
     * Voert een INSERT uit en geeft het ID van de nieuwe rij terug.
     */
    protected function insert(string $sql, array $parameters = []): int
    {
        $this->execute($sql, $parameters);

        return (int) $this->db->lastInsertId();
    }
}
