<?php

declare(strict_types=1);

/*
 * Bootstrap: laadt de autoloader, helpers en configuratie.
 * Wordt gebruikt door public/index.php (en later door tests).
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativePath = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relativePath . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set('Europe/Amsterdam');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

$logFile = BASE_PATH . '/storage/logs/php-error.log';
if (is_writable(dirname($logFile))) {
    ini_set('error_log', $logFile);
}
