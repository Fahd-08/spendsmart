<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Eigen categorieën van een gebruiker. Elke query filtert op user_id,
 * zodat niemand categorieën van een ander kan zien of wijzigen.
 */
final class CategoryRepository extends Repository
{
    public function allForUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT c.id, c.name, c.type, c.monthly_budget_cents, c.suggestion_id,
                    (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id) AS transaction_count
             FROM categories c
             WHERE c.user_id = :user_id
             ORDER BY c.type DESC, c.name',
            ['user_id' => $userId]
        );
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, type, monthly_budget_cents, suggestion_id
             FROM categories WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function nameExists(int $userId, string $name, string $type, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM categories
             WHERE user_id = :user_id AND name = :name AND type = :type AND id <> :except_id',
            ['user_id' => $userId, 'name' => $name, 'type' => $type, 'except_id' => $exceptId ?? 0]
        );
    }

    public function hasTransactions(int $id, int $userId): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM transactions WHERE category_id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * @param array{name: string, type: string, monthly_budget_cents: ?int, suggestion_id?: ?int} $data
     */
    public function create(int $userId, array $data): int
    {
        return $this->insert(
            'INSERT INTO categories (user_id, suggestion_id, name, type, monthly_budget_cents)
             VALUES (:user_id, :suggestion_id, :name, :type, :monthly_budget_cents)',
            [
                'user_id' => $userId,
                'suggestion_id' => $data['suggestion_id'] ?? null,
                'name' => $data['name'],
                'type' => $data['type'],
                'monthly_budget_cents' => $data['monthly_budget_cents'],
            ]
        );
    }

    public function update(int $id, int $userId, array $data): void
    {
        $this->execute(
            'UPDATE categories SET name = :name, type = :type, monthly_budget_cents = :monthly_budget_cents
             WHERE id = :id AND user_id = :user_id',
            [
                'name' => $data['name'],
                'type' => $data['type'],
                'monthly_budget_cents' => $data['monthly_budget_cents'],
                'id' => $id,
                'user_id' => $userId,
            ]
        );
    }

    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM categories WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * @return int[] ID's van voorstellen die de gebruiker al heeft overgenomen
     */
    public function adoptedSuggestionIds(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT suggestion_id FROM categories WHERE user_id = :user_id AND suggestion_id IS NOT NULL',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): int => (int) $row['suggestion_id'], $rows);
    }
}
