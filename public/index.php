<?php

declare(strict_types=1);

/*
 * Front controller: elk verzoek komt hier binnen en wordt door de router
 * naar de juiste controller gestuurd.
 */

use App\Core\ErrorHandler;
use App\Core\RedirectException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

require dirname(__DIR__) . '/app/bootstrap.php';

Response::sendSecurityHeaders();
Session::start();

$request = Request::fromGlobals();
View::share('currentPath', $request->path());

$router = new Router();
require BASE_PATH . '/routes/web.php';

try {
    $router->dispatch($request);
} catch (RedirectException $redirect) {
    $redirect->send();
} catch (Throwable $exception) {
    ErrorHandler::handle($exception);
}
