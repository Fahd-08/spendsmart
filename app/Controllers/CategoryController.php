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
 * Eigen categorieën beheren, met optionele maandlimiet voor uitgaven (FE-07),
 * en categorievoorstellen overnemen (FE-10).
 */
final class CategoryController extends Controller
{
    /** Veldnamen zoals de gebruiker ze ziet in foutmeldingen. */
    private const LABELS = [
        'name' => 'Naam',
        'type' => 'Soort',
        'monthly_budget' => 'Maandlimiet',
    ];

    /** Velden van het formulier. */
    private const FIELDS = ['name', 'type', 'monthly_budget'];

    /**
     * GET /categories: eigen categorieën + voorstellen die je nog niet hebt overgenomen.
     */
    public function index(): void
    {
        $userId = $this->userId();
        $categories = new CategoryRepository($this->db());
        $adoptedIds = $categories->adoptedSuggestionIds($userId);

        // Alleen actieve voorstellen tonen die de gebruiker nog niet heeft.
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

    /**
     * GET /categories/create: leeg formulier (standaard een uitgavencategorie).
     */
    public function create(): void
    {
        $this->showForm('categories/create', ['name' => '', 'type' => CategoryType::EXPENSE, 'monthly_budget' => '']);
    }

    /**
     * POST /categories: nieuwe categorie opslaan.
     */
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

    /**
     * GET /categories/{id}/edit: formulier met de bestaande gegevens.
     */
    public function edit(int $id): void
    {
        $category = $this->findOrFail($id);

        $this->showForm('categories/edit', [
            'name' => $category['name'],
            'type' => $category['type'],
            'monthly_budget' => money_input($category['monthly_budget_cents'] === null ? null : (int) $category['monthly_budget_cents']),
        ], [], 200, $category);
    }

    /**
     * POST /categories/{id}/update: wijzigingen opslaan.
     */
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

    /**
     * POST /categories/{id}/delete: categorie verwijderen, maar alleen als hij leeg is.
     */
    public function destroy(int $id): void
    {
        $userId = $this->userId();
        $category = $this->findOrFail($id);
        $categories = new CategoryRepository($this->db());

        // Staan er nog transacties in? Dan niet verwijderen, anders raken die transacties hun categorie kwijt.
        if ($categories->hasTransactions($id, $userId)) {
            Flash::add('error', 'Categorie "' . $category['name'] . '" kan niet worden verwijderd, omdat er nog transacties in staan. Wijzig of verwijder eerst die transacties.');
            $this->redirect('/categories');
        }

        $categories->delete($id, $userId);

        Flash::add('success', 'Categorie "' . $category['name'] . '" is verwijderd.');
        $this->redirect('/categories');
    }

    /**
     * POST /categories/adopt/{id}: een algemeen categorievoorstel overnemen als eigen categorie.
     */
    public function adopt(int $suggestionId): void
    {
        $userId = $this->userId();

        // Alleen actieve voorstellen kunnen worden overgenomen; anders 404.
        $suggestion = (new CategorySuggestionRepository($this->db()))->findActive($suggestionId)
            ?? $this->notFound('Dit categorievoorstel bestaat niet (meer).');

        $categories = new CategoryRepository($this->db());

        // Heb je al een categorie met die naam en soort? Dan geen dubbele maken.
        if ($categories->nameExists($userId, $suggestion['name'], $suggestion['type'])) {
            Flash::add('info', 'Je hebt al een categorie "' . $suggestion['name'] . '".');
            $this->redirect('/categories');
        }

        // Nieuwe categorie, gekoppeld aan het voorstel (voor de statistieken).
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
     * Controleert het formulier. Bij wijzigen is $existing de huidige categorie.
     *
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(int $userId, ?array $existing = null): array
    {
        $validator = Validator::make($this->request->body(), [
            'name' => 'required|max:60',
            'type' => 'required|' . CategoryType::ruleIn(),
            'monthly_budget' => 'money_zero', // optioneel; € 0,00 mag
        ], self::LABELS);

        $values = $validator->validated();
        $categories = new CategoryRepository($this->db());
        $existingId = $existing === null ? null : (int) $existing['id'];

        // Geen twee categorieën met dezelfde naam en soort (Boodschappen als inkomst én uitgave mag wel).
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

    /**
     * Toont het formulier voor toevoegen of wijzigen.
     */
    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $category = null): void
    {
        $this->view($template, [
            'title' => $category === null ? 'Categorie toevoegen' : 'Categorie wijzigen',
            'values' => $values,
            'errors' => $errors,
            'category' => $category,
        ], $status);
    }

    /**
     * Zoekt een categorie van de ingelogde gebruiker, of toont 404.
     */
    private function findOrFail(int $id): array
    {
        return (new CategoryRepository($this->db()))->findForUser($id, $this->userId())
            ?? $this->notFound('Deze categorie bestaat niet of hoort niet bij jouw account.');
    }
}
