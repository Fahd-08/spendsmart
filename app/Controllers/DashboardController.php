<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\TipRepository;
use App\Repositories\TransactionRepository;
use App\Services\BudgetService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $userId = $this->userId();
        $month = $this->selectedMonth();

        $transactions = new TransactionRepository($this->db());
        $budgetService = new BudgetService($transactions, (int) config('budget.warning_percentage'));

        $totals = $transactions->totalsForMonth($userId, $month);
        $budgets = $budgetService->overview($userId, $month);

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'month' => $month,
            'totals' => $totals,
            'balance' => $totals['income'] - $totals['expense'],
            'budgets' => $budgets,
            'exceeded' => $budgetService->exceeded($budgets),
            'goals' => (new SavingsGoalRepository($this->db()))->allForUser($userId, 3),
            'recentTransactions' => $transactions->recentForUser($userId, $month),
            'tip' => (new TipRepository($this->db()))->randomPublished(),
        ]);
    }
}
