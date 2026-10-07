<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-07: Eigen categorieën beheren, met een maandlimiet per uitgavencategorie.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-07
 */
final class CategoryTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(self::SAM_ID);
    }

    public function test_overzicht_toont_eigen_categorieen(): void
    {
        $this->get('/categories')
            ->assertOk()
            ->assertSee('Boodschappen')
            ->assertSee('Cadeaus')
            ->assertDontSee('Huur');

        $this->get('/categories/create')->assertOk();
    }

    public function test_gebruiker_maakt_uitgavencategorie_met_limiet(): void
    {
        $this->post('/categories', ['name' => 'Sport', 'type' => 'expense', 'monthly_budget' => '25,00'])
            ->assertRedirect('/categories');

        $this->assertFlash('success', 'Categorie "Sport" is aangemaakt.');
        $this->assertSame(
            ['type' => 'expense', 'monthly_budget_cents' => 2500],
            $this->row('SELECT type, monthly_budget_cents FROM categories WHERE user_id = ? AND name = ?', [self::SAM_ID, 'Sport'])
        );
    }

    public function test_randgeval_limiet_bij_inkomstencategorie_wordt_niet_opgeslagen(): void
    {
        $this->post('/categories', ['name' => 'Zakgeld', 'type' => 'income', 'monthly_budget' => '50'])->assertRedirect('/categories');

        $this->assertNull($this->value('SELECT monthly_budget_cents FROM categories WHERE name = ?', ['Zakgeld']));
    }

    public function test_randgeval_limiet_van_nul_euro_is_toegestaan(): void
    {
        $this->post('/categories', ['name' => 'Gokken', 'type' => 'expense', 'monthly_budget' => '0'])->assertRedirect('/categories');

        $this->assertSame(0, (int) $this->value('SELECT monthly_budget_cents FROM categories WHERE name = ?', ['Gokken']));
    }

    public function test_unhappy_naam_bestaat_al_voor_dezelfde_soort(): void
    {
        $this->post('/categories', ['name' => 'Boodschappen', 'type' => 'expense', 'monthly_budget' => ''])
            ->assertStatus(422)
            ->assertSee('Je hebt al een uitgavecategorie met deze naam.');

        $this->assertSame(1, $this->countRows('categories', 'user_id = ? AND name = ?', [self::SAM_ID, 'Boodschappen']));
    }

    public function test_randgeval_zelfde_naam_bij_andere_soort_of_andere_gebruiker_mag(): void
    {
        $this->post('/categories', ['name' => 'Boodschappen', 'type' => 'income', 'monthly_budget' => ''])->assertRedirect('/categories');
        $this->post('/categories', ['name' => 'Huur', 'type' => 'expense', 'monthly_budget' => ''])->assertRedirect('/categories');
    }

    public function test_unhappy_ongeldige_soort_en_limiet(): void
    {
        $this->post('/categories', ['name' => '', 'type' => 'sparen', 'monthly_budget' => 'veel'])
            ->assertStatus(422)
            ->assertSee('Naam is verplicht.')
            ->assertSee('Kies een geldige waarde bij Soort.')
            ->assertSee('Maandlimiet moet een bedrag zijn');
    }

    public function test_gebruiker_wijzigt_categorie_en_limiet(): void
    {
        $this->get('/categories/' . self::SAM_BOODSCHAPPEN . '/edit')->assertOk()->assertSee('200,00');

        $this->post('/categories/' . self::SAM_BOODSCHAPPEN . '/update', ['name' => 'Supermarkt', 'type' => 'expense', 'monthly_budget' => '250'])
            ->assertRedirect('/categories');

        $this->assertSame(
            ['name' => 'Supermarkt', 'monthly_budget_cents' => 25000],
            $this->row('SELECT name, monthly_budget_cents FROM categories WHERE id = ?', [self::SAM_BOODSCHAPPEN])
        );
    }

    public function test_randgeval_eigen_naam_behouden_bij_wijzigen_mag(): void
    {
        $this->post('/categories/' . self::SAM_BOODSCHAPPEN . '/update', ['name' => 'Boodschappen', 'type' => 'expense', 'monthly_budget' => ''])
            ->assertRedirect('/categories');

        $this->assertNull($this->value('SELECT monthly_budget_cents FROM categories WHERE id = ?', [self::SAM_BOODSCHAPPEN]));
    }

    public function test_unhappy_soort_wijzigen_als_er_transacties_zijn(): void
    {
        $this->post('/categories/' . self::SAM_BOODSCHAPPEN . '/update', ['name' => 'Boodschappen', 'type' => 'income', 'monthly_budget' => ''])
            ->assertStatus(422)
            ->assertSee('De soort kan niet worden gewijzigd, omdat er al transacties in deze categorie staan.');

        $this->assertSame('expense', $this->value('SELECT type FROM categories WHERE id = ?', [self::SAM_BOODSCHAPPEN]));
    }

    public function test_lege_categorie_verwijderen(): void
    {
        $this->post('/categories', ['name' => 'Tijdelijk', 'type' => 'expense', 'monthly_budget' => '']);
        $id = (int) $this->value('SELECT id FROM categories WHERE name = ?', ['Tijdelijk']);

        $this->post("/categories/{$id}/delete")->assertRedirect('/categories');

        $this->assertFlash('success', 'Categorie "Tijdelijk" is verwijderd.');
        $this->assertSame(0, $this->countRows('categories', 'id = ?', [$id]));
    }

    public function test_unhappy_categorie_met_transacties_kan_niet_worden_verwijderd(): void
    {
        $this->post('/categories/' . self::SAM_BOODSCHAPPEN . '/delete')->assertRedirect('/categories');

        $this->assertFlash('error', 'kan niet worden verwijderd, omdat er nog transacties in staan');
        $this->assertSame(1, $this->countRows('categories', 'id = ?', [self::SAM_BOODSCHAPPEN]));
    }

    public function test_unhappy_categorie_van_een_ander_wijzigen_of_verwijderen(): void
    {
        $this->get('/categories/' . self::SANNE_HUUR . '/edit')->assertStatus(404);
        $this->post('/categories/' . self::SANNE_HUUR . '/update', ['name' => 'Gehackt', 'type' => 'expense', 'monthly_budget' => ''])->assertStatus(404);
        $this->post('/categories/' . self::SANNE_HUUR . '/delete')->assertStatus(404);

        $this->assertSame('Huur', $this->value('SELECT name FROM categories WHERE id = ?', [self::SANNE_HUUR]));
    }
}
