<?php

declare(strict_types=1);

/**
 * Alle routes van SpendSmart: welke URL hoort bij welke controller-methode, en wie mag erbij.
 *
 * Opbouw van een regel:
 *   $router->get('/url', [Controller::class, 'methode'], [middleware]);
 *   - get  = pagina bekijken, post = formulier versturen (opslaan/wijzigen/verwijderen)
 *   - {id} = een getal uit de URL dat aan de methode wordt meegegeven
 *
 * Middleware: 'auth' = ingelogd, 'guest' = niet ingelogd, 'role:<rol>' = alleen voor die rol.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\Content\StatisticsController;
use App\Controllers\Content\SuggestionController;
use App\Controllers\Content\TipController as ContentTipController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\SavingsGoalController;
use App\Controllers\TipController;
use App\Controllers\TransactionController;
use App\Support\Role;

// Groepen middleware, zodat we ze niet bij elke route opnieuw hoeven te typen.
$guest = ['guest'];                                          // alleen als je NIET bent ingelogd
$loggedIn = ['auth'];                                        // iedereen die is ingelogd
$user = ['auth', 'role:' . Role::USER];                      // alleen gewone gebruikers
$contentManager = ['auth', 'role:' . Role::CONTENT_MANAGER]; // alleen contentbeheerders

// Openbaar: startpagina (iedereen)
$router->get('/', [HomeController::class, 'index']);

// Account: inloggen, registreren, uitloggen (FE-01, FE-02)
$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->get('/register', [AuthController::class, 'showRegister'], $guest);
$router->post('/register', [AuthController::class, 'register'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $loggedIn);

// Profiel: eigen gegevens beheren (FE-03). Account verwijderen mag alleen een gewone gebruiker.
$router->get('/profile', [ProfileController::class, 'edit'], $loggedIn);
$router->post('/profile', [ProfileController::class, 'update'], $loggedIn);
$router->post('/profile/password', [ProfileController::class, 'updatePassword'], $loggedIn);
$router->post('/profile/delete', [ProfileController::class, 'destroy'], $user);

// Gebruiker: dashboard met maandtotalen en budgetwaarschuwingen (FE-06, FE-09)
$router->get('/dashboard', [DashboardController::class, 'index'], $user);

// Gebruiker: transacties registreren en filteren (FE-04, FE-05)
$router->get('/transactions', [TransactionController::class, 'index'], $user);
$router->get('/transactions/create', [TransactionController::class, 'create'], $user);
$router->post('/transactions', [TransactionController::class, 'store'], $user);
$router->get('/transactions/{id}/edit', [TransactionController::class, 'edit'], $user);
$router->post('/transactions/{id}/update', [TransactionController::class, 'update'], $user);
$router->post('/transactions/{id}/delete', [TransactionController::class, 'destroy'], $user);

// Gebruiker: eigen categorieën en voorstellen overnemen (FE-07, FE-10)
$router->get('/categories', [CategoryController::class, 'index'], $user);
$router->get('/categories/create', [CategoryController::class, 'create'], $user);
$router->post('/categories', [CategoryController::class, 'store'], $user);
$router->get('/categories/{id}/edit', [CategoryController::class, 'edit'], $user);
$router->post('/categories/{id}/update', [CategoryController::class, 'update'], $user);
$router->post('/categories/{id}/delete', [CategoryController::class, 'destroy'], $user);
$router->post('/categories/adopt/{id}', [CategoryController::class, 'adopt'], $user);

// Gebruiker: spaardoelen (FE-08)
$router->get('/goals', [SavingsGoalController::class, 'index'], $user);
$router->get('/goals/create', [SavingsGoalController::class, 'create'], $user);
$router->post('/goals', [SavingsGoalController::class, 'store'], $user);
$router->get('/goals/{id}/edit', [SavingsGoalController::class, 'edit'], $user);
$router->post('/goals/{id}/update', [SavingsGoalController::class, 'update'], $user);
$router->post('/goals/{id}/deposit', [SavingsGoalController::class, 'deposit'], $user);
$router->post('/goals/{id}/delete', [SavingsGoalController::class, 'destroy'], $user);

// Gebruiker: gepubliceerde leerteksten lezen (FE-11)
$router->get('/tips', [TipController::class, 'index'], $user);

// Contentbeheerder: anonieme statistieken (FE-12)
$router->get('/content/statistics', [StatisticsController::class, 'index'], $contentManager);

// Contentbeheerder: leerteksten beheren en publiceren (FE-11)
$router->get('/content/tips', [ContentTipController::class, 'index'], $contentManager);
$router->get('/content/tips/create', [ContentTipController::class, 'create'], $contentManager);
$router->post('/content/tips', [ContentTipController::class, 'store'], $contentManager);
$router->get('/content/tips/{id}/edit', [ContentTipController::class, 'edit'], $contentManager);
$router->post('/content/tips/{id}/update', [ContentTipController::class, 'update'], $contentManager);
$router->post('/content/tips/{id}/delete', [ContentTipController::class, 'destroy'], $contentManager);

// Contentbeheerder: categorievoorstellen beheren (FE-10)
$router->get('/content/suggestions', [SuggestionController::class, 'index'], $contentManager);
$router->get('/content/suggestions/create', [SuggestionController::class, 'create'], $contentManager);
$router->post('/content/suggestions', [SuggestionController::class, 'store'], $contentManager);
$router->get('/content/suggestions/{id}/edit', [SuggestionController::class, 'edit'], $contentManager);
$router->post('/content/suggestions/{id}/update', [SuggestionController::class, 'update'], $contentManager);
$router->post('/content/suggestions/{id}/delete', [SuggestionController::class, 'destroy'], $contentManager);
