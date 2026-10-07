<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\TransactionRepository;
use App\Support\Month;
use Tests\FeatureTestCase;

/**
 * FE-06: Inkomsten en uitgaven per maand optellen.
 *
 * Demodata van Sam in de huidige maand:
 * inkomsten 425,00 + 300,00 = 725,00
 * uitgaven  64,35 + 52,10 + 48,75 + 36,00 + 42,50 + 39,90 + 17,99 + 13,99 = 315,58
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-06
 */
final class MonthTotalsTest extends FeatureTestCase
{
    public function test_totalen_van_de_huidige_maand_in_de_database(): void
    {
        $totals = (new TransactionRepository($this->db()))->totalsForMonth(self::SAM_ID, Month::current());

        $this->assertSame(['income' => 72500, 'expense' => 31558], $totals);
    }

    public function test_dashboard_toont_inkomsten_uitgaven_en_saldo(): void
    {
        $this->actingAs(self::SAM_ID)->get('/dashboard')
            ->assertOk()
            ->assertSee('€ 725,00')
            ->assertSee('€ 315,58')
            ->assertSee('€ 409,42')
            ->assertSee('Meer ontvangen dan uitgegeven');
    }

    public function test_transactieoverzicht_toont_totalen_van_het_filter(): void
    {
        $this->actingAs(self::SAM_ID)->get('/transactions?type=expense')
            ->assertSee('Uitgaven:')
            ->assertSee('€ 315,58')
            ->assertSee('€ 0,00');
    }

    public function test_totalen_van_vorige_maand(): void
    {
        $this->actingAs(self::SAM_ID)->get('/dashboard?month=' . Month::current()->previous()->key())
            ->assertSee('€ 698,00')   // 398,00 + 300,00
            ->assertSee('€ 266,50');  // 189,50 + 52,00 + 25,00
    }

    public function test_randgeval_eerste_en_laatste_dag_van_de_maand_tellen_mee(): void
    {
        $this->addTransaction(self::SAM_CADEAUS, 100, '2030-01-31');
        $this->addTransaction(self::SAM_CADEAUS, 200, '2030-01-01');
        $this->addTransaction(self::SAM_CADEAUS, 400, '2030-02-01');
        $this->addTransaction(self::SAM_CADEAUS, 800, '2029-12-31');

        $totals = (new TransactionRepository($this->db()))->totalsForMonth(self::SAM_ID, Month::tryFromString('2030-01'));

        $this->assertSame(['income' => 0, 'expense' => 300], $totals);
    }

    public function test_randgeval_transacties_van_andere_gebruikers_tellen_niet_mee(): void
    {
        $totals = (new TransactionRepository($this->db()))->totalsForMonth(self::SANNE_ID, Month::current());

        $this->assertSame(['income' => 55000, 'expense' => 53120], $totals);
    }

    public function test_randgeval_maand_zonder_transacties_geeft_nul(): void
    {
        $this->actingAs(self::SAM_ID)->get('/dashboard?month=2001-01')
            ->assertOk()
            ->assertSee('Nog geen inkomsten of uitgaven in januari 2001.');

        $totals = (new TransactionRepository($this->db()))->totalsForMonth(self::SAM_ID, Month::tryFromString('2001-01'));
        $this->assertSame(['income' => 0, 'expense' => 0], $totals);
    }

    public function test_randgeval_negatief_saldo(): void
    {
        $this->addTransaction(self::SAM_CADEAUS, 50000, '2030-05-10');
        $this->addTransaction(self::SAM_BIJBAAN, 10000, '2030-05-01');

        $this->actingAs(self::SAM_ID)->get('/dashboard?month=2030-05')
            ->assertSee('- € 400,00')
            ->assertSee('Meer uitgegeven dan ontvangen');
    }

    public function test_randgeval_grote_bedragen_worden_exact_opgeteld(): void
    {
        // Twee keer het maximum en een cent: met floats zou hier een afrondingsfout kunnen ontstaan.
        $this->addTransaction(self::SAM_BIJBAAN, 999_999_999, '2030-06-01');
        $this->addTransaction(self::SAM_BIJBAAN, 999_999_999, '2030-06-02');
        $this->addTransaction(self::SAM_BIJBAAN, 1, '2030-06-03');

        $totals = (new TransactionRepository($this->db()))->totalsForMonth(self::SAM_ID, Month::tryFromString('2030-06'));

        $this->assertSame(1_999_999_999, $totals['income']);
    }

    private function addTransaction(int $categoryId, int $cents, string $date): void
    {
        (new TransactionRepository($this->db()))->create(self::SAM_ID, [
            'category_id' => $categoryId,
            'amount_cents' => $cents,
            'transaction_date' => $date,
            'description' => null,
        ]);
    }
}
