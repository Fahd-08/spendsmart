<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Eigen categorieën van een gebruiker. Elke query filtert op user_id,
 * zodat niemand categorieën van een ander kan zien of wijzigen.
 */
final class CategoryRepository extends Repository
{
    /**
     * Alle categorieën van een gebruiker, met per categorie het aantal transacties.
     * Volgorde: eerst uitgaven, dan inkomsten (type DESC), daarna op naam.
     */
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

    /**
     * Eén categorie, maar alleen als die van deze gebruiker is (anders null -> 404).
     */
    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, type, monthly_budget_cents, suggestion_id
             FROM categories WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * Heeft de gebruiker al een categorie met deze naam en soort?
     * Bij wijzigen telt de categorie zelf niet mee ($exceptId).
     */
    public function nameExists(int $userId, string $name, string $type, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM categories
             WHERE user_id = :user_id AND name = :name AND type = :type AND id <> :except_id',
            ['user_id' => $userId, 'name' => $name, 'type' => $type, 'except_id' => $exceptId ?? 0]
        );
    }

    /**
     * Staan er transacties in deze categorie? Dan mag hij niet weg en niet van soort wisselen.
     */
    public function hasTransactions(int $id, int $userId): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM transactions WHERE category_id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * Nieuwe categorie opslaan. suggestion_id is gevuld als de categorie van een voorstel komt.
     *
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

    /**
     * Categorie wijzigen. "AND user_id" zorgt dat je alleen je eigen categorie kunt wijzigen.
     */
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

    /**
     * Categorie verwijderen (alleen je eigen).
     */
    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM categories WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Welke voorstellen heeft de gebruiker al overgenomen? Die worden niet meer als voorstel getoond.
     *
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
