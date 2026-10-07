<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Support\Role;

/**
 * Eigen accountgegevens bekijken en wijzigen, wachtwoord wijzigen en account verwijderen (FE-03).
 */
final class ProfileController extends Controller
{
    /** Veldnamen zoals de gebruiker ze ziet in foutmeldingen. */
    private const LABELS = [
        'name' => 'Naam',
        'email' => 'E-mailadres',
        'current_password' => 'Huidig wachtwoord',
        'password' => 'Nieuw wachtwoord',
        'delete_password' => 'Wachtwoord',
    ];

    /**
     * GET /profile: profielpagina met de huidige gegevens ingevuld.
     */
    public function edit(): void
    {
        $user = Auth::user();

        $this->showPage(['name' => $user['name'], 'email' => $user['email']]);
    }

    /**
     * POST /profile: naam en e-mailadres opslaan.
     */
    public function update(): void
    {
        $userId = $this->userId();
        $input = $this->request->body();
        $input['email'] = mb_strtolower(trim($this->request->input('email')));

        $validator = Validator::make($input, [
            'name' => 'required|max:100',
            'email' => 'required|email|max:190',
        ], self::LABELS);

        $users = new UserRepository($this->db());

        // Het e-mailadres mag niet al bij een ánder account horen (je eigen adres opnieuw opslaan mag wel).
        if (!isset($validator->errors()['email']) && $users->emailExists($input['email'], $userId)) {
            $validator->addError('email', 'Dit e-mailadres wordt al door een ander account gebruikt.');
        }

        if ($validator->fails()) {
            $this->showPage(['name' => $this->request->input('name'), 'email' => $input['email']], $validator->errors(), 422);

            return;
        }

        $data = $validator->validated();
        $users->updateProfile($userId, $data['name'], $data['email']);

        Flash::add('success', 'Je gegevens zijn opgeslagen.');
        $this->redirect('/profile');
    }

    /**
     * POST /profile/password: wachtwoord wijzigen. Eerst het huidige wachtwoord controleren,
     * zodat iemand die even achter je computer zit niet zomaar je wachtwoord kan veranderen.
     */
    public function updatePassword(): void
    {
        $userId = $this->userId();
        $users = new UserRepository($this->db());

        $validator = Validator::make($this->request->body(), [
            'current_password' => 'required|raw',
            'password' => 'required|password|confirmed',
        ], self::LABELS);

        // Huidig wachtwoord vergelijken met de hash in de database.
        if (!isset($validator->errors()['current_password'])
            && !password_verify($this->request->input('current_password'), (string) $users->passwordHash($userId))) {
            $validator->addError('current_password', 'Je huidige wachtwoord is onjuist.');
        }

        if ($validator->fails()) {
            $user = Auth::user();
            $this->showPage(['name' => $user['name'], 'email' => $user['email']], $validator->errors(), 422);

            return;
        }

        // Nieuw wachtwoord gehasht opslaan en een nieuw sessie-ID maken.
        $users->updatePassword($userId, password_hash($validator->validated()['password'], PASSWORD_DEFAULT));
        Session::regenerate();

        Flash::add('success', 'Je wachtwoord is gewijzigd.');
        $this->redirect('/profile');
    }

    /**
     * POST /profile/delete: gebruiker verwijdert eigen account en alle bijbehorende gegevens.
     */
    public function destroy(): void
    {
        $userId = $this->userId();

        // Extra controle (de route staat dit al alleen toe voor gebruikers): beheerders mogen zichzelf niet verwijderen.
        if (!Auth::hasRole(Role::USER)) {
            Flash::add('error', 'Een contentbeheerdersaccount kan niet zelf worden verwijderd. Neem contact op met MoneyMinds.');
            $this->redirect('/profile');
        }

        $users = new UserRepository($this->db());

        // Ter bevestiging moet het wachtwoord worden ingevuld.
        if (!password_verify($this->request->input('delete_password'), (string) $users->passwordHash($userId))) {
            $user = Auth::user();
            $this->showPage(
                ['name' => $user['name'], 'email' => $user['email']],
                ['delete_password' => 'Het wachtwoord is onjuist. Je account is niet verwijderd.'],
                422
            );

            return;
        }

        // Account met alle transacties, categorieën en spaardoelen verwijderen, daarna uitloggen.
        $users->delete($userId);
        Auth::logout();

        Flash::add('success', 'Je account en al je gegevens zijn verwijderd.');
        $this->redirect('/');
    }

    /**
     * Toont de profielpagina (gedeeld door alle methodes hierboven).
     */
    private function showPage(array $values, array $errors = [], int $status = 200): void
    {
        $this->view('profile/edit', [
            'title' => 'Profiel',
            'user' => Auth::user(),
            'values' => $values,
            'errors' => $errors,
            'canDeleteAccount' => Auth::hasRole(Role::USER), // knop "account verwijderen" alleen voor gebruikers
        ], $status);
    }
}
