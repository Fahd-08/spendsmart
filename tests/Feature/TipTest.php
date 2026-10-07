<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * FE-11: Contentbeheerder publiceert leerteksten; gebruikers lezen alleen gepubliceerde teksten.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-11
 */
final class TipTest extends FeatureTestCase
{
    private const BODY = 'Een begroting is een plan voor je geld, geen voorspelling.';

    public function test_gebruiker_ziet_alleen_gepubliceerde_tips(): void
    {
        $this->actingAs(self::SAM_ID)->get('/tips')
            ->assertOk()
            ->assertSee('Vaste en variabele uitgaven')
            ->assertDontSee('Concept: sparen met een doel');
    }

    public function test_contentbeheerder_ziet_ook_concepten(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/tips')
            ->assertOk()
            ->assertSee('Concept: sparen met een doel')
            ->assertSee('Concept');

        $this->get('/content/tips/create')->assertOk();
    }

    public function test_contentbeheerder_publiceert_leertekst(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/content/tips', ['title' => 'Wat is een begroting?', 'body' => self::BODY, 'is_published' => '1'])
            ->assertRedirect('/content/tips');

        $this->assertFlash('success', 'is gepubliceerd.');
        $this->assertNotNull($this->value('SELECT published_at FROM tips WHERE title = ?', ['Wat is een begroting?']));

        $this->actingAs(self::SAM_ID)->get('/tips')->assertSee('Wat is een begroting?')->assertSee(self::BODY);
    }

    public function test_concept_is_niet_zichtbaar_voor_gebruikers(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/content/tips', ['title' => 'Nog niet af', 'body' => self::BODY])
            ->assertRedirect('/content/tips');

        $this->assertFlash('success', 'is als concept opgeslagen.');
        $this->assertNull($this->value('SELECT published_at FROM tips WHERE title = ?', ['Nog niet af']));

        $this->actingAs(self::SAM_ID)->get('/tips')->assertDontSee('Nog niet af');
    }

    public function test_concept_publiceren_en_weer_intrekken(): void
    {
        $id = (int) $this->value('SELECT id FROM tips WHERE is_published = 0');
        $this->actingAs(self::CONTENT_MANAGER_ID);
        $this->get("/content/tips/{$id}/edit")->assertOk();

        $this->post("/content/tips/{$id}/update", ['title' => 'Sparen met een doel', 'body' => self::BODY, 'is_published' => '1'])
            ->assertRedirect('/content/tips');
        $this->assertNotNull($this->value('SELECT published_at FROM tips WHERE id = ?', [$id]));

        $this->post("/content/tips/{$id}/update", ['title' => 'Sparen met een doel', 'body' => self::BODY])
            ->assertRedirect('/content/tips');
        $this->assertSame(
            ['is_published' => 0, 'published_at' => null],
            $this->row('SELECT is_published, published_at FROM tips WHERE id = ?', [$id])
        );
    }

    public function test_unhappy_te_korte_tekst_en_lege_titel(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post('/content/tips', ['title' => '', 'body' => 'Te kort.', 'is_published' => '1'])
            ->assertStatus(422)
            ->assertSee('Titel is verplicht.')
            ->assertSee('Tekst moet minimaal 20 tekens bevatten.');
    }

    public function test_unhappy_wijzigen_met_te_lange_titel(): void
    {
        $id = (int) $this->value('SELECT id FROM tips LIMIT 1');

        $this->actingAs(self::CONTENT_MANAGER_ID)
            ->post("/content/tips/{$id}/update", ['title' => str_repeat('t', 151), 'body' => self::BODY])
            ->assertStatus(422)
            ->assertSee('Titel mag maximaal 150 tekens bevatten.');
    }

    public function test_randgeval_html_in_leertekst_wordt_als_tekst_getoond(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->post('/content/tips', [
            'title' => '<img src=x onerror=alert(1)>',
            'body' => self::BODY,
            'is_published' => '1',
        ]);

        $this->actingAs(self::SAM_ID)->get('/tips')->assertDontSeeRaw('<img src=x onerror=alert(1)>');
    }

    public function test_leertekst_verwijderen(): void
    {
        $id = (int) $this->value('SELECT id FROM tips LIMIT 1');

        $this->actingAs(self::CONTENT_MANAGER_ID)->post("/content/tips/{$id}/delete")->assertRedirect('/content/tips');

        $this->assertSame(0, $this->countRows('tips', 'id = ?', [$id]));
    }

    public function test_randgeval_zonder_gepubliceerde_tips(): void
    {
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec('UPDATE tips SET is_published = 0, published_at = NULL');

        $this->actingAs(self::SAM_ID)->get('/tips')->assertSee('Er zijn nog geen tips gepubliceerd. Kijk later nog eens.');
        $this->get('/dashboard')->assertOk();
    }

    public function test_unhappy_onbekende_leertekst(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/tips/999/edit')->assertStatus(404);
    }
}
