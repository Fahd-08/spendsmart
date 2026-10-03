<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Koppelt URL's aan controller-methodes en voert per route de middleware uit
 * (inloggen verplicht, rolcontrole). Elk POST-verzoek wordt gecontroleerd op een geldig CSRF-token.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: array{0: class-string, 1: string}, middleware: string[]}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();
        $pathExists = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $pathExists = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST' && !Csrf::verify($request->input('_token'))) {
                throw new HttpException(419, 'Je sessie is verlopen of het formulier is ongeldig. Ga terug, vernieuw de pagina en probeer het opnieuw.');
            }

            foreach ($route['middleware'] as $middleware) {
                Middleware::run($middleware);
            }

            // Waarden uit de URL op volgorde doorgeven, zodat de naam van de methode-parameter vrij is.
            $parameters = array_values(array_map(
                'intval',
                array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)
            ));

            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass($request);
            $controller->$action(...$parameters);

            return;
        }

        if ($pathExists) {
            throw new HttpException(405, 'Deze actie is op deze manier niet toegestaan.');
        }

        throw new HttpException(404, 'Deze pagina bestaat niet.');
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        // {id} wordt een verplicht positief getal.
        $pattern = preg_replace('#\{([a-zA-Z]+)\}#', '(?P<$1>[1-9][0-9]{0,9})', $path);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
}
