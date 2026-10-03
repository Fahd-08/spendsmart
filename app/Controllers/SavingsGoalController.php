<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\SavingsGoalRepository;
use App\Support\Money;

/**
 * Spaardoelen beheren en er bedragen aan toevoegen.
 */
final class SavingsGoalController extends Controller
{
    private const LABELS = [
        'name' => 'Naam van het doel',
        'target_amount' => 'Doelbedrag',
        'saved_amount' => 'Al gespaard',
        'target_date' => 'Streefdatum',
        'amount' => 'Bedrag',
    ];

    private const FIELDS = ['name', 'target_amount', 'saved_amount', 'target_date'];

    public function index(): void
    {
        $this->view('goals/index', [
            'title' => 'Spaardoelen',
            'goals' => (new SavingsGoalRepository($this->db()))->allForUser($this->userId()),
        ]);
    }

    public function create(): void
    {
        $this->showForm('goals/create', ['name' => '', 'target_amount' => '', 'saved_amount' => '', 'target_date' => '']);
    }

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

    public function deposit(int $id): void
    {
        $goal = $this->findOrFail($id);
        $validator = Validator::make($this->request->body(), ['amount' => 'required|money'], self::LABELS);

        if ($validator->fails()) {
            Flash::add('error', 'Bedrag niet toegevoegd aan "' . $goal['name'] . '": ' . $validator->errors()['amount']);
            $this->redirect('/goals');
        }

        $amount = $validator->validated()['amount'];
        $goals = new SavingsGoalRepository($this->db());

        if (!$goals->addToSaved($id, $this->userId(), $amount)) {
            Flash::add('error', 'Dit bedrag is te hoog: het gespaarde bedrag mag niet hoger worden dan ' . Money::format(Money::MAX_CENTS) . '.');
            $this->redirect('/goals');
        }

        $newSaved = (int) $goal['saved_cents'] + $amount;
        Flash::add('success', Money::format($amount) . ' toegevoegd aan "' . $goal['name'] . '".');

        if ((int) $goal['saved_cents'] < (int) $goal['target_cents'] && $newSaved >= (int) $goal['target_cents']) {
            Flash::add('success', 'Je hebt je spaardoel "' . $goal['name'] . '" bereikt!');
        }

        $this->redirect('/goals');
    }

    public function destroy(int $id): void
    {
        $goal = $this->findOrFail($id);
        (new SavingsGoalRepository($this->db()))->delete($id, $this->userId());

        Flash::add('success', 'Spaardoel "' . $goal['name'] . '" is verwijderd.');
        $this->redirect('/goals');
    }

    /**
     * @return array{0: array, 1: array<string, string>}
     */
    private function validate(bool $isNew): array
    {
        $validator = Validator::make($this->request->body(), [
            'name' => 'required|max:100',
            'target_amount' => 'required|money',
            'saved_amount' => 'money_zero',
            'target_date' => 'date',
        ], self::LABELS);

        $values = $validator->validated();

        if ($isNew && isset($values['target_date']) && $values['target_date'] < date('Y-m-d')) {
            $validator->addError('target_date', 'De streefdatum mag niet in het verleden liggen.');
        }

        if ($validator->fails()) {
            return [[], $validator->errors()];
        }

        return [[
            'name' => $values['name'],
            'target_cents' => $values['target_amount'],
            'saved_cents' => $values['saved_amount'] ?? 0,
            'target_date' => $values['target_date'],
        ], []];
    }

    private function showForm(string $template, array $values, array $errors = [], int $status = 200, ?array $goal = null): void
    {
        $this->view($template, [
            'title' => $goal === null ? 'Spaardoel toevoegen' : 'Spaardoel wijzigen',
            'values' => $values,
            'errors' => $errors,
            'goal' => $goal,
        ], $status);
    }

    private function findOrFail(int $id): array
    {
        return (new SavingsGoalRepository($this->db()))->findForUser($id, $this->userId())
            ?? $this->notFound('Dit spaardoel bestaat niet of hoort niet bij jouw account.');
    }
}
