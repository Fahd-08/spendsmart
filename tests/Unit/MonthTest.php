<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Month;
use PHPUnit\Framework\TestCase;

/**
 * De maand bepaalt welke transacties in het filter en in de maandtotalen vallen (FE-05, FE-06).
 *
 * Leeswijzer: een unittest roept één methode aan en controleert de uitkomst met assert...
 * Geen database en geen browser nodig. test_randgeval_... = grensgeval.
 */
final class MonthTest extends TestCase
{
    public function test_geldige_maand_uit_url(): void
    {
        $month = Month::tryFromString('2026-09');

        $this->assertNotNull($month);
        $this->assertSame('2026-09', $month->key());
        $this->assertSame('september 2026', $month->label());
        $this->assertSame('sep 2026', $month->shortLabel());
    }

    /**
     * @dataProvider invalidMonths
     */
    public function test_ongeldige_maand_geeft_null(string $value): void
    {
        $this->assertNull(Month::tryFromString($value));
    }

    // Lijst met testgevallen: elke regel wordt een aparte test (de naam links verschijnt in het testrapport).
    public function invalidMonths(): array
    {
        return [
            'maand 13' => ['2026-13'],
            'maand 0' => ['2026-00'],
            'zonder voorloopnul' => ['2026-9'],
            'randgeval: jaar 1999' => ['1999-12'],
            'randgeval: jaar 2101' => ['2101-01'],
            'tekst' => ['september'],
            'leeg' => [''],
        ];
    }

    public function test_randgeval_vorige_maand_van_januari_is_december_vorig_jaar(): void
    {
        $this->assertSame('2025-12', Month::tryFromString('2026-01')->previous()->key());
    }

    public function test_randgeval_volgende_maand_van_december_is_januari_volgend_jaar(): void
    {
        $this->assertSame('2027-01', Month::tryFromString('2026-12')->next()->key());
    }

    public function test_begin_en_einde_van_de_maand(): void
    {
        $month = Month::tryFromString('2026-02');

        $this->assertSame('2026-02-01', $month->start());
        $this->assertSame('2026-03-01', $month->end());
    }

    public function test_randgeval_laatste_dag_hoort_bij_de_maand_eerste_dag_van_volgende_maand_niet(): void
    {
        $month = Month::tryFromString('2026-02');

        $this->assertTrue($month->contains('2026-02-01'));
        $this->assertTrue($month->contains('2026-02-28'));
        $this->assertFalse($month->contains('2026-03-01'));
        $this->assertFalse($month->contains('2026-01-31'));
    }

    public function test_huidige_maand(): void
    {
        $this->assertSame(date('Y-m'), Month::current()->key());
        $this->assertTrue(Month::current()->isCurrent());
        $this->assertFalse(Month::current()->previous()->isCurrent());
    }

    public function test_maand_uit_datum(): void
    {
        $this->assertSame('2026-12', Month::fromDate('2026-12-31')->key());
    }
}
