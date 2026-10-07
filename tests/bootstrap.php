<?php

declare(strict_types=1);

/*
 * Wordt één keer uitgevoerd voordat de tests starten (ingesteld in phpunit.xml).
 */

use Tests\Support\TestDatabase;

// De app draait in de tests alsof public/ de document root is (zoals op Plesk). Dan maakt url('/login') gewoon '/login'.
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Sessies zonder cookies en headers, zodat ze ook op de command line werken (daar is geen browser).
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.cache_limiter', '');
session_save_path(sys_get_temp_dir());

// Eerst de autoloader van Composer (voor PHPUnit en de testklassen), dan de app zelf.
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';

session_start();

// Testdatabase aanmaken (als die nog niet bestaat) en de tabellen opnieuw opbouwen.
TestDatabase::prepare();
