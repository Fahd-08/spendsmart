<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Month;
use Tests\FeatureTestCase;

/**
 * FE-05: Transacties per maand en categorie filteren.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-05
 */
final class FilterTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(self::SAM_ID);
    }

    public function test_standaard_wordt_de_huidige_maand_getoond(): void
    {
        $this->get('/transactions')
            ->assertOk()
            ->assertSee(Month::current()->label())
            ->assertSee('Weekboodschappen')
            ->assertDontSee('Cadeau zus');
    }

    public function test_filteren_op_vorige_maand(): void
    {
        $previous = Month::current()->previous();

        $this->get('/transactions?month=' . $previous->key())
            ->assertOk()
            ->assertSee($previous->label())
            ->assertSee('Cadeau zus')
            ->assertSee('Treinkaartjes')
            ->assertDontSee('Weekboodschappen');
    }

    public function test_filteren_op_categorie(): void
    {
        $this->get('/transactions?category=' . self::SAM_BOODSCHAPPEN)
            ->assertOk()
            ->assertSee('Weekboodschappen')
            ->assertDontSee('OV-saldo opgeladen')
            ->assertDontSee('Salaris bijbaan');
    }

    public function test_filteren_op_soort_inkomsten(): void
    {
        $this->get('/transactions?type=income')
            ->assertOk()
            ->assertSee('Salaris bijbaan')
            ->assertDontSee('Weekboodschappen');
    }

    public function test_filters_combineren_met_maand(): void
    {
        $previous = Month::current()->previous();

        $this->get('/transactions?month=' . $previous->key() . '&category=' . self::SAM_CADEAUS . '&type=expense')
            ->assertSee('Cadeau zus')
            ->assertDontSee('Treinkaartjes');
    }

    public function test_alleen_eigen_transacties_zichtbaar(): void
    {
        $this->get('/transactions')->assertDontSee('Huur kamer');
    }

    public function test_randgeval_ongeldige_maand_toont_huidige_maand_met_melding(): void
    {
        $this->get('/transactions?month=2026-13')
            ->assertOk()
            ->assertSee(Month::current()->label())
            ->assertSee('De gekozen maand is ongeldig. De huidige maand wordt getoond.');
    }

    public function test_randgeval_categorie_van_een_ander_in_filter_wordt_genegeerd(): void
    {
        $this->get('/transactions?category=' . self::SANNE_HUUR)
            ->assertOk()
            ->assertSee('Weekboodschappen')
            ->assertDontSee('Huur kamer')
            ->assertSee('De gekozen categorie bestaat niet. Alle categorieën worden getoond.');
    }

    public function test_randgeval_onbekende_soort_wordt_genegeerd(): void
    {
        $this->get('/transactions?type=sparen')->assertOk()->assertSee('Weekboodschappen')->assertSee('Salaris bijbaan');
    }

    public function test_randgeval_maand_zonder_transacties_toont_lege_melding(): void
    {
        $this->get('/transactions?month=2001-01')->assertOk()->assertSee('Je hebt nog geen inkomsten of uitgaven in januari 2001.');
        $this->get('/transactions?month=2001-01&type=expense')->assertSee('Geen transacties gevonden in januari 2001 met deze filters.');
    }
}
