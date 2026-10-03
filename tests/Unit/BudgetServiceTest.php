<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\TransactionRepository;
use App\Services\BudgetService;
use App\Support\Month;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * FE-09: de status van een maandlimiet. Deze berekening heeft geen database nodig.
 */
final class BudgetServiceTest extends TestCase
{
    private BudgetService $service;

    protected function setUp(): void
    {
        // Lege SQLite-database in het geheugen: status() voert geen queries uit.
        $this->service = new BudgetService(new TransactionRepository(new PDO('sqlite::memory:')), 80);
    }

    public function test_zonder_limiet_is_er_geen_status(): void
    {
        $this->assertSame(BudgetService::STATUS_NONE, $this->service->status(50000, null));
    }

    public function test_ruim_binnen_de_limiet(): void
    {
        $this->assertSame(BudgetService::STATUS_OK, $this->service->status(1000, 10000));
    }

    public function test_randgeval_79_procent_is_nog_binnen_de_limiet(): void
    {
        $this->assertSame(BudgetService::STATUS_OK, $this->service->status(7999, 10000));
    }

    public function test_randgeval_precies_80_procent_is_bijna_bereikt(): void
    {
        $this->assertSame(BudgetService::STATUS_WARNING, $this->service->status(8000, 10000));
    }

    public function test_randgeval_precies_op_de_limiet_is_nog_niet_overschreden(): void
    {
        $this->assertSame(BudgetService::STATUS_WARNING, $this->service->status(10000, 10000));
    }

    public function test_randgeval_een_cent_boven_de_limiet_is_overschreden(): void
    {
        $this->assertSame(BudgetService::STATUS_OVER, $this->service->status(10001, 10000));
    }

    public function test_randgeval_limiet_van_nul_euro(): void
    {
        $this->assertSame(BudgetService::STATUS_OK, $this->service->status(0, 0));
        $this->assertSame(BudgetService::STATUS_OVER, $this->service->status(1, 0));
    }

    public function test_alleen_overschreden_categorieen_worden_teruggegeven(): void
    {
        $overview = [
            ['name' => 'Boodschappen', 'status' => BudgetService::STATUS_OK],
            ['name' => 'Uitgaan', 'status' => BudgetService::STATUS_OVER],
            ['name' => 'Vervoer', 'status' => BudgetService::STATUS_WARNING],
        ];

        $this->assertSame([['name' => 'Uitgaan', 'status' => BudgetService::STATUS_OVER]], $this->service->exceeded($overview));
    }

    public function test_melding_bevat_bedragen_en_geen_adviesclaim(): void
    {
        $message = BudgetService::overLimitMessage('Uitgaan', Month::tryFromString('2026-09'), 8240, 7500);

        $this->assertStringContainsString('"Uitgaan" in september 2026 (€ 82,40)', $message);
        $this->assertStringContainsString('limiet van € 75,00', $message);
        $this->assertStringContainsString('geen financieel advies', $message);
    }

    public function test_statuslabels_zijn_in_gewone_taal(): void
    {
        $this->assertSame('Binnen limiet', BudgetService::statusLabel(BudgetService::STATUS_OK));
        $this->assertSame('Bijna bereikt', BudgetService::statusLabel(BudgetService::STATUS_WARNING));
        $this->assertSame('Limiet overschreden', BudgetService::statusLabel(BudgetService::STATUS_OVER));
        $this->assertSame('Geen limiet', BudgetService::statusLabel(BudgetService::STATUS_NONE));
    }
}
