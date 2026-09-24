<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Request;
use App\Core\View;
use App\Support\Money;

/**
 * Maakt tekst veilig voor HTML-uitvoer (bescherming tegen XSS).
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
    static $config = null;
    $config ??= require BASE_PATH . '/config/config.php';

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
 */
function url(string $path = '/', array $query = []): string
{
    $basePath = implode('/', array_map('rawurlencode', explode('/', Request::basePath())));
    $url = $basePath . '/' . ltrim($path, '/');

    $query = array_filter($query, static fn ($value) => $value !== null && $value !== '');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : null;

    return url('/assets/' . ltrim($path, '/'), ['v' => $version]);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function money(int $cents): string
{
    return Money::format($cents);
}

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

function format_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '';
    }

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
 * Extra attributen voor een veld met een fout, zodat schermlezers de fout voorlezen.
 */
function field_attributes(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return ' aria-invalid="true" aria-describedby="' . e($field) . '-error"';
}

function is_active(string $prefix): bool
{
    $currentPath = View::shared('currentPath', '/');

    return $currentPath === $prefix || str_starts_with($currentPath, rtrim($prefix, '/') . '/');
}

function partial(string $template, array $data = []): string
{
    return View::partial($template, $data);
}
