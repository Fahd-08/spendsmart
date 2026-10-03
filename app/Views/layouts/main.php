<?php
/**
 * Hoofdlayout met navigatie per rol en meldingen.
 *
 * @var string $content
 * @var string|null $title
 */

use App\Core\Auth;
use App\Core\Flash;
use App\Support\Role;

try {
    $currentUser = Auth::user();
} catch (Throwable) {
    $currentUser = null; // Bijvoorbeeld als de database niet bereikbaar is.
}

$navigation = match ($currentUser['role'] ?? null) {
    Role::USER => [
        '/dashboard' => 'Dashboard',
        '/transactions' => 'Transacties',
        '/categories' => 'Categorieën',
        '/goals' => 'Spaardoelen',
        '/tips' => 'Tips',
        '/profile' => 'Profiel',
    ],
    Role::CONTENT_MANAGER => [
        '/content/statistics' => 'Statistieken',
        '/content/tips' => 'Leerteksten',
        '/content/suggestions' => 'Categorievoorstellen',
        '/profile' => 'Profiel',
    ],
    default => [
        '/login' => 'Inloggen',
        '/register' => 'Account maken',
    ],
};
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SpendSmart: oefen met je budget. Alleen oefengegevens, geen bankkoppeling en geen financieel advies.">
    <title><?= e(isset($title) ? $title . ' · SpendSmart' : 'SpendSmart') ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Direct naar de inhoud</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand__mark" aria-hidden="true">S</span>
            <span class="brand__name">SpendSmart</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>

        <nav id="main-nav" class="main-nav" aria-label="Hoofdmenu">
            <ul class="main-nav__list">
                <?php foreach ($navigation as $path => $label): ?>
                    <li>
                        <a class="main-nav__link" href="<?= e(url($path)) ?>"<?= is_active($path) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($currentUser !== null): ?>
                <div class="main-nav__account">
                    <span class="role-badge"><?= e(Role::label($currentUser['role'])) ?></span>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="button button--ghost button--small">Uitloggen</button>
                    </form>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main" class="container main">
    <?php foreach (Flash::consume() as $message): ?>
        <?= partial('alert', ['type' => $message['type'], 'text' => $message['text']]) ?>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>SpendSmart is een leeromgeving van MoneyMinds. Je werkt alleen met zelf ingevoerde oefengegevens. Er is geen bankkoppeling en SpendSmart geeft geen financieel advies.</p>
    </div>
</footer>
</body>
</html>
