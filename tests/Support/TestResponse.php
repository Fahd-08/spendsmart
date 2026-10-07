<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Het antwoord van de app op een testverzoek: statuscode, HTML en eventuele redirect.
 * De assert-methodes geven $this terug, zodat je controles achter elkaar kunt zetten:
 *   $response->assertStatus(422)->assertSee('Bedrag is verplicht.');
 */
final class TestResponse
{
    /**
     * @param int         $status      HTTP-status, bijv. 200 (ok), 303 (redirect), 404, 422
     * @param string      $body        de HTML van de pagina
     * @param string|null $redirectUrl waar de app naartoe stuurt, of null
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly ?string $redirectUrl = null,
    ) {
    }

    /**
     * Controleert de statuscode, bijv. 403 bij geen toegang.
     */
    public function assertStatus(int $expected): self
    {
        Assert::assertSame($expected, $this->status, "Verwachtte status {$expected}, kreeg {$this->status}." . $this->hint());

        return $this;
    }

    /**
     * Pagina is gewoon goed geladen (200).
     */
    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    /**
     * Controleert waar de app naartoe doorstuurt, bijv. '/dashboard' na inloggen.
     */
    public function assertRedirect(string $expectedUrl): self
    {
        Assert::assertSame($expectedUrl, $this->redirectUrl, 'Onverwachte redirect.' . $this->hint());

        return $this;
    }

    /**
     * Controleert of tekst op de pagina staat, zoals de gebruiker hem ziet (dus HTML-veilig gemaakt).
     */
    public function assertSee(string $text): self
    {
        Assert::assertTrue(str_contains($this->body, e($text)), "De tekst '{$text}' staat niet op de pagina." . $this->hint());

        return $this;
    }

    /**
     * Controleert dat tekst níet op de pagina staat (bijv. gegevens van een andere gebruiker).
     */
    public function assertDontSee(string $text): self
    {
        Assert::assertFalse(str_contains($this->body, e($text)), "De tekst '{$text}' staat onverwacht op de pagina.");

        return $this;
    }

    /**
     * Controleert de ruwe HTML (zonder omzetten), bijvoorbeeld om te zien dat een script-tag níet letterlijk staat.
     * Samen met assertSee bewijst dit dat de tekst wel getoond wordt, maar als veilige tekst (XSS-test).
     */
    public function assertDontSeeRaw(string $html): self
    {
        Assert::assertFalse(str_contains($this->body, $html), "De HTML '{$html}' staat letterlijk op de pagina.");

        return $this;
    }

    /**
     * Extra uitleg bij een mislukte test: waar werd naartoe gestuurd, of welke veldfout stond er.
     */
    private function hint(): string
    {
        if ($this->redirectUrl !== null) {
            return " (redirect naar {$this->redirectUrl})";
        }

        if (preg_match('/<p class="field-error"[^>]*>(.*?)<\/p>/s', $this->body, $match)) {
            return ' (eerste veldfout: ' . html_entity_decode($match[1]) . ')';
        }

        return '';
    }
}
