<?php

declare(strict_types=1);

namespace App\Controllers\Content;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\CategorySuggestionRepository;
use App\Support\CategoryType;

/**
 * Contentbeheerder: algemene categorievoorstellen beheren.
 * Gebruikers kunnen actieve voorstellen overnemen als eigen categorie.
 */
final class SuggestionController extends Controller
{
    private const LABELS = ['name' => 'Naam', 'type' => 'Soort', 'description' => 'Omschrijving'];

    private const FIELDS = ['name', 'type', 'description', 'is_active'];

    public function index(): void
    {
        $this->view('content/suggestions/index', [
            'title' => 'Categorievoorstellen',
            'suggestions' => (new CategorySuggestionRepository($this->db()))->all(),
        ]);
    }

    public function create(): void
    {
        $this->showForm('content/suggestions/create', [
            'name' => '',
            'type' => CategoryType::EXPENSE,
            'description' => '',
            'is_active' => '1',
        ]);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            $this->showForm('content/suggestions/create', $this->formValues(self::FIELDS), $errors, 422);

            return;
        }

        (new CategorySuggestionRepository($this->db()))->create($data, $this->userId());

        Flash::add('success', 'Categorievoorstel "' . $data['name'] . '" is aangemaakt.');
        $this->redirect('/content/suggestions');
    }

    public function edit(int $id): void
    {
        $suggestion = $this->findOrFail($id);

        $this->showForm('content/suggestions/edit', [
            'name' => $suggestion['name'],
            'type' => $suggestion['type'],
            'description' => (string) $suggestion['description'],
            'is_active' => $suggestion['is_active'] ? '1' : '',
        ], [], 200, $suggestion);
    }

    public function update(int $id): void
    {
        $suggestion = $this->findOrFail($id);
        [$data, $errors] = $this->validate($id);

        if ($errors !== []) {
            $this->showForm('content/suggestions/edit', $this->formValues(self::FIELDS), $errors, 422, $suggestion);

            return;
        }

        (new CategorySuggestionRepository($this->db()))->update($id, $data);

        Flash::add('success', 'Categorievoorstel "' . $data['name'] . '" is bijgewerkt.');
        $this->redirect('/content/suggestions');
    }

    public function destroy(int $id): void
    {
        $suggestion = $this->findOrFail($id);
        (new CategorySuggestionRepository($this->db()))->delete($id);

        Flash::add('success', 'Categorievoorstel "' . $suggestion['name'] . '" is verwijderd. Categorieën die gebruikers al hebben overgenomen, blijven bestaan.');
        $this->redirect('/content/suggestions');
    }

    /**
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(?int $exceptId = null): array
    {
        $validator = Validator::make($this->request->body(), [
            'name' => 'required|max:60',
            'type' => 'required|' . CategoryType::ruleIn(),
            'description' => 'max:255',
        ], self::LABELS);

        $values = $validator->validated();

        if (isset($values['name'], $values['type'])
            && (new CategorySuggestionRepository($this->db()))->nameExists($values['name'], $values['type'], $exceptId)) {
            $validator->addError('name', 'Er bestaat al een voorstel met deze naam en soort.');
        }

        if ($validator->fails()) {
            return [[], $validator->errors()];
        }

        return [[
            'name' => $values['name'],
            'type' => $values['type'],
            'description' => $values['description'],
            'is_active' => $this->request->input('is_active') === '1',
        ], []];
    }

    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $suggestion = null): void
    {
        $this->view($template, [
            'title' => $suggestion === null ? 'Categorievoorstel toevoegen' : 'Categorievoorstel wijzigen',
            'values' => $values,
            'errors' => $errors,
            'suggestion' => $suggestion,
        ], $status);
    }

    private function findOrFail(int $id): array
    {
        return (new CategorySuggestionRepository($this->db()))->find($id)
            ?? $this->notFound('Dit categorievoorstel bestaat niet (meer).');
    }
}
