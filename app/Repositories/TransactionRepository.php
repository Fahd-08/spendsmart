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
    private const SELECT_WITH_CATEGORY =
        'SELECT t.id, t.amount_cents, t.transaction_date, t.description,
                c.id AS category_id, c.name AS category_name, c.type
         FROM transactions t
         JOIN categories c ON c.id = t.category_id AND c.user_id = t.user_id';

    /**
     * @param string|null $type 'income', 'expense' of null voor alles
     */
    public function filterForUser(int $userId, Month $month, ?int $categoryId = null, ?string $type = null): array
    {
        $sql = self::SELECT_WITH_CATEGORY . '
            WHERE t.user_id = :user_id
              AND t.transaction_date >= :start AND t.transaction_date < :end';
        $parameters = ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()];

        if ($categoryId !== null) {
            $sql .= ' AND t.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }

        if ($type !== null) {
            $sql .= ' AND c.type = :type';
            $parameters['type'] = $type;
        }

        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC';

        return $this->fetchAll($sql, $parameters);
    }

    public function recentForUser(int $userId, Month $month, int $limit = 5): array
    {
        return $this->fetchAll(
            self::SELECT_WITH_CATEGORY . '
             WHERE t.user_id = :user_id AND t.transaction_date >= :start AND t.transaction_date < :end
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT ' . max(1, $limit),
            ['user_id' => $userId, 'start' => $month->start(), 'end' => $month->end()]
        );
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            self::SELECT_WITH_CATEGORY . ' WHERE t.id = :id AND t.user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
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

    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM transactions WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * @return array{income: int, expense: int}
     */
    public function totalsForMonth(int $userId, Month $month): array
    {
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

    public function spentInCategory(int $userId, int $categoryId, Month $month): int
    {
        return (int) $this->fetchValue(
            'SELECT COALESCE(SUM(amount_cents), 0) FROM transactions
             WHERE user_id = :user_id AND category_id = :category_id
               AND transaction_date >= :start AND transaction_date < :end',
            ['user_id' => $userId, 'category_id' => $categoryId, 'start' => $month->start(), 'end' => $month->end()]
        );
    }

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
