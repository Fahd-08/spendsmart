<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Invoer uit het verzoek en het tonen van templates.
 *
 * Leeswijzer: een unittest roept één methode aan en controleert de uitkomst met assert...
 * Geen database en geen browser nodig. test_randgeval_... = grensgeval.
 */
final class RequestViewTest extends TestCase
{
    public function test_randgeval_onverwachte_invoer_zoals_een_array_geeft_de_standaardwaarde(): void
    {
        $request = new Request(['month' => ['2026-09']], ['amount' => ['12']], []);

        $this->assertSame('', $request->input('amount'));
        $this->assertSame('standaard', $request->query('month', 'standaard'));
    }

    public function test_url_filter_wordt_getrimd_formulierinvoer_niet(): void
    {
        $request = new Request(['month' => ' 2026-09 '], ['password' => ' geheim '], []);

        $this->assertSame('2026-09', $request->query('month'));
        $this->assertSame(' geheim ', $request->input('password'));
    }

    public function test_methode_pad_en_ip_adres(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'post', 'REQUEST_URI' => '/transactions/?month=2026-09', 'REMOTE_ADDR' => '10.0.0.1']);

        $this->assertSame('POST', $request->method());
        $this->assertSame('/transactions', $request->path());
        $this->assertSame('10.0.0.1', $request->ip());
    }

    public function test_verzoek_uit_de_superglobals(): void
    {
        $_GET = ['type' => 'income'];
        $_POST = [];

        $request = Request::fromGlobals();

        $this->assertSame('income', $request->query('type'));
        $this->assertSame('GET', $request->method());
        $this->assertSame('0.0.0.0', $request->ip());

        $_GET = [];
    }

    public function test_onbekende_view_is_een_programmeerfout(): void
    {
        $this->expectException(RuntimeException::class);

        View::partial('bestaat-niet');
    }

    public function test_partial_toont_gegevens_veilig(): void
    {
        $html = View::partial('alert', ['type' => 'warning', 'text' => '<b>Let op</b>']);

        $this->assertStringContainsString('&lt;b&gt;Let op&lt;/b&gt;', $html);
    }
}
