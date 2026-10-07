<?php

declare(strict_types=1);

/*
 * Alle instellingen van de app op één plek. Op te vragen met config('sectie.naam'),
 * bijvoorbeeld config('db.host'). Geheime waarden komen uit .env (via Env::get).
 * De tweede waarde bij Env::get is de standaardwaarde als .env die instelling niet heeft.
 */

use App\Core\Env;

return [
    'app' => [
        'name' => 'SpendSmart',
        'env' => Env::get('APP_ENV', 'production'),
        // Debug alleen aan als er letterlijk APP_DEBUG=true in .env staat (lokaal).
        'debug' => Env::get('APP_DEBUG', 'false') === 'true',
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'spendsmart'),
        'user' => Env::get('DB_USER', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],
    'security' => [
        // Na dit aantal mislukte inlogpogingen wordt tijdelijk geblokkeerd.
        'max_login_attempts' => 5,
        'login_lockout_minutes' => 15,
    ],
    'budget' => [
        // Vanaf dit percentage van de limiet krijgt een categorie de status "bijna op".
        'warning_percentage' => 80,
    ],
];
