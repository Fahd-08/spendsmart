<?php

declare(strict_types=1);

/**
 * Alle routes van SpendSmart.
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

$guest = ['guest'];
$loggedIn = ['auth'];
$user = ['auth', 'role:' . Role::USER];
$contentManager = ['auth', 'role:' . Role::CONTENT_MANAGER];

// Openbaar
$router->get('/', [HomeController::class, 'index']);

// Account
$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->get('/register', [AuthController::class, 'showRegister'], $guest);
$router->post('/register', [AuthController::class, 'register'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $loggedIn);

$router->get('/profile', [ProfileController::class, 'edit'], $loggedIn);
$router->post('/profile', [ProfileController::class, 'update'], $loggedIn);
$router->post('/profile/password', [ProfileController::class, 'updatePassword'], $loggedIn);
$router->post('/profile/delete', [ProfileController::class, 'destroy'], $user);

// Gebruiker
$router->get('/dashboard', [DashboardController::class, 'index'], $user);

$router->get('/transactions', [TransactionController::class, 'index'], $user);
$router->get('/transactions/create', [TransactionController::class, 'create'], $user);
$router->post('/transactions', [TransactionController::class, 'store'], $user);
$router->get('/transactions/{id}/edit', [TransactionController::class, 'edit'], $user);
$router->post('/transactions/{id}/update', [TransactionController::class, 'update'], $user);
$router->post('/transactions/{id}/delete', [TransactionController::class, 'destroy'], $user);

$router->get('/categories', [CategoryController::class, 'index'], $user);
$router->get('/categories/create', [CategoryController::class, 'create'], $user);
$router->post('/categories', [CategoryController::class, 'store'], $user);
$router->get('/categories/{id}/edit', [CategoryController::class, 'edit'], $user);
$router->post('/categories/{id}/update', [CategoryController::class, 'update'], $user);
$router->post('/categories/{id}/delete', [CategoryController::class, 'destroy'], $user);
$router->post('/categories/adopt/{id}', [CategoryController::class, 'adopt'], $user);

$router->get('/goals', [SavingsGoalController::class, 'index'], $user);
$router->get('/goals/create', [SavingsGoalController::class, 'create'], $user);
$router->post('/goals', [SavingsGoalController::class, 'store'], $user);
$router->get('/goals/{id}/edit', [SavingsGoalController::class, 'edit'], $user);
$router->post('/goals/{id}/update', [SavingsGoalController::class, 'update'], $user);
$router->post('/goals/{id}/deposit', [SavingsGoalController::class, 'deposit'], $user);
$router->post('/goals/{id}/delete', [SavingsGoalController::class, 'destroy'], $user);

$router->get('/tips', [TipController::class, 'index'], $user);

// Contentbeheerder
$router->get('/content/statistics', [StatisticsController::class, 'index'], $contentManager);

$router->get('/content/tips', [ContentTipController::class, 'index'], $contentManager);
$router->get('/content/tips/create', [ContentTipController::class, 'create'], $contentManager);
$router->post('/content/tips', [ContentTipController::class, 'store'], $contentManager);
$router->get('/content/tips/{id}/edit', [ContentTipController::class, 'edit'], $contentManager);
$router->post('/content/tips/{id}/update', [ContentTipController::class, 'update'], $contentManager);
$router->post('/content/tips/{id}/delete', [ContentTipController::class, 'destroy'], $contentManager);

$router->get('/content/suggestions', [SuggestionController::class, 'index'], $contentManager);
$router->get('/content/suggestions/create', [SuggestionController::class, 'create'], $contentManager);
$router->post('/content/suggestions', [SuggestionController::class, 'store'], $contentManager);
$router->get('/content/suggestions/{id}/edit', [SuggestionController::class, 'edit'], $contentManager);
$router->post('/content/suggestions/{id}/update', [SuggestionController::class, 'update'], $contentManager);
$router->post('/content/suggestions/{id}/delete', [SuggestionController::class, 'destroy'], $contentManager);
