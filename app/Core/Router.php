<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Koppelt URL's aan controller-methodes en voert per route de middleware uit
 * (inloggen verplicht, rolcontrole). Elk POST-verzoek wordt gecontroleerd op een geldig CSRF-token.
 *
 * Voorbeeld uit routes/web.php:
 *   $router->get('/transactions/{id}/edit', [TransactionController::class, 'edit'], ['auth']);
 *   Bij de URL /transactions/5/edit wordt TransactionController->edit(5) aangeroepen.
 */
final class Router
{
    /**
     * Lijst met alle routes. Elke route heeft een methode (GET/POST), een patroon (regex),
     * een controller + methode en een lijst met middleware.
     *
     * @var array<int, array{method: string, pattern: string, handler: array{0: class-string, 1: string}, middleware: string[]}>
     */
    private array $routes = [];

    /**
     * Route voor het opvragen van een pagina (link of adresbalk).
     */
    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * Route voor het versturen van een formulier (opslaan, wijzigen, verwijderen).
     */
    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * Zoekt de route die bij het verzoek past en voert die uit.
     * Gooit een HttpException (404, 405 of 419) als er iets niet klopt.
     */
    public function dispatch(Request $request): void
    {
        $path = $request->path();       // bijv. '/transactions/5/edit'
        $method = $request->method();   // 'GET' of 'POST'
        $pathExists = false;            // onthouden of de URL wel bestaat (voor 405 i.p.v. 404)

        foreach ($this->routes as $route) {
            // Past de URL bij het patroon van deze route? Zo niet: volgende route proberen.
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            // De URL bestaat, maar misschien met een andere methode (bijv. GET op een POST-route).
            $pathExists = true;
            if ($route['method'] !== $method) {
                continue;
            }

            // CSRF-bescherming: elk formulier moet het geheime token uit de sessie meesturen.
            if ($method === 'POST' && !Csrf::verify($request->input('_token'))) {
                throw new HttpException(419, 'Je sessie is verlopen of het formulier is ongeldig. Ga terug, vernieuw de pagina en probeer het opnieuw.');
            }

            // Toegangscontrole uitvoeren, bijv. 'auth' (ingelogd) en 'role:user' (juiste rol).
            foreach ($route['middleware'] as $middleware) {
                Middleware::run($middleware);
            }

            // Waarden uit de URL (zoals het id 5) als getal ophalen.
            // Op volgorde doorgeven, zodat de naam van de methode-parameter vrij is.
            $parameters = array_values(array_map(
                'intval',
                array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)
            ));

            // De controller maken en de juiste methode aanroepen, bijv. (new TransactionController)->edit(5).
            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass($request);
            $controller->$action(...$parameters);

            return;
        }

        // De URL bestaat wel, maar niet met deze methode.
        if ($pathExists) {
            throw new HttpException(405, 'Deze actie is op deze manier niet toegestaan.');
        }

        // Geen enkele route past: pagina bestaat niet.
        throw new HttpException(404, 'Deze pagina bestaat niet.');
    }

    /**
     * Zet een route om naar een regex en bewaart hem in de lijst.
     */
    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        // {id} wordt een verplicht positief getal (1 t/m 10 cijfers, niet beginnend met 0).
        // '/transactions/{id}/edit' -> '/transactions/(?P<id>[1-9][0-9]{0,9})/edit'
        $pattern = preg_replace('#\{([a-zA-Z]+)\}#', '(?P<$1>[1-9][0-9]{0,9})', $path);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#', // ^ en $: de hele URL moet passen, niet een stukje
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
}
