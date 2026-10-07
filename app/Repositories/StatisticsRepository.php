<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Role;

/**
 * Anonieme gebruiksaantallen voor de contentbeheerder (FE-12).
 * Geeft alleen totalen (COUNT) terug: geen namen, e-mailadressen, bedragen of omschrijvingen.
 */
final class StatisticsRepository extends Repository
{
    /**
     * Aantal gewone gebruikers (contentbeheerders tellen niet mee).
     */
    public function countUsers(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM users WHERE role = :role', ['role' => Role::USER]);
    }

    /**
     * Aantal gebruikers dat sinds een datum een account heeft gemaakt.
     */
    public function countNewUsersSince(string $date): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM users WHERE role = :role AND created_at >= :date',
            ['role' => Role::USER, 'date' => $date]
        );
    }

    /**
     * Totaal aantal transacties van alle gebruikers samen.
     */
    public function countTransactions(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM transactions');
    }

    /**
     * Aantal spaardoelen en hoeveel daarvan bereikt zijn.
     * SUM(saved_cents >= target_cents) telt de 1'en: elk bereikt doel telt als 1.
     *
     * @return array{total: int, reached: int}
     */
    public function countGoals(): array
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total, COALESCE(SUM(saved_cents >= target_cents), 0) AS reached FROM savings_goals'
        );

        return ['total' => (int) ($row['total'] ?? 0), 'reached' => (int) ($row['reached'] ?? 0)];
    }

    /**
     * Aantal registraties en actieve gebruikers per maand (vanaf een startdatum).
     * GROUP BY month_key: één rij per maand.
     *
     * @return array<string, array{transaction_count: int, active_users: int}> met maand 'YYYY-MM' als sleutel
     */
    public function activityPerMonth(string $fromDate): array
    {
        $rows = $this->fetchAll(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month_key,
                    COUNT(*) AS transaction_count,
                    COUNT(DISTINCT user_id) AS active_users
             FROM transactions
             WHERE transaction_date >= :from_date
             GROUP BY month_key",
            ['from_date' => $fromDate]
        );

        // Lijst omzetten naar ['2026-09' => [...], '2026-10' => [...]] zodat we per maand kunnen opzoeken.
        $result = [];
        foreach ($rows as $row) {
            $result[$row['month_key']] = [
                'transaction_count' => (int) $row['transaction_count'],
                'active_users' => (int) $row['active_users'],
            ];
        }

        return $result;
    }
}
