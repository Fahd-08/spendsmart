<?php

declare(strict_types=1);

/*
 * Front controller: elk verzoek komt hier binnen en wordt door de router
 * naar de juiste controller gestuurd.
 *
 * Volgorde bij elk verzoek:
 *   1. bootstrap laden (autoloader, instellingen)
 *   2. beveiligingsheaders sturen en de sessie starten
 *   3. routes inladen en de router de juiste controller laten kiezen
 *   4. een redirect of fout netjes afhandelen
 */

use App\Core\ErrorHandler;
use App\Core\RedirectException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// Stap 1: autoloader, helpers en instellingen uit .env laden.
require dirname(__DIR__) . '/app/bootstrap.php';

// Stap 2: beveiligingsheaders (o.a. tegen XSS en clickjacking) en de sessie (wie is ingelogd).
Response::sendSecurityHeaders();
Session::start();

// Het verzoek van de browser (URL, formulierdata) in één object stoppen.
$request = Request::fromGlobals();

// De huidige pagina doorgeven aan de views, zodat het menu de actieve pagina kan markeren.
View::share('currentPath', $request->path());

// Stap 3: alle routes registreren (in routes/web.php staat de variabele $router).
$router = new Router();
require BASE_PATH . '/routes/web.php';

try {
    // De router zoekt de route die bij de URL past en roept de controller aan.
    $router->dispatch($request);
} catch (RedirectException $redirect) {
    // Stap 4a: de controller wil doorsturen naar een andere pagina.
    $redirect->send();
} catch (Throwable $exception) {
    // Stap 4b: er ging iets mis (bijv. 404 of databasefout): nette foutpagina tonen.
    ErrorHandler::handle($exception);
}
