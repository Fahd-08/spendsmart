<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

/**
 * Bedragen worden als hele centen opgeslagen (TE-06). Een fout hier geeft verkeerde totalen in de hele app.
 */
final class MoneyTest extends TestCase
{
    /**
     * @dataProvider validAmounts
     */
    public function test_geldige_invoer_wordt_omgezet_naar_centen(string $input, int $expectedCents): void
    {
        $this->assertSame($expectedCents, Money::parse($input));
    }

    public function validAmounts(): array
    {
        return [
            'komma' => ['12,50', 1250],
            'punt' => ['12.50', 1250],
            'één cijfer achter de komma' => ['12,5', 1250],
            'heel bedrag' => ['12', 1200],
            'met euroteken en spaties' => [' € 12,50 ', 1250],
            'randgeval: nul' => ['0', 0],
            'randgeval: één cent' => ['0,01', 1],
            'randgeval: hoogst toegestane bedrag' => ['9999999,99', Money::MAX_CENTS],
        ];
    }

    /**
     * @dataProvider invalidAmounts
     */
    public function test_ongeldige_invoer_geeft_null(string $input): void
    {
        $this->assertNull(Money::parse($input));
    }

    public function invalidAmounts(): array
    {
        return [
            'leeg' => [''],
            'tekst' => ['twaalf'],
            'negatief' => ['-5'],
            'drie decimalen' => ['12,345'],
            'duizendtalscheiding' => ['1.250,00'],
            'randgeval: één euro boven maximum' => ['10000000'],
            'alleen een komma' => [','],
        ];
    }

    public function test_bedrag_wordt_als_euro_getoond(): void
    {
        $this->assertSame('€ 1.234,56', Money::format(123456));
        $this->assertSame('€ 0,05', Money::format(5));
        $this->assertSame('€ 0,00', Money::format(0));
    }

    public function test_negatief_saldo_krijgt_een_minteken(): void
    {
        $this->assertSame('- € 5,00', Money::format(-500));
    }

    public function test_bedrag_voor_invoerveld_gebruikt_komma(): void
    {
        $this->assertSame('12,50', Money::toInput(1250));
        $this->assertSame('0,07', Money::toInput(7));
    }

    public function test_percentage_wordt_naar_beneden_afgerond(): void
    {
        $this->assertSame(25, Money::percentage(50, 200));
        $this->assertSame(33, Money::percentage(1, 3));
        $this->assertSame(150, Money::percentage(300, 200));
    }

    public function test_percentage_van_nul_geeft_geen_deling_door_nul(): void
    {
        $this->assertSame(0, Money::percentage(500, 0));
    }
}
