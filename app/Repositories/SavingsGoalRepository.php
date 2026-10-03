<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Money;

final class SavingsGoalRepository extends Repository
{
    public function allForUser(int $userId, ?int $limit = null): array
    {
        $sql = 'SELECT id, name, target_cents, saved_cents, target_date
                FROM savings_goals WHERE user_id = :user_id
                ORDER BY (saved_cents >= target_cents), target_date IS NULL, target_date, name';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        return $this->fetchAll($sql, ['user_id' => $userId]);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, target_cents, saved_cents, target_date
             FROM savings_goals WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * @param array{name: string, target_cents: int, saved_cents: int, target_date: ?string} $data
     */
    public function create(int $userId, array $data): int
    {
        return $this->insert(
            'INSERT INTO savings_goals (user_id, name, target_cents, saved_cents, target_date)
             VALUES (:user_id, :name, :target_cents, :saved_cents, :target_date)',
            ['user_id' => $userId] + $this->columns($data)
        );
    }

    public function update(int $id, int $userId, array $data): void
    {
        $this->execute(
            'UPDATE savings_goals
             SET name = :name, target_cents = :target_cents, saved_cents = :saved_cents, target_date = :target_date
             WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId] + $this->columns($data)
        );
    }

    /**
     * Telt een bedrag op bij het gespaarde bedrag, in één query (geen race condition).
     * Geeft false als het maximum zou worden overschreden.
     */
    public function addToSaved(int $id, int $userId, int $amountCents): bool
    {
        return $this->execute(
            'UPDATE savings_goals SET saved_cents = saved_cents + :amount
             WHERE id = :id AND user_id = :user_id AND saved_cents + :amount_check <= :max',
            [
                'amount' => $amountCents,
                'amount_check' => $amountCents,
                'max' => Money::MAX_CENTS,
                'id' => $id,
                'user_id' => $userId,
            ]
        ) === 1;
    }

    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM savings_goals WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    private function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'target_cents' => $data['target_cents'],
            'saved_cents' => $data['saved_cents'],
            'target_date' => $data['target_date'],
        ];
    }
}
