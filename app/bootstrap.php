<?php

declare(strict_types=1);

/*
 * Bootstrap: laadt de autoloader, helpers en configuratie.
 * Wordt gebruikt door public/index.php en door de tests (tests/bootstrap.php).
 */

// De hoofdmap van het project, zodat we overal paden kunnen bouwen (bijv. BASE_PATH . '/app').
define('BASE_PATH', dirname(__DIR__));

/*
 * Autoloader: als PHP een klasse nodig heeft (bijv. App\Core\Router), zoekt deze functie
 * het bestand erbij (app/Core/Router.php) en laadt het. Zo hoeven we nergens 'require' te schrijven.
 */
spl_autoload_register(static function (string $class): void {
    // Alleen onze eigen klassen (namespace App\...) laden.
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    // 'App\Core\Router' -> 'Core/Router' -> '/pad/naar/app/Core/Router.php'
    $relativePath = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relativePath . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// Kleine hulpfuncties die overal beschikbaar zijn, zoals e() en url().
require BASE_PATH . '/app/helpers.php';

// Geheime instellingen (databasewachtwoord) uit het .env-bestand lezen.
App\Core\Env::load(BASE_PATH . '/.env');

// Nederlandse tijd en UTF-8 (voor tekens zoals é en €).
date_default_timezone_set('Europe/Amsterdam');
mb_internal_encoding('UTF-8');

// Alle fouten melden. Alleen tonen in de browser als APP_DEBUG=true (lokaal), anders alleen loggen.
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

// Fouten opslaan in storage/logs/php-error.log (als die map schrijfbaar is).
$logFile = BASE_PATH . '/storage/logs/php-error.log';
if (is_writable(dirname($logFile))) {
    ini_set('error_log', $logFile);
}
