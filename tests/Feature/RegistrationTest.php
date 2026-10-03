<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\CategoryRepository;
use App\Repositories\CategorySuggestionRepository;
use App\Repositories\UserRepository;
use App\Services\RegistrationService;
use PDOException;
use Tests\FeatureTestCase;

/**
 * FE-01: Account maken.
 *
 * @group FE-01
 */
final class RegistrationTest extends FeatureTestCase
{
    private const VALID = [
        'name' => 'Nieuwe Student',
        'email' => 'nieuw@spendsmart.test',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
        'practice_data' => '1',
    ];

    public function test_registratiepagina_is_bereikbaar(): void
    {
        $this->get('/register')->assertOk()->assertSee('Account maken');
    }

    public function test_bezoeker_kan_een_account_maken(): void
    {
        $response = $this->post('/register', self::VALID);

        $response->assertRedirect('/dashboard');
        $this->assertFlash('success', 'Je account is aangemaakt');

        $user = $this->row('SELECT * FROM users WHERE email = ?', ['nieuw@spendsmart.test']);
        $this->assertNotNull($user);
        $this->assertSame('user', $user['role']);
        $this->assertSame((int) $user['id'], $this->loggedInUserId(), 'Na registreren ben je direct ingelogd.');

        // Wachtwoord is gehasht opgeslagen (TE-04), nooit als leesbare tekst.
        $this->assertNotSame('Geheim123', $user['password_hash']);
        $this->assertTrue(password_verify('Geheim123', $user['password_hash']));

        $this->follow($response)->assertOk()->assertSee('Hoi Nieuwe Student');
    }

    public function test_nieuw_account_krijgt_startcategorieen_van_actieve_voorstellen(): void
    {
        $this->post('/register', self::VALID);

        $userId = $this->loggedInUserId();
        $activeSuggestions = $this->countRows('category_suggestions', 'is_active = 1');

        $this->assertSame($activeSuggestions, $this->countRows('categories', 'user_id = ?', [$userId]));
        $this->assertSame(0, $this->countRows('categories', "user_id = ? AND name = 'Studiekosten'", [$userId]), 'Inactief voorstel wordt niet toegevoegd.');
    }

    public function test_unhappy_emailadres_bestaat_al(): void
    {
        $usersBefore = $this->countRows('users');

        $this->post('/register', ['email' => 'student@spendsmart.test'] + self::VALID)
            ->assertStatus(422)
            ->assertSee('Er bestaat al een account met dit e-mailadres.');

        $this->assertSame($usersBefore, $this->countRows('users'));
        $this->assertNull($this->loggedInUserId());
    }

    public function test_unhappy_zwak_wachtwoord(): void
    {
        $this->post('/register', ['password' => 'geheim', 'password_confirmation' => 'geheim'] + self::VALID)
            ->assertStatus(422)
            ->assertSee('Het wachtwoord moet minimaal 8 tekens hebben, met minstens één letter en één cijfer.');

        $this->assertSame(0, $this->countRows('users', 'email = ?', ['nieuw@spendsmart.test']));
    }

    public function test_unhappy_wachtwoorden_komen_niet_overeen(): void
    {
        $this->post('/register', ['password_confirmation' => 'Geheim124'] + self::VALID)
            ->assertStatus(422)
            ->assertSee('De twee ingevulde wachtwoorden komen niet overeen.');
    }

    public function test_unhappy_zonder_bevestiging_oefengegevens(): void
    {
        $data = self::VALID;
        unset($data['practice_data']);

        $this->post('/register', $data)->assertStatus(422)->assertSee('Bevestiging is verplicht.');
    }

    public function test_randgeval_emailadres_met_hoofdletters_en_spaties_wordt_genormaliseerd(): void
    {
        $this->post('/register', ['email' => '  NIEUW@SpendSmart.TEST '] + self::VALID)->assertRedirect('/dashboard');

        $this->assertSame(1, $this->countRows('users', 'email = ?', ['nieuw@spendsmart.test']));
    }

    public function test_randgeval_zelfde_emailadres_met_hoofdletters_bestaat_al(): void
    {
        $this->post('/register', ['email' => 'STUDENT@spendsmart.test'] + self::VALID)
            ->assertStatus(422)
            ->assertSee('Er bestaat al een account met dit e-mailadres.');
    }

    public function test_randgeval_naam_van_precies_100_tekens_wel_101_niet(): void
    {
        $this->post('/register', ['name' => str_repeat('a', 101)] + self::VALID)
            ->assertStatus(422)
            ->assertSee('Naam mag maximaal 100 tekens bevatten.');

        $this->post('/register', ['name' => str_repeat('a', 100)] + self::VALID)->assertRedirect('/dashboard');
    }

    public function test_randgeval_mislukte_registratie_laat_geen_halve_gegevens_achter(): void
    {
        $service = new RegistrationService(
            $this->db(),
            new UserRepository($this->db()),
            new CategoryRepository($this->db()),
            new CategorySuggestionRepository($this->db()),
        );
        $usersBefore = $this->countRows('users');
        $categoriesBefore = $this->countRows('categories');

        try {
            // Bestaand e-mailadres: de database weigert dit (unieke sleutel).
            $service->register('Dubbel', 'student@spendsmart.test', 'Geheim123');
            $this->fail('Registratie met bestaand e-mailadres had moeten mislukken.');
        } catch (PDOException) {
            // verwacht
        }

        $this->assertSame($usersBefore, $this->countRows('users'));
        $this->assertSame($categoriesBefore, $this->countRows('categories'));
        $this->assertFalse($this->db()->inTransaction(), 'De databasetransactie is teruggedraaid.');
    }

    public function test_ingelogde_gebruiker_wordt_van_registratiepagina_doorgestuurd(): void
    {
        $this->actingAs(self::SAM_ID)->get('/register')->assertRedirect('/dashboard');
    }
}
