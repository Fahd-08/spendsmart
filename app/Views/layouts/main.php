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

// Ingelogde gebruiker ophalen voor het menu.
try {
    $currentUser = Auth::user();
} catch (Throwable) {
    $currentUser = null; // Bijvoorbeeld als de database niet bereikbaar is.
}

// Menu-items per rol: gebruiker, contentbeheerder of bezoeker (niet ingelogd).
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
    <?php // CSS en JavaScript als los bestand (geen inline code), zodat de Content-Security-Policy streng kan blijven. ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<?php // Toegankelijkheid: met Tab direct naar de inhoud springen, zonder door het hele menu te gaan. ?>
<a class="skip-link" href="#main">Direct naar de inhoud</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <?php // Logo: een munt met staafjes die oplopen, zoals een groeiend spaarbedrag. ?>
            <svg class="brand__mark" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                <circle cx="16" cy="16" r="15" fill="#FDE68A"/>
                <rect x="9" y="17" width="3.5" height="6" rx="1" fill="#0F766E"/>
                <rect x="14.25" y="13" width="3.5" height="10" rx="1" fill="#0F766E"/>
                <rect x="19.5" y="9" width="3.5" height="14" rx="1" fill="#0F766E"/>
            </svg>
            <span class="brand__name">SpendSmart</span>
        </a>

        <?php // Menuknop voor mobiel; app.js klapt het menu open en dicht. ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>

        <nav id="main-nav" class="main-nav" aria-label="Hoofdmenu">
            <ul class="main-nav__list">
                <?php // Elk menu-item; aria-current markeert de pagina waar je nu bent. ?>
                <?php foreach ($navigation as $path => $label): ?>
                    <li>
                        <a class="main-nav__link" href="<?= e(url($path)) ?>"<?= is_active($path) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php // Rol en uitlogknop alleen als iemand is ingelogd. Uitloggen is een POST-formulier met CSRF-token. ?>
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
    <?php // Meldingen (bijv. 'is opgeslagen') tonen en daarna wissen. ?>
    <?php foreach (Flash::consume() as $message): ?>
        <?= partial('alert', ['type' => $message['type'], 'text' => $message['text']]) ?>
    <?php endforeach; ?>

    <?php // Hier komt de HTML van de pagina zelf (bijv. het dashboard). ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>SpendSmart is een leeromgeving van MoneyMinds. Je werkt alleen met zelf ingevoerde oefengegevens. Er is geen bankkoppeling en SpendSmart geeft geen financieel advies.</p>
    </div>
</footer>
</body>
</html>
