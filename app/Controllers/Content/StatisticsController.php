<?php

declare(strict_types=1);

namespace App\Controllers\Content;

use App\Core\Controller;
use App\Repositories\CategorySuggestionRepository;
use App\Repositories\StatisticsRepository;
use App\Services\StatisticsService;

/**
 * Contentbeheerder: anonieme gebruiksaantallen (geen persoonsgegevens of bedragen).
 */
final class StatisticsController extends Controller
{
    public function index(): void
    {
        $service = new StatisticsService(
            new StatisticsRepository($this->db()),
            new CategorySuggestionRepository($this->db()),
        );

        $this->view('content/statistics/index', [
            'title' => 'Statistieken',
            'statistics' => $service->overview(),
        ]);
    }
}
