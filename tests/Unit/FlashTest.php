<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Flash;
use PHPUnit\Framework\TestCase;

/**
 * Meldingen voor de gebruiker. Leeswijzer: een unittest roept één methode aan en controleert de uitkomst.
 */
final class FlashTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_waarschuwing_staat_boven_een_succesmelding(): void
    {
        // Zo gaat het bij het opslaan van een uitgave boven de limiet: eerst "gelukt", dan de waarschuwing.
        Flash::add('success', 'Uitgave opgeslagen.');
        Flash::add('warning', 'Limiet overschreden.');

        $types = array_column(Flash::consume(), 'type');

        $this->assertSame(['warning', 'success'], $types);
    }

    public function test_meldingen_worden_maar_een_keer_getoond(): void
    {
        Flash::add('info', 'Hallo.');

        $this->assertCount(1, Flash::consume());
        $this->assertSame([], Flash::consume());
    }

    public function test_randgeval_onbekende_soort_wordt_info(): void
    {
        Flash::add('raar', 'Tekst.');

        $this->assertSame('info', Flash::consume()[0]['type']);
    }
}
