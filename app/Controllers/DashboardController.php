<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\TipRepository;
use App\Repositories\TransactionRepository;
use App\Services\BudgetService;

/**
 * Het dashboard: overzicht van één maand voor de gebruiker (FE-06, FE-09).
 */
final class DashboardController extends Controller
{
    /**
     * GET /dashboard: maandtotalen, limieten, laatste transacties, spaardoelen en een tip.
     */
    public function index(): void
    {
        $userId = $this->userId();
        $month = $this->selectedMonth();

        $transactions = new TransactionRepository($this->db());
        $budgetService = new BudgetService($transactions, (int) config('budget.warning_percentage'));

        // Inkomsten en uitgaven van de maand (opgeteld door de database).
        $totals = $transactions->totalsForMonth($userId, $month);

        // Per uitgavencategorie: hoeveel van de limiet is gebruikt.
        $budgets = $budgetService->overview($userId, $month);

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'month' => $month,
            'totals' => $totals,
            'balance' => $totals['income'] - $totals['expense'],             // saldo = inkomsten - uitgaven
            'budgets' => $budgets,
            'exceeded' => $budgetService->exceeded($budgets),                 // voor de waarschuwingen bovenaan
            'goals' => (new SavingsGoalRepository($this->db()))->allForUser($userId, 3), // maximaal 3
            'recentTransactions' => $transactions->recentForUser($userId, $month),
            'tip' => (new TipRepository($this->db()))->randomPublished(),
        ]);
    }
}
