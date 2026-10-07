<?php

declare(strict_types=1);

namespace App\Controllers\Content;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\TipRepository;

/**
 * Contentbeheerder: algemene, niet-persoonlijke leerteksten beheren en publiceren (FE-11).
 */
final class TipController extends Controller
{
    /** Veldnamen zoals de gebruiker ze ziet in foutmeldingen. */
    private const LABELS = ['title' => 'Titel', 'body' => 'Tekst'];

    /**
     * GET /content/tips: alle leerteksten, ook concepten.
     */
    public function index(): void
    {
        $this->view('content/tips/index', [
            'title' => 'Leerteksten beheren',
            'tips' => (new TipRepository($this->db()))->all(),
        ]);
    }

    /**
     * GET /content/tips/create: leeg formulier.
     */
    public function create(): void
    {
        $this->showForm('content/tips/create', ['title' => '', 'body' => '', 'is_published' => '']);
    }

    /**
     * POST /content/tips: nieuwe tekst opslaan, als concept of direct gepubliceerd.
     */
    public function store(): void
    {
        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            $this->showForm('content/tips/create', $this->formValues(['title', 'body', 'is_published']), $errors, 422);

            return;
        }

        // De ingelogde contentbeheerder wordt opgeslagen als schrijver.
        (new TipRepository($this->db()))->create($data, $this->userId());

        // Andere melding voor publiceren en voor concept.
        Flash::add('success', $data['is_published']
            ? 'Leertekst "' . $data['title'] . '" is gepubliceerd.'
            : 'Leertekst "' . $data['title'] . '" is als concept opgeslagen.');
        $this->redirect('/content/tips');
    }

    /**
     * GET /content/tips/{id}/edit: formulier met de bestaande tekst.
     */
    public function edit(int $id): void
    {
        $tip = $this->findOrFail($id);

        $this->showForm('content/tips/edit', [
            'title' => $tip['title'],
            'body' => $tip['body'],
            'is_published' => $tip['is_published'] ? '1' : '',
        ], [], 200, $tip);
    }

    /**
     * POST /content/tips/{id}/update: wijzigingen opslaan (ook publiceren of terugzetten naar concept).
     */
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

    /**
     * POST /content/tips/{id}/delete: tekst verwijderen.
     */
    public function destroy(int $id): void
    {
        $tip = $this->findOrFail($id);
        (new TipRepository($this->db()))->delete($id);

        Flash::add('success', 'Leertekst "' . $tip['title'] . '" is verwijderd.');
        $this->redirect('/content/tips');
    }

    /**
     * Controleert het formulier: titel verplicht (max 150), tekst 20 tot 5000 tekens.
     *
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
            // Vinkje "publiceren": '1' als aangevinkt.
            'is_published' => $this->request->input('is_published') === '1',
        ], []];
    }

    /**
     * Toont het formulier voor toevoegen of wijzigen.
     */
    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $tip = null): void
    {
        $this->view($template, [
            'title' => $tip === null ? 'Leertekst toevoegen' : 'Leertekst wijzigen',
            'values' => $values,
            'errors' => $errors,
            'tip' => $tip,
        ], $status);
    }

    /**
     * Zoekt een leertekst, of toont 404.
     */
    private function findOrFail(int $id): array
    {
        return (new TipRepository($this->db()))->find($id)
            ?? $this->notFound('Deze leertekst bestaat niet (meer).');
    }
}
