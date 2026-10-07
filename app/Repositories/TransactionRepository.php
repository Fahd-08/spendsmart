<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Month;

/**
 * Inkomsten en uitgaven. Alle queries filteren op user_id.
 * Totalen worden in de database opgeteld als hele centen.
 */
final class TransactionRepository extends Repository
{
    /**
     * Basisquery: transactie met de naam en soort van de categorie.
     * "c.user_id = t.user_id" is een extra controle dat categorie en transactie van dezelfde gebruiker zijn.
     */
    private const SELECT_WITH_CATEGORY =
        'SELECT t.id, t.amount_cents, t.transaction_date, t.description,
                c.id AS category_id, c.name AS category_name, c.type
         FROM transactions t
         JOIN categories c ON c.id = t.category_id AND c.user_id = t.user_id';

    /**
     * Transacties van één maand, eventueel gefilterd op categorie en/of soort (FE-05).
     * Nieuwste eerst.
     *
     * @param string|null $type 'income', 'expense' of null voor alles
     */
    public function filterForUser(int $userId, Month $month, ?int $categoryId = null, ?string $type = null): array
    {
        // Basis: van deze gebruiker en binnen de maand (begin t/m dag vóór de volgende maand).
        $sql = self::SELECT_WITH_CATEGORY . '
            WHERE t.user_id = :user_id
              AND t.transaction_date >= :start AND t.transaction_date < :end';
        $parameters = ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()];

        // Alleen als er op categorie gefilterd wordt, die voorwaarde toevoegen.
        if ($categoryId !== null) {
            $sql .= ' AND t.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }

        // Alleen als er op soort gefilterd wordt (inkomsten of uitgaven).
        if ($type !== null) {
            $sql .= ' AND c.type = :type';
            $parameters['type'] = $type;
        }

        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC';

        return $this->fetchAll($sql, $parameters);
    }

    /**
     * De laatste transacties van een maand (voor het dashboard).
     */
    public function recentForUser(int $userId, Month $month, int $limit = 5): array
    {
        return $this->fetchAll(
            self::SELECT_WITH_CATEGORY . '
             WHERE t.user_id = :user_id AND t.transaction_date >= :start AND t.transaction_date < :end
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT ' . max(1, $limit), // $limit is een int uit onze eigen code, geen gebruikersinvoer
            ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()]
        );
    }

    /**
     * Eén transactie, alleen als die van deze gebruiker is (anders null -> 404).
     */
    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            self::SELECT_WITH_CATEGORY . ' WHERE t.id = :id AND t.user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * Nieuwe transactie opslaan.
     *
     * @param array{category_id: int, amount_cents: int, transaction_date: string, description: ?string} $data
     */
    public function create(int $userId, array $data): int
    {
        return $this->insert(
            'INSERT INTO transactions (user_id, category_id, amount_cents, transaction_date, description)
             VALUES (:user_id, :category_id, :amount_cents, :transaction_date, :description)',
            ['user_id' => $userId] + $this->columns($data)
        );
    }

    /**
     * Transactie wijzigen (alleen je eigen).
     */
    public function update(int $id, int $userId, array $data): void
    {
        $this->execute(
            'UPDATE transactions
             SET category_id = :category_id, amount_cents = :amount_cents,
                 transaction_date = :transaction_date, description = :description
             WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId] + $this->columns($data)
        );
    }

    /**
     * Transactie verwijderen (alleen je eigen).
     */
    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM transactions WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Totaal inkomsten en uitgaven in een maand (FE-06). De database telt op in centen.
     *
     * @return array{income: int, expense: int}
     */
    public function totalsForMonth(int $userId, Month $month): array
    {
        // CASE WHEN: tel het bedrag alleen mee bij de juiste soort. COALESCE: geen transacties = 0 in plaats van NULL.
        $row = $this->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount_cents END), 0) AS income,
                COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount_cents END), 0) AS expense
             FROM transactions t
             JOIN categories c ON c.id = t.category_id AND c.user_id = t.user_id
             WHERE t.user_id = :user_id AND t.transaction_date >= :start AND t.transaction_date < :end",
            ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()]
        );

        return ['income' => (int) ($row['income'] ?? 0), 'expense' => (int) ($row['expense'] ?? 0)];
    }

    /**
     * Uitgaven per uitgavencategorie in een maand, ook categorieën zonder uitgaven.
     * LEFT JOIN: categorieën zonder transacties komen ook mee (met 0). Gebruikt door de BudgetService.
     */
    public function expensesPerCategory(int $userId, Month $month): array
    {
        return $this->fetchAll(
            "SELECT c.id, c.name, c.monthly_budget_cents, COALESCE(SUM(t.amount_cents), 0) AS spent_cents
             FROM categories c
             LEFT JOIN transactions t
                ON t.category_id = c.id
               AND t.user_id = c.user_id
               AND t.transaction_date >= :start
               AND t.transaction_date < :end
             WHERE c.user_id = :user_id AND c.type = 'expense'
             GROUP BY c.id, c.name, c.monthly_budget_cents
             ORDER BY c.name",
            ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()]
        );
    }

    /**
     * Totaal uitgegeven in één categorie in een maand (voor de waarschuwing na opslaan).
     */
    public function spentInCategory(int $userId, int $categoryId, Month $month): int
    {
        return (int) $this->fetchValue(
            'SELECT COALESCE(SUM(amount_cents), 0) FROM transactions
             WHERE user_id = :user_id AND category_id = :category_id
               AND transaction_date >= :start AND transaction_date < :end',
            ['user_id' => $userId, 'category_id' => $categoryId, 'start' => $month->start(), 'end' => $month->end()]
        );
    }

    /**
     * De kolomwaarden die bij create en update hetzelfde zijn.
     */
    private function columns(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'amount_cents' => $data['amount_cents'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'],
        ];
    }
}
