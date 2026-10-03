<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\CategorySuggestionRepository;
use App\Support\CategoryType;

/**
 * Eigen categorieën beheren, met optionele maandlimiet voor uitgaven.
 */
final class CategoryController extends Controller
{
    private const LABELS = [
        'name' => 'Naam',
        'type' => 'Soort',
        'monthly_budget' => 'Maandlimiet',
    ];

    private const FIELDS = ['name', 'type', 'monthly_budget'];

    public function index(): void
    {
        $userId = $this->userId();
        $categories = new CategoryRepository($this->db());
        $adoptedIds = $categories->adoptedSuggestionIds($userId);

        $suggestions = array_filter(
            (new CategorySuggestionRepository($this->db()))->active(),
            static fn (array $suggestion): bool => !in_array((int) $suggestion['id'], $adoptedIds, true)
        );

        $this->view('categories/index', [
            'title' => 'Categorieën',
            'categories' => $categories->allForUser($userId),
            'suggestions' => array_values($suggestions),
        ]);
    }

    public function create(): void
    {
        $this->showForm('categories/create', ['name' => '', 'type' => CategoryType::EXPENSE, 'monthly_budget' => '']);
    }

    public function store(): void
    {
        $userId = $this->userId();
        [$data, $errors] = $this->validate($userId);

        if ($errors !== []) {
            $this->showForm('categories/create', $this->formValues(self::FIELDS), $errors, 422);

            return;
        }

        (new CategoryRepository($this->db()))->create($userId, $data);

        Flash::add('success', 'Categorie "' . $data['name'] . '" is aangemaakt.');
        $this->redirect('/categories');
    }

    public function edit(int $id): void
    {
        $category = $this->findOrFail($id);

        $this->showForm('categories/edit', [
            'name' => $category['name'],
            'type' => $category['type'],
            'monthly_budget' => money_input($category['monthly_budget_cents'] === null ? null : (int) $category['monthly_budget_cents']),
        ], [], 200, $category);
    }

    public function update(int $id): void
    {
        $userId = $this->userId();
        $category = $this->findOrFail($id);
        [$data, $errors] = $this->validate($userId, $category);

        if ($errors !== []) {
            $this->showForm('categories/edit', $this->formValues(self::FIELDS), $errors, 422, $category);

            return;
        }

        (new CategoryRepository($this->db()))->update($id, $userId, $data);

        Flash::add('success', 'Categorie "' . $data['name'] . '" is bijgewerkt.');
        $this->redirect('/categories');
    }

    public function destroy(int $id): void
    {
        $userId = $this->userId();
        $category = $this->findOrFail($id);
        $categories = new CategoryRepository($this->db());

        if ($categories->hasTransactions($id, $userId)) {
            Flash::add('error', 'Categorie "' . $category['name'] . '" kan niet worden verwijderd, omdat er nog transacties in staan. Wijzig of verwijder eerst die transacties.');
            $this->redirect('/categories');
        }

        $categories->delete($id, $userId);

        Flash::add('success', 'Categorie "' . $category['name'] . '" is verwijderd.');
        $this->redirect('/categories');
    }

    /**
     * Een algemeen categorievoorstel overnemen als eigen categorie.
     */
    public function adopt(int $suggestionId): void
    {
        $userId = $this->userId();
        $suggestion = (new CategorySuggestionRepository($this->db()))->findActive($suggestionId)
            ?? $this->notFound('Dit categorievoorstel bestaat niet (meer).');

        $categories = new CategoryRepository($this->db());

        if ($categories->nameExists($userId, $suggestion['name'], $suggestion['type'])) {
            Flash::add('info', 'Je hebt al een categorie "' . $suggestion['name'] . '".');
            $this->redirect('/categories');
        }

        $categories->create($userId, [
            'name' => $suggestion['name'],
            'type' => $suggestion['type'],
            'monthly_budget_cents' => null,
            'suggestion_id' => (int) $suggestion['id'],
        ]);

        Flash::add('success', 'Categorie "' . $suggestion['name'] . '" is toegevoegd aan je categorieën.');
        $this->redirect('/categories');
    }

    /**
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(int $userId, ?array $existing = null): array
    {
        $validator = Validator::make($this->request->body(), [
            'name' => 'required|max:60',
            'type' => 'required|' . CategoryType::ruleIn(),
            'monthly_budget' => 'money_zero',
        ], self::LABELS);

        $values = $validator->validated();
        $categories = new CategoryRepository($this->db());
        $existingId = $existing === null ? null : (int) $existing['id'];

        if (isset($values['name'], $values['type'])
            && $categories->nameExists($userId, $values['name'], $values['type'], $existingId)) {
            $validator->addError('name', 'Je hebt al een ' . mb_strtolower(CategoryType::label($values['type'])) . 'categorie met deze naam.');
        }

        // Van soort wisselen zou bestaande transacties ongemerkt van inkomst naar uitgave zetten.
        if ($existing !== null && isset($values['type']) && $values['type'] !== $existing['type']
            && $categories->hasTransactions($existingId, $userId)) {
            $validator->addError('type', 'De soort kan niet worden gewijzigd, omdat er al transacties in deze categorie staan.');
        }

        if ($validator->fails()) {
            return [[], $validator->errors()];
        }

        return [[
            'name' => $values['name'],
            'type' => $values['type'],
            // Een limiet geldt alleen voor uitgaven.
            'monthly_budget_cents' => $values['type'] === CategoryType::EXPENSE ? $values['monthly_budget'] : null,
        ], []];
    }

    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $category = null): void
    {
        $this->view($template, [
            'title' => $category === null ? 'Categorie toevoegen' : 'Categorie wijzigen',
            'values' => $values,
            'errors' => $errors,
            'category' => $category,
        ], $status);
    }

    private function findOrFail(int $id): array
    {
        return (new CategoryRepository($this->db()))->findForUser($id, $this->userId())
            ?? $this->notFound('Deze categorie bestaat niet of hoort niet bij jouw account.');
    }
}
