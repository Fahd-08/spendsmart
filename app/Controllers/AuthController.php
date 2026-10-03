<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\CategorySuggestionRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\RegistrationService;

final class AuthController extends Controller
{
    private const LABELS = [
        'name' => 'Naam',
        'email' => 'E-mailadres',
        'password' => 'Wachtwoord',
        'practice_data' => 'Bevestiging',
    ];

    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Inloggen', 'values' => ['email' => ''], 'errors' => []]);
    }

    public function login(): void
    {
        $validator = Validator::make($this->request->body(), [
            'email' => 'required|email|max:190',
            'password' => 'required|raw|max:255',
        ], self::LABELS);

        $email = mb_strtolower(trim($this->request->input('email')));
        $values = ['email' => $email];

        if ($validator->fails()) {
            $this->view('auth/login', ['title' => 'Inloggen', 'values' => $values, 'errors' => $validator->errors()], 422);

            return;
        }

        $attempts = new LoginAttemptRepository($this->db());
        $ip = $this->request->ip();
        $maxAttempts = (int) config('security.max_login_attempts');
        $lockoutMinutes = (int) config('security.login_lockout_minutes');

        if ($attempts->countRecentFailures($email, $ip, $lockoutMinutes) >= $maxAttempts) {
            $this->view('auth/login', [
                'title' => 'Inloggen',
                'values' => $values,
                'errors' => ['email' => "Te veel mislukte pogingen. Probeer het over {$lockoutMinutes} minuten opnieuw."],
            ], 429);

            return;
        }

        $users = new UserRepository($this->db());
        $user = $users->findByEmailWithPassword($email);
        $password = $this->request->input('password');

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $attempts->record($email, $ip);
            // Zelfde melding bij onbekend e-mailadres en fout wachtwoord: verraadt niet welke accounts bestaan.
            $this->view('auth/login', [
                'title' => 'Inloggen',
                'values' => $values,
                'errors' => ['email' => 'Het e-mailadres of wachtwoord is onjuist.'],
            ], 422);

            return;
        }

        $attempts->clear($email, $ip);

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::login($user);
        Flash::add('success', 'Welkom terug, ' . $user['name'] . '.');
        $this->redirect(Auth::homePath());
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Account maken',
            'values' => ['name' => '', 'email' => ''],
            'errors' => [],
        ]);
    }

    public function register(): void
    {
        $input = $this->request->body();
        $input['email'] = mb_strtolower(trim($this->request->input('email')));

        $validator = Validator::make($input, [
            'name' => 'required|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|password|confirmed',
            'practice_data' => 'required|in:1',
        ], self::LABELS);

        $users = new UserRepository($this->db());

        if (!isset($validator->errors()['email']) && $users->emailExists($input['email'])) {
            $validator->addError('email', 'Er bestaat al een account met dit e-mailadres.');
        }

        if ($validator->fails()) {
            $this->view('auth/register', [
                'title' => 'Account maken',
                'values' => ['name' => $this->request->input('name'), 'email' => $input['email']],
                'errors' => $validator->errors(),
            ], 422);

            return;
        }

        $data = $validator->validated();
        $registration = new RegistrationService(
            $this->db(),
            $users,
            new CategoryRepository($this->db()),
            new CategorySuggestionRepository($this->db()),
        );
        $userId = $registration->register($data['name'], $data['email'], $data['password']);

        Auth::login(['id' => $userId]);
        Flash::add('success', 'Je account is aangemaakt. We hebben alvast een paar categorieën voor je klaargezet.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::add('success', 'Je bent uitgelogd.');
        $this->redirect('/login');
    }
}
