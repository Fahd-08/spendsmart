<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Support\Money;
use App\Support\Month;

/**
 * Berekent per uitgavencategorie hoeveel van de zelf ingestelde maandlimiet is gebruikt (FE-09).
 * De meldingen zijn neutraal: ze vergelijken alleen met de limiet van de gebruiker
 * en geven geen financieel advies.
 *
 * Een service bevat "businesslogica": regels en berekeningen die niet in een controller
 * (verzoek afhandelen) of repository (database) horen.
 */
final class BudgetService
{
    // De vier mogelijke statussen van een limiet.
    public const STATUS_NONE = 'none';       // geen limiet ingesteld
    public const STATUS_OK = 'ok';           // ruim binnen de limiet
    public const STATUS_WARNING = 'warning'; // 80% of meer gebruikt
    public const STATUS_OVER = 'over';       // limiet overschreden

    /** Deze zin staat bij elke waarschuwing: de app geeft geen advies, alleen informatie. */
    public const DISCLAIMER = 'Dit is een melding op basis van je eigen limiet, geen financieel advies.';

    /**
     * @param int $warningPercentage vanaf welk percentage "bijna bereikt" wordt getoond (uit config: 80)
     */
    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly int $warningPercentage = 80,
    ) {
    }

    /**
     * Overzicht van alle uitgavencategorieën van een gebruiker in een maand:
     * limiet, uitgegeven, resterend, percentage en status. Wordt op het dashboard getoond.
     *
     * @return array<int, array{category_id: int, name: string, budget_cents: ?int, spent_cents: int,
     *     remaining_cents: ?int, percentage: int, status: string, status_label: string}>
     */
    public function overview(int $userId, Month $month): array
    {
        $overview = [];

        // De database telt per categorie de uitgaven op; hier maken we er een regel van.
        foreach ($this->transactions->expensesPerCategory($userId, $month) as $row) {
            $budget = $row['monthly_budget_cents'] === null ? null : (int) $row['monthly_budget_cents'];
            $overview[] = $this->line((int) $row['id'], (string) $row['name'], (int) $row['spent_cents'], $budget);
        }

        return $overview;
    }

    /**
     * Categorieën waarvan de limiet is overschreden (voor de waarschuwingen bovenaan het dashboard).
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
        // Geen limiet ingesteld: dan valt er niets te overschrijden.
        if ($category['monthly_budget_cents'] === null) {
            return null;
        }

        $budget = (int) $category['monthly_budget_cents'];

        // Totaal uitgegeven in deze categorie in de maand van de transactie.
        $spent = $this->transactions->spentInCategory($userId, (int) $category['id'], $month);

        if ($this->status($spent, $budget) !== self::STATUS_OVER) {
            return null;
        }

        return self::overLimitMessage((string) $category['name'], $month, $spent, $budget);
    }

    /**
     * De tekst van de waarschuwing, inclusief de disclaimer "geen financieel advies".
     */
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

    /**
     * Bepaalt de status van een limiet.
     * Voorbeeld met limiet € 100: € 50 = ok, € 85 = bijna bereikt, € 100,01 = overschreden.
     */
    public function status(int $spentCents, ?int $budgetCents): string
    {
        if ($budgetCents === null) {
            return self::STATUS_NONE;
        }

        // Meer uitgegeven dan de limiet (precies op de limiet is nog niet "over").
        if ($spentCents > $budgetCents) {
            return self::STATUS_OVER;
        }

        // 80% of meer gebruikt.
        if ($budgetCents > 0 && Money::percentage($spentCents, $budgetCents) >= $this->warningPercentage) {
            return self::STATUS_WARNING;
        }

        // Limiet van € 0,00 en nog niets uitgegeven: binnen de limiet.
        return self::STATUS_OK;
    }

    /**
     * Tekst bij een status. Status wordt altijd met kleur én tekst getoond (ook voor kleurenblinden).
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_OK => 'Binnen limiet',
            self::STATUS_WARNING => 'Bijna bereikt',
            self::STATUS_OVER => 'Limiet overschreden',
            default => 'Geen limiet',
        };
    }

    /**
     * Maakt één regel van het overzicht.
     */
    private function line(int $categoryId, string $name, int $spent, ?int $budget): array
    {
        $status = $this->status($spent, $budget);

        // Percentage voor de voortgangsbalk.
        $percentage = match (true) {
            $budget === null => 0,                  // geen limiet: geen balk
            $budget === 0 => $spent > 0 ? 100 : 0,  // limiet € 0: elke uitgave = vol
            default => Money::percentage($spent, $budget),
        };

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'budget_cents' => $budget,
            'spent_cents' => $spent,
            'remaining_cents' => $budget === null ? null : $budget - $spent, // negatief = boven de limiet
            'percentage' => $percentage,
            'status' => $status,
            'status_label' => self::statusLabel($status),
        ];
    }
}
