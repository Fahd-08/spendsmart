<?php

declare(strict_types=1);

/*
 * Kleine hulpfuncties die overal (vooral in de views) gebruikt worden.
 */

use App\Core\Csrf;
use App\Core\Request;
use App\Core\View;
use App\Support\Money;

/**
 * Maakt tekst veilig voor HTML-uitvoer (bescherming tegen XSS).
 * '<script>' wordt '&lt;script&gt;': de browser toont het als tekst en voert het niet uit.
 * Alles wat een gebruiker heeft ingevuld, gaat via e() naar de pagina.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Haalt een instelling op met puntnotatie, bijvoorbeeld config('db.host').
 */
function config(string $key, mixed $default = null): mixed
{
    // config.php maar één keer inlezen ('static' onthoudt de waarde tussen aanroepen).
    static $config = null;
    $config ??= require BASE_PATH . '/config/config.php';

    // 'db.host' -> eerst $config['db'], daarna ['host'].
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * Bouwt een URL binnen de app. Werkt ook als de app in een submap staat (XAMPP).
 * url('/transactions', ['month' => '2026-09']) -> '/Examen%20portfolio/public/transactions?month=2026-09'
 */
function url(string $path = '/', array $query = []): string
{
    // De map van de app, met spaties veilig gemaakt (%20).
    $basePath = implode('/', array_map('rawurlencode', explode('/', Request::basePath())));
    $url = $basePath . '/' . ltrim($path, '/');

    // Lege filters weglaten, zodat de URL kort blijft.
    $query = array_filter($query, static fn ($value) => $value !== null && $value !== '');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * URL naar een bestand in public/assets (CSS, JS, afbeelding).
 * ?v=<tijdstip> zorgt dat de browser een nieuwe versie ophaalt als het bestand verandert.
 */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : null;

    return url('/assets/' . ltrim($path, '/'), ['v' => $version]);
}

/**
 * Verborgen formulierveld met het CSRF-token. Staat in elk formulier met method="post".
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/**
 * Centen als euro tonen: 123456 -> '€ 1.234,56'.
 */
function money(int $cents): string
{
    return Money::format($cents);
}

/**
 * Centen voor in een invoerveld: 1250 -> '12,50' (of leeg).
 */
function money_input(?int $cents): string
{
    return $cents === null ? '' : Money::toInput($cents);
}

/**
 * Breedte van een voortgangsbalk in stappen van 5% (0-100), voor de CSS-klasse w-{n}.
 * Zo zijn geen inline styles nodig en blijft de Content-Security-Policy streng.
 */
function bar_width(int $percentage): int
{
    return max(0, min(100, (int) (round($percentage / 5) * 5)));
}

/**
 * Datum uit de database in Nederlandse notatie: '2026-09-30' -> '30-09-2026'.
 */
function format_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '';
    }

    // Alleen de eerste 10 tekens (de datum), zodat het ook werkt met '2026-09-30 14:00:00'.
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));

    return $parsed === false ? $date : $parsed->format('d-m-Y');
}

/**
 * Toont de foutmelding bij een formulierveld (of niets).
 */
function field_error(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
}

/**
 * Extra attributen voor een veld met een fout, zodat schermlezers de fout voorlezen (toegankelijkheid).
 */
function field_attributes(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return ' aria-invalid="true" aria-describedby="' . e($field) . '-error"';
}

/**
 * Is dit de huidige pagina (of een subpagina ervan)? Gebruikt om het menu-item te markeren.
 */
function is_active(string $prefix): bool
{
    $currentPath = View::shared('currentPath', '/');

    return $currentPath === $prefix || str_starts_with($currentPath, rtrim($prefix, '/') . '/');
}

/**
 * Korte schrijfwijze voor View::partial() in de templates.
 */
function partial(string $template, array $data = []): string
{
    return View::partial($template, $data);
}
