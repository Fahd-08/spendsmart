<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Support\Money;
use App\Support\Month;

/**
 * Berekent per uitgavencategorie hoeveel van de zelf ingestelde maandlimiet is gebruikt.
 * De meldingen zijn neutraal: ze vergelijken alleen met de limiet van de gebruiker
 * en geven geen financieel advies.
 */
final class BudgetService
{
    public const STATUS_NONE = 'none';
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_OVER = 'over';

    public const DISCLAIMER = 'Dit is een melding op basis van je eigen limiet, geen financieel advies.';

    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly int $warningPercentage = 80,
    ) {
    }

    /**
     * @return array<int, array{category_id: int, name: string, budget_cents: ?int, spent_cents: int,
     *     remaining_cents: ?int, percentage: int, status: string, status_label: string}>
     */
    public function overview(int $userId, Month $month): array
    {
        $overview = [];

        foreach ($this->transactions->expensesPerCategory($userId, $month) as $row) {
            $budget = $row['monthly_budget_cents'] === null ? null : (int) $row['monthly_budget_cents'];
            $overview[] = $this->line((int) $row['id'], (string) $row['name'], (int) $row['spent_cents'], $budget);
        }

        return $overview;
    }

    /**
     * Categorieën waarvan de limiet is overschreden.
     */
    public function exceeded(array $overview): array
    {
        return array_values(array_filter(
            $overview,
            static fn (array $line): bool => $line['status'] === self::STATUS_OVER
        ));
    }

    /**
     * Melding na het opslaan van een uitgave, of null als de limiet niet is overschreden.
     */
    public function exceededMessage(int $userId, array $category, Month $month): ?string
    {
        if ($category['monthly_budget_cents'] === null) {
            return null;
        }

        $budget = (int) $category['monthly_budget_cents'];
        $spent = $this->transactions->spentInCategory($userId, (int) $category['id'], $month);

        if ($this->status($spent, $budget) !== self::STATUS_OVER) {
            return null;
        }

        return self::overLimitMessage((string) $category['name'], $month, $spent, $budget);
    }

    public static function overLimitMessage(string $categoryName, Month $month, int $spent, int $budget): string
    {
        return sprintf(
            'Je uitgaven voor "%s" in %s (%s) zijn hoger dan je ingestelde limiet van %s. %s',
            $categoryName,
            $month->label(),
            Money::format($spent),
            Money::format($budget),
            self::DISCLAIMER
        );
    }

    public function status(int $spentCents, ?int $budgetCents): string
    {
        if ($budgetCents === null) {
            return self::STATUS_NONE;
        }

        if ($spentCents > $budgetCents) {
            return self::STATUS_OVER;
        }

        if ($budgetCents > 0 && Money::percentage($spentCents, $budgetCents) >= $this->warningPercentage) {
            return self::STATUS_WARNING;
        }

        // Limiet van € 0,00 en nog niets uitgegeven: binnen de limiet.
        return self::STATUS_OK;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_OK => 'Binnen limiet',
            self::STATUS_WARNING => 'Bijna bereikt',
            self::STATUS_OVER => 'Limiet overschreden',
            default => 'Geen limiet',
        };
    }

    private function line(int $categoryId, string $name, int $spent, ?int $budget): array
    {
        $status = $this->status($spent, $budget);
        $percentage = match (true) {
            $budget === null => 0,
            $budget === 0 => $spent > 0 ? 100 : 0,
            default => Money::percentage($spent, $budget),
        };

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'budget_cents' => $budget,
            'spent_cents' => $spent,
            'remaining_cents' => $budget === null ? null : $budget - $spent,
            'percentage' => $percentage,
            'status' => $status,
            'status_label' => self::statusLabel($status),
        ];
    }
}
