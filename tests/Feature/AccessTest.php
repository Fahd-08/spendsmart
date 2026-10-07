<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

/**
 * TE-03, TE-07, TE-08: rollen, afscherming en foutpagina's over de hele app.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group toegang
 */
final class AccessTest extends FeatureTestCase
{
    /**
     * @dataProvider userPages
     */
    public function test_unhappy_contentbeheerder_kan_niet_bij_gebruikerspaginas(string $path): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get($path)->assertStatus(403);
    }

    // Lijst met testgevallen: elke regel wordt een aparte test (de naam links verschijnt in het testrapport).
    public function userPages(): array
    {
        return [['/dashboard'], ['/transactions'], ['/categories'], ['/goals'], ['/tips']];
    }

    /**
     * @dataProvider contentManagerPages
     */
    public function test_unhappy_gebruiker_kan_niet_bij_beheerpaginas(string $path): void
    {
        $this->actingAs(self::SAM_ID)->get($path)->assertStatus(403);
    }

    // Lijst met testgevallen: elke regel wordt een aparte test (de naam links verschijnt in het testrapport).
    public function contentManagerPages(): array
    {
        return [['/content/statistics'], ['/content/tips'], ['/content/suggestions']];
    }

    public function test_unhappy_gebruiker_kan_geen_leertekst_aanmaken_via_formulier(): void
    {
        $this->actingAs(self::SAM_ID)
            ->post('/content/tips', ['title' => 'Hack', 'body' => str_repeat('x', 30), 'is_published' => '1'])
            ->assertStatus(403);

        $this->assertSame(0, $this->countRows('tips', 'title = ?', ['Hack']));
    }

    public function test_bezoeker_ziet_startpagina(): void
    {
        $this->get('/')->assertOk()->assertSee('SpendSmart');
    }

    public function test_ingelogde_gebruiker_gaat_van_startpagina_naar_eigen_startscherm(): void
    {
        $this->actingAs(self::SAM_ID)->get('/')->assertRedirect('/dashboard');
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/')->assertRedirect('/content/statistics');
    }

    public function test_onbekende_pagina_geeft_404(): void
    {
        $this->get('/bestaat-niet')->assertStatus(404)->assertSee('Deze pagina bestaat niet.');
    }

    public function test_randgeval_ongeldig_id_in_url_geeft_404(): void
    {
        $this->actingAs(self::SAM_ID);

        $this->get('/transactions/0/edit')->assertStatus(404);
        $this->get('/transactions/abc/edit')->assertStatus(404);
        $this->get('/transactions/99999999999/edit')->assertStatus(404);
    }

    public function test_unhappy_verkeerde_http_methode_geeft_405(): void
    {
        $this->actingAs(self::SAM_ID)->get('/logout')->assertStatus(405);
    }

    public function test_randgeval_trailing_slash_wordt_genegeerd(): void
    {
        $this->actingAs(self::SAM_ID)->get('/dashboard/')->assertOk();
    }
}
