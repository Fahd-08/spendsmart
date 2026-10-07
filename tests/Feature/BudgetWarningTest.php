<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\TransactionRepository;
use App\Services\BudgetService;
use App\Support\Month;
use Tests\FeatureTestCase;

/**
 * FE-09: Waarschuwen bij overschrijding van een maandlimiet, zonder financieel advies te geven.
 *
 * Demodata van Sam deze maand: Vervoer 36,00 van 60,00 limiet, Uitgaan 82,40 van 75,00 limiet (al overschreden).
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-09
 */
final class BudgetWarningTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(self::SAM_ID);
    }

    public function test_waarschuwing_na_uitgave_boven_de_limiet(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_VERVOER,
            'amount' => '25',
            'transaction_date' => date('Y-m-d'),
        ])->assertRedirect('/transactions?month=' . Month::current()->key());

        $this->assertFlash('warning', 'Je uitgaven voor "Vervoer"');
        $this->assertFlash('warning', 'geen financieel advies');
    }

    public function test_geen_waarschuwing_als_de_limiet_niet_wordt_overschreden(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_VERVOER,
            'amount' => '24',
            'transaction_date' => date('Y-m-d'),
        ]);

        $this->assertNoFlash('warning');
    }

    public function test_randgeval_geen_waarschuwing_bij_categorie_zonder_limiet(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_CADEAUS,
            'amount' => '5000',
            'transaction_date' => date('Y-m-d'),
        ]);

        $this->assertNoFlash('warning');
    }

    public function test_randgeval_geen_waarschuwing_bij_inkomst(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BIJBAAN,
            'amount' => '5000',
            'transaction_date' => date('Y-m-d'),
        ]);

        $this->assertNoFlash('warning');
    }

    public function test_randgeval_uitgave_in_andere_maand_telt_voor_die_maand(): void
    {
        // In een maand zonder andere uitgaven blijft 25,00 binnen de limiet van 60,00.
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_VERVOER,
            'amount' => '25',
            'transaction_date' => '2030-01-15',
        ]);

        $this->assertNoFlash('warning');
    }

    public function test_waarschuwing_ook_na_wijzigen_van_een_uitgave(): void
    {
        $id = (int) $this->value("SELECT id FROM transactions WHERE description = 'OV-saldo opgeladen'");

        $this->post("/transactions/{$id}/update", [
            'category_id' => (string) self::SAM_VERVOER,
            'amount' => '60,01',
            'transaction_date' => date('Y-m-d'),
        ]);

        $this->assertFlash('warning', 'hoger dan je ingestelde limiet van € 60,00');
    }

    public function test_dashboard_toont_overschreden_limiet_met_disclaimer(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Je uitgaven voor "Uitgaan"')
            ->assertSee('Limiet overschreden')
            ->assertSee(BudgetService::DISCLAIMER);
    }

    public function test_budgetoverzicht_berekent_status_per_categorie(): void
    {
        $service = new BudgetService(new TransactionRepository($this->db()), 80);
        $lines = array_column($service->overview(self::SAM_ID, Month::current()), null, 'name');

        $this->assertSame(BudgetService::STATUS_OVER, $lines['Uitgaan']['status']);
        $this->assertSame(-740, $lines['Uitgaan']['remaining_cents']);
        $this->assertSame(BudgetService::STATUS_OK, $lines['Vervoer']['status']);
        $this->assertSame(60, $lines['Vervoer']['percentage']);
        $this->assertSame(BudgetService::STATUS_WARNING, $lines['Boodschappen']['status']); // 165,20 van 200,00 = 82%
        $this->assertSame(BudgetService::STATUS_OK, $lines['Abonnementen']['status']);      // 31,98 van 40,00 = 79,95%
        $this->assertSame(79, $lines['Abonnementen']['percentage'], 'Percentage wordt naar beneden afgerond.');
        $this->assertSame(BudgetService::STATUS_NONE, $lines['Cadeaus']['status']);
        $this->assertSame(0, $lines['Cadeaus']['percentage']);
    }

    public function test_randgeval_limiet_nul_met_uitgave_toont_100_procent(): void
    {
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec('UPDATE categories SET monthly_budget_cents = 0 WHERE id = ' . self::SAM_CADEAUS);
        $this->post('/transactions', ['category_id' => (string) self::SAM_CADEAUS, 'amount' => '1', 'transaction_date' => date('Y-m-d')]);

        $service = new BudgetService(new TransactionRepository($this->db()), 80);
        $lines = array_column($service->overview(self::SAM_ID, Month::current()), null, 'name');

        $this->assertSame(100, $lines['Cadeaus']['percentage']);
        $this->assertSame(BudgetService::STATUS_OVER, $lines['Cadeaus']['status']);
        $this->assertFlash('warning', 'geen financieel advies');
    }
}
