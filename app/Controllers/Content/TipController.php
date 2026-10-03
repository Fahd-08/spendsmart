<?php

declare(strict_types=1);

namespace App\Controllers\Content;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\TipRepository;

/**
 * Contentbeheerder: algemene, niet-persoonlijke leerteksten beheren en publiceren.
 */
final class TipController extends Controller
{
    private const LABELS = ['title' => 'Titel', 'body' => 'Tekst'];

    public function index(): void
    {
        $this->view('content/tips/index', [
            'title' => 'Leerteksten beheren',
            'tips' => (new TipRepository($this->db()))->all(),
        ]);
    }

    public function create(): void
    {
        $this->showForm('content/tips/create', ['title' => '', 'body' => '', 'is_published' => '']);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            $this->showForm('content/tips/create', $this->formValues(['title', 'body', 'is_published']), $errors, 422);

            return;
        }

        (new TipRepository($this->db()))->create($data, $this->userId());

        Flash::add('success', $data['is_published']
            ? 'Leertekst "' . $data['title'] . '" is gepubliceerd.'
            : 'Leertekst "' . $data['title'] . '" is als concept opgeslagen.');
        $this->redirect('/content/tips');
    }

    public function edit(int $id): void
    {
        $tip = $this->findOrFail($id);

        $this->showForm('content/tips/edit', [
            'title' => $tip['title'],
            'body' => $tip['body'],
            'is_published' => $tip['is_published'] ? '1' : '',
        ], [], 200, $tip);
    }

    public function update(int $id): void
    {
        $tip = $this->findOrFail($id);
        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            $this->showForm('content/tips/edit', $this->formValues(['title', 'body', 'is_published']), $errors, 422, $tip);

            return;
        }

        (new TipRepository($this->db()))->update($id, $data);

        Flash::add('success', 'Leertekst "' . $data['title'] . '" is bijgewerkt.');
        $this->redirect('/content/tips');
    }

    public function destroy(int $id): void
    {
        $tip = $this->findOrFail($id);
        (new TipRepository($this->db()))->delete($id);

        Flash::add('success', 'Leertekst "' . $tip['title'] . '" is verwijderd.');
        $this->redirect('/content/tips');
    }

    /**
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(): array
    {
        $validator = Validator::make($this->request->body(), [
            'title' => 'required|max:150',
            'body' => 'required|min:20|max:5000',
        ], self::LABELS);

        if ($validator->fails()) {
            return [[], $validator->errors()];
        }

        $values = $validator->validated();

        return [[
            'title' => $values['title'],
            'body' => $values['body'],
            'is_published' => $this->request->input('is_published') === '1',
        ], []];
    }

    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $tip = null): void
    {
        $this->view($template, [
            'title' => $tip === null ? 'Leertekst toevoegen' : 'Leertekst wijzigen',
            'values' => $values,
            'errors' => $errors,
            'tip' => $tip,
        ], $status);
    }

    private function findOrFail(int $id): array
    {
        return (new TipRepository($this->db()))->find($id)
            ?? $this->notFound('Deze leertekst bestaat niet (meer).');
    }
}
