<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-03: Eigen gegevens veilig beheren (wijzigen, wachtwoord wijzigen, account verwijderen).
 *
 * @group FE-03
 */
final class ProfileTest extends FeatureTestCase
{
    public function test_profielpagina_toont_eigen_gegevens(): void
    {
        $this->actingAs(self::SAM_ID)->get('/profile')
            ->assertOk()
            ->assertSee('Sam Student')
            ->assertSee('student@spendsmart.test');
    }

    public function test_gebruiker_wijzigt_naam_en_emailadres(): void
    {
        $this->actingAs(self::SAM_ID)
            ->post('/profile', ['name' => 'Sam de Student', 'email' => 'Sam.Nieuw@spendsmart.test'])
            ->assertRedirect('/profile');

        $this->assertFlash('success', 'Je gegevens zijn opgeslagen.');
        $user = $this->row('SELECT name, email FROM users WHERE id = ?', [self::SAM_ID]);
        $this->assertSame(['name' => 'Sam de Student', 'email' => 'sam.nieuw@spendsmart.test'], $user);
    }

    public function test_randgeval_eigen_emailadres_opnieuw_opslaan_mag(): void
    {
        $this->actingAs(self::SAM_ID)
            ->post('/profile', ['name' => 'Sam', 'email' => 'student@spendsmart.test'])
            ->assertRedirect('/profile');
    }

    public function test_unhappy_emailadres_van_een_ander_account(): void
    {
        $this->actingAs(self::SAM_ID)
            ->post('/profile', ['name' => 'Sam', 'email' => 'sanne@spendsmart.test'])
            ->assertStatus(422)
            ->assertSee('Dit e-mailadres wordt al door een ander account gebruikt.');

        $this->assertSame('student@spendsmart.test', $this->value('SELECT email FROM users WHERE id = ?', [self::SAM_ID]));
    }

    public function test_gebruiker_wijzigt_wachtwoord(): void
    {
        $this->actingAs(self::SAM_ID)->post('/profile/password', [
            'current_password' => self::PASSWORD,
            'password' => 'NieuwWachtwoord1',
            'password_confirmation' => 'NieuwWachtwoord1',
        ])->assertRedirect('/profile');

        $hash = $this->value('SELECT password_hash FROM users WHERE id = ?', [self::SAM_ID]);
        $this->assertTrue(password_verify('NieuwWachtwoord1', $hash));
        $this->assertSame(self::SAM_ID, $this->loggedInUserId(), 'Gebruiker blijft ingelogd.');
    }

    public function test_unhappy_huidig_wachtwoord_onjuist(): void
    {
        $hashBefore = $this->value('SELECT password_hash FROM users WHERE id = ?', [self::SAM_ID]);

        $this->actingAs(self::SAM_ID)->post('/profile/password', [
            'current_password' => 'Fout12345',
            'password' => 'NieuwWachtwoord1',
            'password_confirmation' => 'NieuwWachtwoord1',
        ])->assertStatus(422)->assertSee('Je huidige wachtwoord is onjuist.');

        $this->assertSame($hashBefore, $this->value('SELECT password_hash FROM users WHERE id = ?', [self::SAM_ID]));
    }

    public function test_gebruiker_verwijdert_account_met_alle_gegevens(): void
    {
        $response = $this->actingAs(self::SAM_ID)->post('/profile/delete', ['delete_password' => self::PASSWORD]);

        $response->assertRedirect('/');
        $this->assertNull($this->loggedInUserId());
        $this->assertSame(0, $this->countRows('users', 'id = ?', [self::SAM_ID]));
        $this->assertSame(0, $this->countRows('transactions', 'user_id = ?', [self::SAM_ID]));
        $this->assertSame(0, $this->countRows('categories', 'user_id = ?', [self::SAM_ID]));
        $this->assertSame(0, $this->countRows('savings_goals', 'user_id = ?', [self::SAM_ID]));

        // Gegevens van andere gebruikers blijven bestaan.
        $this->assertSame(3, $this->countRows('transactions', 'user_id = ?', [self::SANNE_ID]));
        $this->follow($response)->assertSee('Je account en al je gegevens zijn verwijderd.');
    }

    public function test_unhappy_account_verwijderen_met_fout_wachtwoord(): void
    {
        $this->actingAs(self::SAM_ID)->post('/profile/delete', ['delete_password' => 'Fout12345'])
            ->assertStatus(422)
            ->assertSee('Het wachtwoord is onjuist. Je account is niet verwijderd.');

        $this->assertSame(1, $this->countRows('users', 'id = ?', [self::SAM_ID]));
    }

    public function test_randgeval_contentbeheerder_kan_eigen_account_niet_verwijderen(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->post('/profile/delete', ['delete_password' => self::PASSWORD])
            ->assertStatus(403);

        $this->assertSame(1, $this->countRows('users', 'id = ?', [self::CONTENT_MANAGER_ID]));
    }

    public function test_contentbeheerder_kan_eigen_profiel_wel_wijzigen(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/profile', ['name' => 'Charlie', 'email' => 'content@spendsmart.test'])
            ->assertRedirect('/profile');
    }
}
