<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-08: Spaardoelen beheren en er bedragen aan toevoegen.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-08
 */
final class SavingsGoalTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(self::SAM_ID);
    }

    public function test_overzicht_toont_eigen_spaardoelen(): void
    {
        $this->get('/goals')
            ->assertOk()
            ->assertSee('Nieuwe laptop')
            ->assertSee('Rijlessen')
            ->assertDontSee('Buffer');

        $this->get('/goals/create')->assertOk();
    }

    public function test_gebruiker_maakt_spaardoel(): void
    {
        $nextYear = date('Y-m-d', strtotime('+1 year'));

        $this->post('/goals', ['name' => 'Fiets', 'target_amount' => '450', 'saved_amount' => '50,00', 'target_date' => $nextYear])
            ->assertRedirect('/goals');

        $this->assertFlash('success', 'Spaardoel "Fiets" is aangemaakt.');
        $this->assertSame(
            ['target_cents' => 45000, 'saved_cents' => 5000, 'target_date' => $nextYear],
            $this->row('SELECT target_cents, saved_cents, target_date FROM savings_goals WHERE name = ?', ['Fiets'])
        );
    }

    public function test_randgeval_spaardoel_zonder_streefdatum_en_gespaard_bedrag(): void
    {
        $this->post('/goals', ['name' => 'Ooit', 'target_amount' => '100'])->assertRedirect('/goals');

        $this->assertSame(
            ['saved_cents' => 0, 'target_date' => null],
            $this->row('SELECT saved_cents, target_date FROM savings_goals WHERE name = ?', ['Ooit'])
        );
    }

    public function test_unhappy_doelbedrag_nul_en_streefdatum_in_het_verleden(): void
    {
        $this->post('/goals', ['name' => 'Fout', 'target_amount' => '0', 'target_date' => date('Y-m-d', strtotime('-1 day'))])
            ->assertStatus(422)
            ->assertSee('Doelbedrag moet groter zijn dan € 0,00.')
            ->assertSee('De streefdatum mag niet in het verleden liggen.');

        $this->assertSame(0, $this->countRows('savings_goals', 'name = ?', ['Fout']));
    }

    public function test_randgeval_streefdatum_vandaag_is_toegestaan(): void
    {
        $this->post('/goals', ['name' => 'Vandaag', 'target_amount' => '10', 'target_date' => date('Y-m-d')])->assertRedirect('/goals');
    }

    public function test_gebruiker_wijzigt_spaardoel(): void
    {
        $id = $this->goalId('Nieuwe laptop');

        $this->get("/goals/{$id}/edit")->assertOk()->assertSee('900,00');

        // Bij wijzigen mag een oude streefdatum blijven staan.
        $this->post("/goals/{$id}/update", ['name' => 'Laptop', 'target_amount' => '1000', 'saved_amount' => '345', 'target_date' => '2020-01-01'])
            ->assertRedirect('/goals');

        $this->assertSame(
            ['name' => 'Laptop', 'target_cents' => 100000, 'target_date' => '2020-01-01'],
            $this->row('SELECT name, target_cents, target_date FROM savings_goals WHERE id = ?', [$id])
        );
    }

    public function test_unhappy_wijzigen_met_lege_naam(): void
    {
        $id = $this->goalId('Nieuwe laptop');

        $this->post("/goals/{$id}/update", ['name' => '', 'target_amount' => '900'])
            ->assertStatus(422)
            ->assertSee('Naam van het doel is verplicht.');
    }

    public function test_bedrag_toevoegen_aan_spaardoel(): void
    {
        $id = $this->goalId('Nieuwe laptop');

        $this->post("/goals/{$id}/deposit", ['amount' => '55,50'])->assertRedirect('/goals');

        $this->assertFlash('success', '€ 55,50 toegevoegd aan "Nieuwe laptop".');
        $this->assertSame(40050, (int) $this->value('SELECT saved_cents FROM savings_goals WHERE id = ?', [$id]));
    }

    public function test_randgeval_doel_precies_bereikt_geeft_felicitatie(): void
    {
        $id = $this->goalId('Nieuwe laptop'); // 345,00 van 900,00

        $this->post("/goals/{$id}/deposit", ['amount' => '555'])->assertRedirect('/goals');

        $this->assertFlash('success', 'Je hebt je spaardoel "Nieuwe laptop" bereikt!');
    }

    public function test_unhappy_ongeldig_bedrag_toevoegen(): void
    {
        $id = $this->goalId('Nieuwe laptop');

        $this->post("/goals/{$id}/deposit", ['amount' => '-10'])->assertRedirect('/goals');

        $this->assertFlash('error', 'Bedrag niet toegevoegd aan "Nieuwe laptop"');
        $this->assertSame(34500, (int) $this->value('SELECT saved_cents FROM savings_goals WHERE id = ?', [$id]));
    }

    public function test_randgeval_gespaard_bedrag_boven_het_maximum(): void
    {
        $id = $this->goalId('Nieuwe laptop');
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec("UPDATE savings_goals SET saved_cents = 999999000 WHERE id = {$id}");

        $this->post("/goals/{$id}/deposit", ['amount' => '10'])->assertRedirect('/goals');

        $this->assertFlash('error', 'Dit bedrag is te hoog');
        $this->assertSame(999999000, (int) $this->value('SELECT saved_cents FROM savings_goals WHERE id = ?', [$id]));
    }

    public function test_spaardoel_verwijderen(): void
    {
        $id = $this->goalId('Rijlessen');

        $this->post("/goals/{$id}/delete")->assertRedirect('/goals');

        $this->assertSame(0, $this->countRows('savings_goals', 'id = ?', [$id]));
    }

    public function test_unhappy_spaardoel_van_een_ander(): void
    {
        $id = (int) $this->value('SELECT id FROM savings_goals WHERE user_id = ?', [self::SANNE_ID]);

        $this->get("/goals/{$id}/edit")->assertStatus(404);
        $this->post("/goals/{$id}/deposit", ['amount' => '10'])->assertStatus(404);
        $this->post("/goals/{$id}/delete")->assertStatus(404);

        $this->assertSame(25000, (int) $this->value('SELECT saved_cents FROM savings_goals WHERE id = ?', [$id]));
    }

    public function test_randgeval_lege_lijst_toont_uitleg(): void
    {
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec('DELETE FROM savings_goals WHERE user_id = ' . self::SAM_ID);

        $this->get('/goals')
            ->assertOk()
            ->assertSee('Je hebt nog geen spaardoelen. Voeg er een toe om je voortgang te volgen.');
    }

    private function goalId(string $name): int
    {
        return (int) $this->value('SELECT id FROM savings_goals WHERE user_id = ? AND name = ?', [self::SAM_ID, $name]);
    }
}
