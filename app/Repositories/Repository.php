<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Basisklasse voor alle repositories: de enige plek waar SQL staat.
 * Alle queries gebruiken prepared statements met parameters.
 */
abstract class Repository
{
    public function __construct(protected readonly PDO $db)
    {
    }

    protected function fetchOne(string $sql, array $parameters = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function fetchAll(string $sql, array $parameters = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

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

    protected function insert(string $sql, array $parameters = []): int
    {
        $this->execute($sql, $parameters);

        return (int) $this->db->lastInsertId();
    }
}
