<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Role;
use Throwable;

final class UserRepository extends Repository
{
    /**
     * Gebruiker zonder wachtwoordhash (veilig om aan views door te geven).
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, email, role, created_at FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Inclusief wachtwoordhash, alleen voor inloggen.
     */
    public function findByEmailWithPassword(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, email, role, password_hash FROM users WHERE email = :email',
            ['email' => $email]
        );
    }

    public function passwordHash(int $id): ?string
    {
        $hash = $this->fetchValue('SELECT password_hash FROM users WHERE id = :id', ['id' => $id]);

        return is_string($hash) ? $hash : null;
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM users WHERE email = :email AND id <> :except_id',
            ['email' => $email, 'except_id' => $exceptUserId ?? 0]
        );
    }

    public function create(string $name, string $email, string $passwordHash, string $role = Role::USER): int
    {
        return $this->insert(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)',
            ['name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'role' => $role]
        );
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $this->execute(
            'UPDATE users SET name = :name, email = :email WHERE id = :id',
            ['name' => $name, 'email' => $email, 'id' => $id]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            ['password_hash' => $passwordHash, 'id' => $id]
        );
    }

    /**
     * Verwijdert het account met alle persoonlijke gegevens.
     * Transacties eerst, omdat categorieën met transacties beschermd zijn (ON DELETE RESTRICT).
     */
    public function delete(int $id): void
    {
        $this->db->beginTransaction();

        try {
            $this->execute('DELETE FROM transactions WHERE user_id = :id', ['id' => $id]);
            $this->execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
