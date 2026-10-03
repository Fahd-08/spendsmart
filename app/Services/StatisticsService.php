<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategorySuggestionRepository;
use App\Repositories\StatisticsRepository;
use App\Support\Month;

/**
 * Stelt het anonieme statistiekenoverzicht samen voor de contentbeheerder.
 */
final class StatisticsService
{
    public function __construct(
        private readonly StatisticsRepository $statistics,
        private readonly CategorySuggestionRepository $suggestions,
    ) {
    }

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
            'suggestions' => $this->suggestions->all(),
        ];
    }

    /**
     * Activiteit voor de laatste maanden, ook maanden zonder activiteit (0).
     */
    private function activity(int $monthCount): array
    {
        $months = [];
        $month = Month::current();
        for ($i = 0; $i < $monthCount; $i++) {
            array_unshift($months, $month);
            $month = $month->previous();
        }

        $counts = $this->statistics->activityPerMonth($months[0]->start());
        $maximum = 1;
        foreach ($counts as $row) {
            $maximum = max($maximum, $row['transaction_count']);
        }

        return array_map(static function (Month $month) use ($counts, $maximum): array {
            $row = $counts[$month->key()] ?? ['transaction_count' => 0, 'active_users' => 0];

            return [
                'label' => $month->shortLabel(),
                'transaction_count' => $row['transaction_count'],
                'active_users' => $row['active_users'],
                'percentage' => intdiv($row['transaction_count'] * 100, $maximum),
            ];
        }, $months);
    }
}
