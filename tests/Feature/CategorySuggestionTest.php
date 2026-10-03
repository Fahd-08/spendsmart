<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-10: Contentbeheerder beheert categorievoorstellen; gebruikers kunnen actieve voorstellen overnemen.
 *
 * Demodata: voorstellen 1 t/m 6 zijn actief, 7 (Studiekosten) is inactief.
 * Sanne heeft voorstel 2 (Studiefinanciering) nog niet overgenomen.
 *
 * @group FE-10
 */
final class CategorySuggestionTest extends FeatureTestCase
{
    private const STUDIEFINANCIERING = 2;
    private const STUDIEKOSTEN_INACTIEF = 7;

    public function test_contentbeheerder_ziet_alle_voorstellen(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/suggestions')
            ->assertOk()
            ->assertSee('Studiekosten')
            ->assertSee('Inactief');

        $this->get('/content/suggestions/create')->assertOk();
    }

    public function test_contentbeheerder_maakt_voorstel(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->post('/content/suggestions', [
            'name' => 'Huisdieren',
            'type' => 'expense',
            'description' => 'Voer en dierenarts.',
            'is_active' => '1',
        ])->assertRedirect('/content/suggestions');

        $this->assertSame(
            ['type' => 'expense', 'is_active' => 1, 'created_by' => self::CONTENT_MANAGER_ID],
            $this->row('SELECT type, is_active, created_by FROM category_suggestions WHERE name = ?', ['Huisdieren'])
        );
    }

    public function test_unhappy_voorstel_met_bestaande_naam_en_soort(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/content/suggestions', ['name' => 'Vervoer', 'type' => 'expense', 'description' => ''])
            ->assertStatus(422)
            ->assertSee('Er bestaat al een voorstel met deze naam en soort.');
    }

    public function test_contentbeheerder_zet_voorstel_op_inactief(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID);
        $this->get('/content/suggestions/' . self::STUDIEFINANCIERING . '/edit')->assertOk()->assertSee('Studiefinanciering');

        $this->post('/content/suggestions/' . self::STUDIEFINANCIERING . '/update', [
            'name' => 'Studiefinanciering',
            'type' => 'income',
            'description' => 'Aangepast.',
        ])->assertRedirect('/content/suggestions');

        $this->assertSame(0, (int) $this->value('SELECT is_active FROM category_suggestions WHERE id = ?', [self::STUDIEFINANCIERING]));

        // Sanne ziet het inactieve voorstel niet meer.
        $this->actingAs(self::SANNE_ID)->get('/categories')->assertDontSee('Studiefinanciering');
    }

    public function test_unhappy_wijzigen_met_lege_naam(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/content/suggestions/' . self::STUDIEFINANCIERING . '/update', ['name' => '', 'type' => 'income'])
            ->assertStatus(422)
            ->assertSee('Naam is verplicht.');
    }

    public function test_voorstel_verwijderen_laat_overgenomen_categorieen_bestaan(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->post('/content/suggestions/3/delete')->assertRedirect('/content/suggestions');

        $this->assertSame(0, $this->countRows('category_suggestions', 'id = 3'));
        $this->assertSame(
            ['name' => 'Boodschappen', 'suggestion_id' => null],
            $this->row('SELECT name, suggestion_id FROM categories WHERE id = ?', [self::SAM_BOODSCHAPPEN])
        );
    }

    public function test_gebruiker_ziet_en_neemt_voorstel_over(): void
    {
        $this->actingAs(self::SANNE_ID)->get('/categories')->assertSee('Studiefinanciering');

        $this->post('/categories/adopt/' . self::STUDIEFINANCIERING)->assertRedirect('/categories');

        $this->assertFlash('success', 'Categorie "Studiefinanciering" is toegevoegd aan je categorieën.');
        $this->assertSame(1, $this->countRows('categories', 'user_id = ? AND suggestion_id = ?', [self::SANNE_ID, self::STUDIEFINANCIERING]));
    }

    public function test_randgeval_voorstel_twee_keer_overnemen_maakt_geen_dubbele_categorie(): void
    {
        $this->actingAs(self::SAM_ID)->post('/categories/adopt/' . self::STUDIEFINANCIERING)->assertRedirect('/categories');

        $this->assertFlash('info', 'Je hebt al een categorie "Studiefinanciering".');
        $this->assertSame(1, $this->countRows('categories', 'user_id = ? AND name = ?', [self::SAM_ID, 'Studiefinanciering']));
    }

    public function test_unhappy_inactief_voorstel_kan_niet_worden_overgenomen(): void
    {
        $this->actingAs(self::SANNE_ID)->post('/categories/adopt/' . self::STUDIEKOSTEN_INACTIEF)->assertStatus(404);

        $this->assertSame(0, $this->countRows('categories', 'suggestion_id = ?', [self::STUDIEKOSTEN_INACTIEF]));
    }

    public function test_unhappy_onbekend_voorstel_bewerken(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/suggestions/999/edit')->assertStatus(404);
    }
}
