<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Money;

/**
 * Spaardoelen van een gebruiker (FE-08). Elke query filtert op user_id.
 */
final class SavingsGoalRepository extends Repository
{
    /**
     * Alle spaardoelen van een gebruiker. Volgorde: nog niet bereikte doelen eerst,
     * dan op streefdatum (doelen zonder datum achteraan), dan op naam.
     *
     * @param int|null $limit maximaal aantal (bijv. 3 op het dashboard), of null voor alles
     */
    public function allForUser(int $userId, ?int $limit = null): array
    {
        $sql = 'SELECT id, name, target_cents, saved_cents, target_date
                FROM savings_goals WHERE user_id = :user_id
                ORDER BY (saved_cents >= target_cents), target_date IS NULL, target_date, name';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit); // $limit komt uit onze eigen code, niet van de gebruiker
        }

        return $this->fetchAll($sql, ['user_id' => $userId]);
    }

    /**
     * Eén spaardoel, alleen als het van deze gebruiker is (anders null -> 404).
     */
    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, target_cents, saved_cents, target_date
             FROM savings_goals WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * Nieuw spaardoel opslaan.
     *
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

    /**
     * Spaardoel wijzigen (alleen je eigen).
     */
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
     *
     * "In één query": de database rekent zelf saved_cents + bedrag uit. Als je twee keer tegelijk
     * op "toevoegen" klikt, gaat er dus geen bedrag verloren.
     */
    public function addToSaved(int $id, int $userId, int $amountCents): bool
    {
        return $this->execute(
            'UPDATE savings_goals SET saved_cents = saved_cents + :amount
             WHERE id = :id AND user_id = :user_id AND saved_cents + :amount_check <= :max',
            [
                'amount' => $amountCents,
                'amount_check' => $amountCents, // zelfde waarde: een parameter mag maar één keer in de query staan
                'max' => Money::MAX_CENTS,
                'id' => $id,
                'user_id' => $userId,
            ]
        ) === 1; // 1 rij gewijzigd = gelukt; 0 = boven het maximum
    }

    /**
     * Spaardoel verwijderen (alleen je eigen).
     */
    public function delete(int $id, int $userId): void
    {
        $this->execute('DELETE FROM savings_goals WHERE id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * De kolomwaarden die bij create en update hetzelfde zijn.
     */
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
