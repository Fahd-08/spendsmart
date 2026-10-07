<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\RedirectException;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use ReflectionProperty;
use Tests\Support\TestResponse;
use Throwable;

/**
 * Basis voor integratietests: stuurt een verzoek door de echte router, middleware, controller,
 * repository (database) en view, precies zoals public/index.php dat doet.
 *
 * Zo test je frontend (formulier/HTML), backend (PHP) en database samen, zonder browser.
 * Voorbeeld in een test:
 *   $this->actingAs(self::SAM_ID)->post('/transactions', [...])->assertRedirect('/transactions?month=2026-03');
 */
abstract class FeatureTestCase extends DatabaseTestCase
{
    /**
     * Voor elke test: demodata opnieuw laden (in DatabaseTestCase) en niemand ingelogd.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
    }

    /**
     * Inloggen als deze gebruiker, met een lege sessie (alsof het een andere browser is).
     * Geeft $this terug, zodat je kunt doorschrijven: $this->actingAs(2)->get('/dashboard').
     */
    protected function actingAs(int $userId): static
    {
        Auth::logout();
        Auth::login(['id' => $userId]);

        return $this;
    }

    /**
     * Pagina opvragen, zoals een link aanklikken.
     */
    protected function get(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    /**
     * POST-verzoek (formulier versturen). Het CSRF-token wordt automatisch meegestuurd,
     * tenzij $withCsrfToken false is (om te testen dat een formulier zonder token wordt geweigerd).
     */
    protected function post(string $uri, array $data = [], bool $withCsrfToken = true): TestResponse
    {
        if ($withCsrfToken) {
            $data['_token'] ??= Csrf::token();
        }

        return $this->request('POST', $uri, $data);
    }

    /**
     * Volgt een redirect en geeft de pagina terug waar de gebruiker op uitkomt.
     */
    protected function follow(TestResponse $response): TestResponse
    {
        $this->assertNotNull($response->redirectUrl, 'Er was geen redirect om te volgen.');

        return $this->get($response->redirectUrl);
    }

    /**
     * Controleert of er een melding (flash) van dit type met deze tekst klaarstaat.
     * Handig na een redirect: de melding staat dan nog in de sessie, klaar voor de volgende pagina.
     */
    protected function assertFlash(string $type, string $textPart): void
    {
        foreach (Session::get('_flash_messages', []) as $message) {
            if ($message['type'] === $type && str_contains($message['text'], $textPart)) {
                $this->addToAssertionCount(1); // telt mee als geslaagde controle

                return;
            }
        }

        $this->fail("Geen {$type}-melding met de tekst '{$textPart}'. Meldingen: " . json_encode(Session::get('_flash_messages', [])));
    }

    /**
     * Controleert dat er géén melding van dit type is (bijv. geen waarschuwing bij een inkomst).
     */
    protected function assertNoFlash(string $type): void
    {
        $types = array_column(Session::get('_flash_messages', []), 'type');
        $this->assertNotContains($type, $types, "Er was onverwacht een {$type}-melding.");
    }

    /**
     * ID van wie nu is ingelogd (of null), om te controleren of inloggen/uitloggen gelukt is.
     */
    protected function loggedInUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Doet hetzelfde als public/index.php, maar vangt het resultaat op in een TestResponse.
     */
    private function request(string $method, string $uri, array $body = []): TestResponse
    {
        // '?month=2026-09' uit de URL halen en omzetten naar ['month' => '2026-09'].
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        // Een nagemaakt verzoek, zoals de browser het zou sturen.
        $request = new Request($query, $body, [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        // Elk verzoek begint "vers", net als een nieuwe pagina in de browser.
        $this->forgetCachedUser();
        View::share('currentPath', $request->path());
        http_response_code(200);

        // Dezelfde routes als de echte app.
        $router = new Router();
        require BASE_PATH . '/routes/web.php';

        // De HTML opvangen in plaats van naar het scherm te sturen.
        ob_start();
        $redirectUrl = null;

        try {
            $router->dispatch($request);
        } catch (RedirectException $redirect) {
            // De app wil doorsturen: onthouden waarheen, zodat de test dat kan controleren.
            $redirectUrl = $redirect->url();
        } catch (Throwable $exception) {
            // Fout (403, 404, ...): dezelfde foutpagina als in de echte app.
            ErrorHandler::handle($exception);
        }

        $body = (string) ob_get_clean();
        $status = $redirectUrl !== null ? 303 : (int) http_response_code();

        return new TestResponse($status, $body, $redirectUrl);
    }

    /**
     * Auth onthoudt de gebruiker binnen één verzoek. In een test doen we meerdere verzoeken na elkaar,
     * dus dat geheugen wissen we (via Reflection, omdat de eigenschap private is).
     */
    private function forgetCachedUser(): void
    {
        (new ReflectionProperty(Auth::class, 'user'))->setValue(null, null);
    }
}
