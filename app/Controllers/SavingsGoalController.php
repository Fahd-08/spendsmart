<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\SavingsGoalRepository;
use App\Support\Money;

/**
 * Spaardoelen beheren en er bedragen aan toevoegen (FE-08).
 */
final class SavingsGoalController extends Controller
{
    /** Veldnamen zoals de gebruiker ze ziet in foutmeldingen. */
    private const LABELS = [
        'name' => 'Naam van het doel',
        'target_amount' => 'Doelbedrag',
        'saved_amount' => 'Al gespaard',
        'target_date' => 'Streefdatum',
        'amount' => 'Bedrag',
    ];

    /** Velden van het formulier. */
    private const FIELDS = ['name', 'target_amount', 'saved_amount', 'target_date'];

    /**
     * GET /goals: alle spaardoelen van de gebruiker.
     */
    public function index(): void
    {
        $this->view('goals/index', [
            'title' => 'Spaardoelen',
            'goals' => (new SavingsGoalRepository($this->db()))->allForUser($this->userId()),
        ]);
    }

    /**
     * GET /goals/create: leeg formulier.
     */
    public function create(): void
    {
        $this->showForm('goals/create', ['name' => '', 'target_amount' => '', 'saved_amount' => '', 'target_date' => '']);
    }

    /**
     * POST /goals: nieuw spaardoel opslaan.
     */
    public function store(): void
    {
        [$data, $errors] = $this->validate(true);

        if ($errors !== []) {
            $this->showForm('goals/create', $this->formValues(self::FIELDS), $errors, 422);

            return;
        }

        (new SavingsGoalRepository($this->db()))->create($this->userId(), $data);

        Flash::add('success', 'Spaardoel "' . $data['name'] . '" is aangemaakt.');
        $this->redirect('/goals');
    }

    /**
     * GET /goals/{id}/edit: formulier met de bestaande gegevens.
     */
    public function edit(int $id): void
    {
        $goal = $this->findOrFail($id);

        $this->showForm('goals/edit', [
            'name' => $goal['name'],
            'target_amount' => Money::toInput((int) $goal['target_cents']),
            'saved_amount' => Money::toInput((int) $goal['saved_cents']),
            'target_date' => (string) $goal['target_date'],
        ], [], 200, $goal);
    }

    /**
     * POST /goals/{id}/update: wijzigingen opslaan.
     */
    public function update(int $id): void
    {
        $goal = $this->findOrFail($id);
        [$data, $errors] = $this->validate(false);

        if ($errors !== []) {
            $this->showForm('goals/edit', $this->formValues(self::FIELDS), $errors, 422, $goal);

            return;
        }

        (new SavingsGoalRepository($this->db()))->update($id, $this->userId(), $data);

        Flash::add('success', 'Spaardoel "' . $data['name'] . '" is bijgewerkt.');
        $this->redirect('/goals');
    }

    /**
     * POST /goals/{id}/deposit: een bedrag toevoegen aan het gespaarde bedrag.
     */
    public function deposit(int $id): void
    {
        $goal = $this->findOrFail($id);
        $validator = Validator::make($this->request->body(), ['amount' => 'required|money'], self::LABELS);

        // Ongeldig bedrag: foutmelding en terug naar het overzicht.
        if ($validator->fails()) {
            Flash::add('error', 'Bedrag niet toegevoegd aan "' . $goal['name'] . '": ' . $validator->errors()['amount']);
            $this->redirect('/goals');
        }

        $amount = $validator->validated()['amount'];
        $goals = new SavingsGoalRepository($this->db());

        // De database telt op en weigert als het maximum wordt overschreden.
        if (!$goals->addToSaved($id, $this->userId(), $amount)) {
            Flash::add('error', 'Dit bedrag is te hoog: het gespaarde bedrag mag niet hoger worden dan ' . Money::format(Money::MAX_CENTS) . '.');
            $this->redirect('/goals');
        }

        $newSaved = (int) $goal['saved_cents'] + $amount;
        Flash::add('success', Money::format($amount) . ' toegevoegd aan "' . $goal['name'] . '".');

        // Door deze storting is het doel net bereikt? Dan een felicitatie.
        if ((int) $goal['saved_cents'] < (int) $goal['target_cents'] && $newSaved >= (int) $goal['target_cents']) {
            Flash::add('success', 'Je hebt je spaardoel "' . $goal['name'] . '" bereikt!');
        }

        $this->redirect('/goals');
    }

    /**
     * POST /goals/{id}/delete: spaardoel verwijderen.
     */
    public function destroy(int $id): void
    {
        $goal = $this->findOrFail($id);
        (new SavingsGoalRepository($this->db()))->delete($id, $this->userId());

        Flash::add('success', 'Spaardoel "' . $goal['name'] . '" is verwijderd.');
        $this->redirect('/goals');
    }

    /**
     * Controleert het formulier.
     *
     * @param bool $isNew true bij aanmaken: dan mag de streefdatum niet in het verleden liggen.
     *                    Bij wijzigen mag een oude datum blijven staan.
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(bool $isNew): array
    {
        $validator = Validator::make($this->request->body(), [
            'name' => 'required|max:100',
            'target_amount' => 'required|money', // doelbedrag moet groter dan 0 zijn
            'saved_amount' => 'money_zero',       // optioneel, 0 mag
            'target_date' => 'date',              // optioneel
        ], self::LABELS);

        $values = $validator->validated();

        // Datums als tekst 'JJJJ-MM-DD' kun je gewoon vergelijken: '2026-01-01' < '2026-10-07'.
        if ($isNew && isset($values['target_date']) && $values['target_date'] < date('Y-m-d')) {
            $validator->addError('target_date', 'De streefdatum mag niet in het verleden liggen.');
        }

        if ($validator->fails()) {
            return [[], $validator->errors()];
        }

        return [[
            'name' => $values['name'],
            'target_cents' => $values['target_amount'],
            'saved_cents' => $values['saved_amount'] ?? 0, // niets ingevuld = 0
            'target_date' => $values['target_date'],
        ], []];
    }

    /**
     * Toont het formulier voor toevoegen of wijzigen.
     */
    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $goal = null): void
    {
        $this->view($template, [
            'title' => $goal === null ? 'Spaardoel toevoegen' : 'Spaardoel wijzigen',
            'values' => $values,
            'errors' => $errors,
            'goal' => $goal,
        ], $status);
    }

    /**
     * Zoekt een spaardoel van de ingelogde gebruiker, of toont 404.
     */
    private function findOrFail(int $id): array
    {
        return (new SavingsGoalRepository($this->db()))->findForUser($id, $this->userId())
            ?? $this->notFound('Dit spaardoel bestaat niet of hoort niet bij jouw account.');
    }
}
