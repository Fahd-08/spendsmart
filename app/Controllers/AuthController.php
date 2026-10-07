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

/**
 * Inloggen, registreren en uitloggen (FE-01, FE-02).
 */
final class AuthController extends Controller
{
    /** Veldnamen zoals de gebruiker ze ziet in foutmeldingen. */
    private const LABELS = [
        'name' => 'Naam',
        'email' => 'E-mailadres',
        'password' => 'Wachtwoord',
        'practice_data' => 'Bevestiging',
    ];

    /**
     * GET /login: het inlogformulier tonen.
     */
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Inloggen', 'values' => ['email' => ''], 'errors' => []]);
    }

    /**
     * POST /login: inloggegevens controleren en inloggen.
     */
    public function login(): void
    {
        // 1. Is het formulier goed ingevuld?
        $validator = Validator::make($this->request->body(), [
            'email' => 'required|email|max:190',
            'password' => 'required|raw|max:255',
        ], self::LABELS);

        // E-mailadres altijd in kleine letters, zodat STUDENT@... en student@... hetzelfde zijn.
        $email = mb_strtolower(trim($this->request->input('email')));
        $values = ['email' => $email];

        if ($validator->fails()) {
            // 422 = de invoer klopt niet. Formulier opnieuw tonen met de fouten.
            $this->view('auth/login', ['title' => 'Inloggen', 'values' => $values, 'errors' => $validator->errors()], 422);

            return;
        }

        // 2. Te veel mislukte pogingen? Dan tijdelijk blokkeren (tegen wachtwoorden raden).
        $attempts = new LoginAttemptRepository($this->db());
        $ip = $this->request->ip();
        $maxAttempts = (int) config('security.max_login_attempts');
        $lockoutMinutes = (int) config('security.login_lockout_minutes');

        if ($attempts->countRecentFailures($email, $ip, $lockoutMinutes) >= $maxAttempts) {
            // 429 = te veel verzoeken.
            $this->view('auth/login', [
                'title' => 'Inloggen',
                'values' => $values,
                'errors' => ['email' => "Te veel mislukte pogingen. Probeer het over {$lockoutMinutes} minuten opnieuw."],
            ], 429);

            return;
        }

        // 3. Gebruiker zoeken en wachtwoord controleren met de opgeslagen hash.
        $users = new UserRepository($this->db());
        $user = $users->findByEmailWithPassword($email);
        $password = $this->request->input('password');

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            // Mislukte poging onthouden voor de blokkade.
            $attempts->record($email, $ip);
            // Zelfde melding bij onbekend e-mailadres en fout wachtwoord: verraadt niet welke accounts bestaan.
            $this->view('auth/login', [
                'title' => 'Inloggen',
                'values' => $values,
                'errors' => ['email' => 'Het e-mailadres of wachtwoord is onjuist.'],
            ], 422);

            return;
        }

        // 4. Gelukt: oude mislukte pogingen wissen.
        $attempts->clear($email, $ip);

        // Is de hash gemaakt met een oudere/zwakkere instelling? Dan meteen vernieuwen.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        // 5. Inloggen en doorsturen naar de startpagina van de rol.
        Auth::login($user);
        Flash::add('success', 'Welkom terug, ' . $user['name'] . '.');
        $this->redirect(Auth::homePath());
    }

    /**
     * GET /register: het registratieformulier tonen.
     */
    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Account maken',
            'values' => ['name' => '', 'email' => ''],
            'errors' => [],
        ]);
    }

    /**
     * POST /register: een nieuw account maken.
     */
    public function register(): void
    {
        // E-mailadres opschonen (spaties weg, kleine letters) vóór de controle.
        $input = $this->request->body();
        $input['email'] = mb_strtolower(trim($this->request->input('email')));

        // 1. Invoer controleren. practice_data = het vinkje "ik gebruik alleen oefengegevens".
        $validator = Validator::make($input, [
            'name' => 'required|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|password|confirmed',
            'practice_data' => 'required|in:1',
        ], self::LABELS);

        $users = new UserRepository($this->db());

        // 2. Bestaat het e-mailadres al? (Alleen controleren als het e-mailadres zelf geldig is.)
        if (!isset($validator->errors()['email']) && $users->emailExists($input['email'])) {
            $validator->addError('email', 'Er bestaat al een account met dit e-mailadres.');
        }

        if ($validator->fails()) {
            // Formulier opnieuw tonen met de fouten. Wachtwoorden worden bewust niet teruggezet.
            $this->view('auth/register', [
                'title' => 'Account maken',
                'values' => ['name' => $this->request->input('name'), 'email' => $input['email']],
                'errors' => $validator->errors(),
            ], 422);

            return;
        }

        // 3. Account + startcategorieën aanmaken in één databasetransactie (zie RegistrationService).
        $data = $validator->validated();
        $registration = new RegistrationService(
            $this->db(),
            $users,
            new CategoryRepository($this->db()),
            new CategorySuggestionRepository($this->db()),
        );
        $userId = $registration->register($data['name'], $data['email'], $data['password']);

        // 4. Direct inloggen en naar het dashboard.
        Auth::login(['id' => $userId]);
        Flash::add('success', 'Je account is aangemaakt. We hebben alvast een paar categorieën voor je klaargezet.');
        $this->redirect('/dashboard');
    }

    /**
     * POST /logout: uitloggen. Via POST (met CSRF-token), zodat een andere site je niet kan uitloggen.
     */
    public function logout(): void
    {
        Auth::logout();
        Flash::add('success', 'Je bent uitgelogd.');
        $this->redirect('/login');
    }
}
