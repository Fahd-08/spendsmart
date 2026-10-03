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
 */
abstract class FeatureTestCase extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
    }

    /**
     * Inloggen als deze gebruiker, met een lege sessie (alsof het een andere browser is).
     */
    protected function actingAs(int $userId): static
    {
        Auth::logout();
        Auth::login(['id' => $userId]);

        return $this;
    }

    protected function get(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    /**
     * POST-verzoek. Het CSRF-token wordt automatisch meegestuurd, tenzij $withCsrfToken false is.
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
     */
    protected function assertFlash(string $type, string $textPart): void
    {
        foreach (Session::get('_flash_messages', []) as $message) {
            if ($message['type'] === $type && str_contains($message['text'], $textPart)) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail("Geen {$type}-melding met de tekst '{$textPart}'. Meldingen: " . json_encode(Session::get('_flash_messages', [])));
    }

    protected function assertNoFlash(string $type): void
    {
        $types = array_column(Session::get('_flash_messages', []), 'type');
        $this->assertNotContains($type, $types, "Er was onverwacht een {$type}-melding.");
    }

    protected function loggedInUserId(): ?int
    {
        return Auth::id();
    }

    private function request(string $method, string $uri, array $body = []): TestResponse
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        $request = new Request($query, $body, [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        // Elk verzoek begint "vers", net als een nieuwe pagina in de browser.
        $this->forgetCachedUser();
        View::share('currentPath', $request->path());
        http_response_code(200);

        $router = new Router();
        require BASE_PATH . '/routes/web.php';

        ob_start();
        $redirectUrl = null;

        try {
            $router->dispatch($request);
        } catch (RedirectException $redirect) {
            $redirectUrl = $redirect->url();
        } catch (Throwable $exception) {
            ErrorHandler::handle($exception);
        }

        $body = (string) ob_get_clean();
        $status = $redirectUrl !== null ? 303 : (int) http_response_code();

        return new TestResponse($status, $body, $redirectUrl);
    }

    private function forgetCachedUser(): void
    {
        (new ReflectionProperty(Auth::class, 'user'))->setValue(null, null);
    }
}
