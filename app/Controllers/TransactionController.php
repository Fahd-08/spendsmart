<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Services\BudgetService;
use App\Support\CategoryType;
use App\Support\Money;
use App\Support\Month;

/**
 * Inkomsten en uitgaven registreren, bekijken, filteren, wijzigen en verwijderen.
 */
final class TransactionController extends Controller
{
    private const LABELS = [
        'category_id' => 'Categorie',
        'amount' => 'Bedrag',
        'transaction_date' => 'Datum',
        'description' => 'Omschrijving',
    ];

    private const RULES = [
        'category_id' => 'required|integer',
        'amount' => 'required|money',
        'transaction_date' => 'required|date',
        'description' => 'max:255',
    ];

    public function index(): void
    {
        $userId = $this->userId();
        $month = $this->selectedMonth();
        $categories = (new CategoryRepository($this->db()))->allForUser($userId);

        $categoryId = $this->selectedCategoryId($categories);
        $type = in_array($this->request->query('type'), CategoryType::ALL, true) ? $this->request->query('type') : null;

        $transactions = (new TransactionRepository($this->db()))->filterForUser($userId, $month, $categoryId, $type);

        $this->view('transactions/index', [
            'title' => 'Transacties',
            'month' => $month,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
            'selectedType' => $type,
            'transactions' => $transactions,
            'totals' => self::sumByType($transactions),
            'isFiltered' => $categoryId !== null || $type !== null,
        ]);
    }

    public function create(): void
    {
        $categories = $this->categoriesOrRedirect();
        $categoryId = $this->request->query('category_id');

        $this->showForm('transactions/create', $categories, [
            'category_id' => $categoryId,
            'amount' => '',
            'transaction_date' => date('Y-m-d'),
            'description' => '',
        ]);
    }

    public function store(): void
    {
        $userId = $this->userId();
        $categories = $this->categoriesOrRedirect();
        [$data, $category, $errors] = $this->validate($userId);

        if ($errors !== []) {
            $this->showForm('transactions/create', $categories, $this->formValues(array_keys(self::RULES)), $errors, 422);

            return;
        }

        $transactions = new TransactionRepository($this->db());
        $transactions->create($userId, $data);

        Flash::add('success', CategoryType::label($category['type']) . ' van ' . Money::format($data['amount_cents']) . ' is opgeslagen.');
        $this->flashBudgetWarning($userId, $category, $data['transaction_date'], $transactions);

        $this->redirect('/transactions', ['month' => Month::fromDate($data['transaction_date'])->key()]);
    }

    public function edit(int $id): void
    {
        $transaction = $this->findOrFail($id);
        $categories = $this->categoriesOrRedirect();

        $this->showForm('transactions/edit', $categories, [
            'category_id' => (string) $transaction['category_id'],
            'amount' => Money::toInput((int) $transaction['amount_cents']),
            'transaction_date' => $transaction['transaction_date'],
            'description' => (string) $transaction['description'],
        ], [], 200, $transaction);
    }

    public function update(int $id): void
    {
        $userId = $this->userId();
        $transaction = $this->findOrFail($id);
        $categories = $this->categoriesOrRedirect();
        [$data, $category, $errors] = $this->validate($userId);

        if ($errors !== []) {
            $this->showForm('transactions/edit', $categories, $this->formValues(array_keys(self::RULES)), $errors, 422, $transaction);

            return;
        }

        $transactions = new TransactionRepository($this->db());
        $transactions->update($id, $userId, $data);

        Flash::add('success', 'De transactie is bijgewerkt.');
        $this->flashBudgetWarning($userId, $category, $data['transaction_date'], $transactions);

        $this->redirect('/transactions', ['month' => Month::fromDate($data['transaction_date'])->key()]);
    }

    public function destroy(int $id): void
    {
        $transaction = $this->findOrFail($id);
        (new TransactionRepository($this->db()))->delete($id, $this->userId());

        Flash::add('success', 'De transactie is verwijderd.');
        $this->redirect('/transactions', ['month' => Month::fromDate($transaction['transaction_date'])->key()]);
    }

    /**
     * @return array{0: array, 1: ?array, 2: array<string, string>} [gegevens, categorie, fouten]
     */
    private function validate(int $userId): array
    {
        $validator = Validator::make($this->request->body(), self::RULES, self::LABELS);
        $values = $validator->validated();
        $category = null;

        // De categorie moet van de ingelogde gebruiker zijn.
        if (isset($values['category_id'])) {
            $category = (new CategoryRepository($this->db()))->findForUser($values['category_id'], $userId);
            if ($category === null) {
                $validator->addError('category_id', 'Kies een van je eigen categorieën.');
            }
        }

        if ($validator->fails()) {
            return [[], null, $validator->errors()];
        }

        return [[
            'category_id' => $values['category_id'],
            'amount_cents' => $values['amount'],
            'transaction_date' => $values['transaction_date'],
            'description' => $values['description'],
        ], $category, []];
    }

    private function flashBudgetWarning(int $userId, array $category, string $date, TransactionRepository $transactions): void
    {
        if ($category['type'] !== CategoryType::EXPENSE) {
            return;
        }

        $budgetService = new BudgetService($transactions, (int) config('budget.warning_percentage'));
        $message = $budgetService->exceededMessage($userId, $category, Month::fromDate($date));

        if ($message !== null) {
            Flash::add('warning', $message);
        }
    }

    private function showForm(
        string $template,
        array $categories,
        array $values,
        array $errors = [],
        int $status = 200,
        ?array $transaction = null,
    ): void {
        $this->view($template, [
            'title' => $transaction === null ? 'Transactie toevoegen' : 'Transactie wijzigen',
            'categories' => $categories,
            'values' => $values,
            'errors' => $errors,
            'transaction' => $transaction,
        ], $status);
    }

    private function categoriesOrRedirect(): array
    {
        $categories = (new CategoryRepository($this->db()))->allForUser($this->userId());

        if ($categories === []) {
            Flash::add('info', 'Maak eerst een categorie aan. Daarna kun je inkomsten en uitgaven registreren.');
            $this->redirect('/categories/create');
        }

        return $categories;
    }

    private function findOrFail(int $id): array
    {
        return (new TransactionRepository($this->db()))->findForUser($id, $this->userId())
            ?? $this->notFound('Deze transactie bestaat niet of hoort niet bij jouw account.');
    }

    /**
     * Categoriefilter uit de URL; alleen eigen categorieën zijn geldig.
     */
    private function selectedCategoryId(array $categories): ?int
    {
        $value = $this->request->query('category');
        if ($value === '') {
            return null;
        }

        foreach ($categories as $category) {
            if ((string) $category['id'] === $value) {
                return (int) $category['id'];
            }
        }

        Flash::add('info', 'De gekozen categorie bestaat niet. Alle categorieën worden getoond.');

        return null;
    }

    /**
     * @return array{income: int, expense: int}
     */
    private static function sumByType(array $transactions): array
    {
        $totals = ['income' => 0, 'expense' => 0];

        foreach ($transactions as $transaction) {
            $totals[$transaction['type']] += (int) $transaction['amount_cents'];
        }

        return $totals;
    }
}
