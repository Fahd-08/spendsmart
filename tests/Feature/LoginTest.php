<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-02: Inloggen en uitloggen, inclusief blokkade na te veel mislukte pogingen.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-02
 */
final class LoginTest extends FeatureTestCase
{
    public function test_inlogpagina_is_bereikbaar(): void
    {
        $this->get('/login')->assertOk()->assertSee('Inloggen');
    }

    public function test_gebruiker_logt_in_en_komt_op_dashboard(): void
    {
        $response = $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD]);

        $response->assertRedirect('/dashboard');
        $this->assertSame(self::SAM_ID, $this->loggedInUserId());
        $this->follow($response)->assertSee('Welkom terug, Sam Student.');
    }

    public function test_contentbeheerder_logt_in_en_komt_op_statistieken(): void
    {
        $this->post('/login', ['email' => 'content@spendsmart.test', 'password' => self::PASSWORD])
            ->assertRedirect('/content/statistics');
    }

    public function test_unhappy_fout_wachtwoord(): void
    {
        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => 'Fout12345'])
            ->assertStatus(422)
            ->assertSee('Het e-mailadres of wachtwoord is onjuist.');

        $this->assertNull($this->loggedInUserId());
        $this->assertSame(1, $this->countRows('login_attempts', 'email = ?', ['student@spendsmart.test']));
    }

    public function test_unhappy_onbekend_emailadres_geeft_dezelfde_melding(): void
    {
        $this->post('/login', ['email' => 'bestaatniet@spendsmart.test', 'password' => self::PASSWORD])
            ->assertStatus(422)
            ->assertSee('Het e-mailadres of wachtwoord is onjuist.');
    }

    public function test_unhappy_leeg_formulier(): void
    {
        $this->post('/login', [])
            ->assertStatus(422)
            ->assertSee('E-mailadres is verplicht.')
            ->assertSee('Wachtwoord is verplicht.');
    }

    public function test_randgeval_na_5_mislukte_pogingen_wordt_inloggen_geblokkeerd(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => 'Fout12345'])->assertStatus(422);
        }

        // Ook met het juiste wachtwoord lukt het nu niet meer.
        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD])
            ->assertStatus(429)
            ->assertSee('Te veel mislukte pogingen. Probeer het over 15 minuten opnieuw.');

        $this->assertNull($this->loggedInUserId());
    }

    public function test_randgeval_4_mislukte_pogingen_blokkeren_nog_niet(): void
    {
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => 'Fout12345']);
        }

        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD])->assertRedirect('/dashboard');
        $this->assertSame(0, $this->countRows('login_attempts'), 'Na succesvol inloggen worden de pogingen gewist.');
    }

    public function test_randgeval_oude_mislukte_pogingen_tellen_niet_mee(): void
    {
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec(
            "INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES
             ('student@spendsmart.test', '127.0.0.1', NOW() - INTERVAL 16 MINUTE),
             ('student@spendsmart.test', '127.0.0.1', NOW() - INTERVAL 16 MINUTE),
             ('student@spendsmart.test', '127.0.0.1', NOW() - INTERVAL 16 MINUTE),
             ('student@spendsmart.test', '127.0.0.1', NOW() - INTERVAL 16 MINUTE),
             ('student@spendsmart.test', '127.0.0.1', NOW() - INTERVAL 16 MINUTE)"
        );

        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD])->assertRedirect('/dashboard');
    }

    public function test_verouderde_wachtwoordhash_wordt_bij_inloggen_vernieuwd(): void
    {
        $oldHash = password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$oldHash, self::SAM_ID]);

        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD])->assertRedirect('/dashboard');

        $newHash = $this->value('SELECT password_hash FROM users WHERE id = ?', [self::SAM_ID]);
        $this->assertNotSame($oldHash, $newHash);
        $this->assertTrue(password_verify(self::PASSWORD, $newHash));
    }

    public function test_gebruiker_logt_uit(): void
    {
        $response = $this->actingAs(self::SAM_ID)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertNull($this->loggedInUserId());
        $this->follow($response)->assertSee('Je bent uitgelogd.');
    }

    public function test_unhappy_formulier_zonder_csrf_token_wordt_geweigerd(): void
    {
        $this->post('/login', ['email' => 'student@spendsmart.test', 'password' => self::PASSWORD], false)
            ->assertStatus(419)
            ->assertSee('Je sessie is verlopen');

        $this->assertNull($this->loggedInUserId());
    }

    public function test_niet_ingelogd_wordt_doorgestuurd_naar_inloggen(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->follow($response)->assertSee('Log in om deze pagina te bekijken.');
    }

    public function test_randgeval_sessie_van_verwijderd_account_wordt_opgeruimd(): void
    {
        $this->actingAs(self::SAM_ID);
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec('DELETE FROM transactions WHERE user_id = ' . self::SAM_ID);
        $this->db()->exec('DELETE FROM users WHERE id = ' . self::SAM_ID);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertNull($this->loggedInUserId());
    }
}
