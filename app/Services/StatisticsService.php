<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategorySuggestionRepository;
use App\Repositories\StatisticsRepository;
use App\Support\Month;

/**
 * Stelt het anonieme statistiekenoverzicht samen voor de contentbeheerder (FE-12).
 * Alleen aantallen: geen namen, e-mailadressen of bedragen.
 */
final class StatisticsService
{
    public function __construct(
        private readonly StatisticsRepository $statistics,
        private readonly CategorySuggestionRepository $suggestions,
    ) {
    }

    /**
     * Alle cijfers voor de statistiekenpagina.
     *
     * @param int $monthCount over hoeveel maanden de activiteit wordt getoond
     */
    public function overview(int $monthCount = 6): array
    {
        $goals = $this->statistics->countGoals();

        return [
            'user_count' => $this->statistics->countUsers(),
            'new_user_count' => $this->statistics->countNewUsersSince(date('Y-m-d', strtotime('-30 days'))),
            'transaction_count' => $this->statistics->countTransactions(),
            'goal_count' => $goals['total'],
            'goals_reached_count' => $goals['reached'],
            'activity' => $this->activity($monthCount),
            'suggestions' => $this->suggestions->all(), // met per voorstel hoe vaak het is overgenomen
        ];
    }

    /**
     * Activiteit voor de laatste maanden, ook maanden zonder activiteit (0).
     */
    private function activity(int $monthCount): array
    {
        // Lijst met de laatste X maanden maken, oudste eerst.
        $months = [];
        $month = Month::current();
        for ($i = 0; $i < $monthCount; $i++) {
            array_unshift($months, $month); // vooraan toevoegen
            $month = $month->previous();
        }

        // Aantallen uit de database, per maand ('2026-09' => [...]).
        $counts = $this->statistics->activityPerMonth($months[0]->start());

        // Drukste maand bepalen; die krijgt een volle balk (100%). Minimaal 1, zodat we niet door 0 delen.
        $maximum = 1;
        foreach ($counts as $row) {
            $maximum = max($maximum, $row['transaction_count']);
        }

        // Per maand een regel; maanden die niet in de database staan krijgen 0.
        return array_map(static function (Month $month) use ($counts, $maximum): array {
            $row = $counts[$month->key()] ?? ['transaction_count' => 0, 'active_users' => 0];

            return [
                'label' => $month->shortLabel(),
                'transaction_count' => $row['transaction_count'],
                'active_users' => $row['active_users'],
                'percentage' => intdiv($row['transaction_count'] * 100, $maximum), // breedte van de balk
            ];
        }, $months);
    }
}
