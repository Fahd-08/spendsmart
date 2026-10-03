<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Repositories\CategorySuggestionRepository;
use App\Repositories\UserRepository;
use App\Support\Role;
use PDO;
use Throwable;

/**
 * Maakt een nieuw gebruikersaccount aan, inclusief startcategorieën
 * op basis van de actieve categorievoorstellen. Alles of niets (databasetransactie).
 */
final class RegistrationService
{
    public function __construct(
        private readonly PDO $db,
        private readonly UserRepository $users,
        private readonly CategoryRepository $categories,
        private readonly CategorySuggestionRepository $suggestions,
    ) {
    }

    public function register(string $name, string $email, string $plainPassword): int
    {
        $this->db->beginTransaction();

        try {
            $userId = $this->users->create($name, $email, password_hash($plainPassword, PASSWORD_DEFAULT), Role::USER);

            foreach ($this->suggestions->active() as $suggestion) {
                $this->categories->create($userId, [
                    'name' => $suggestion['name'],
                    'type' => $suggestion['type'],
                    'monthly_budget_cents' => null,
                    'suggestion_id' => (int) $suggestion['id'],
                ]);
            }

            $this->db->commit();

            return $userId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
