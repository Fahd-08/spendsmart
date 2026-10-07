<?php
/**
 * Maandfilter met vorige/volgende maand. Andere filters blijven behouden.
 *
 * @var App\Support\Month $month
 * @var string $path
 * @var array $query extra filterwaarden
 */

$query ??= [];
?>
<form class="month-filter" method="get" action="<?= e(url($path)) ?>" aria-label="Maand kiezen">
    <?php // Link naar de vorige maand; + $query houdt de andere filters vast. ?>
    <a class="button button--ghost button--small" href="<?= e(url($path, ['month' => $month->previous()->key()] + $query)) ?>" aria-label="Vorige maand">&larr;</a>

    <label class="visually-hidden" for="month">Maand</label>
    <?php // Maand kiezen met de ingebouwde maandkiezer van de browser. ?>
    <input class="month-filter__input" type="month" id="month" name="month" value="<?= e($month->key()) ?>" min="2000-01" max="2100-12">
    <?php // Andere filters als verborgen velden meesturen. ?>
    <?php foreach ($query as $name => $value): ?>
        <?php if ($value !== null && $value !== ''): ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <button class="button button--secondary button--small" type="submit">Toon</button>

    <a class="button button--ghost button--small" href="<?= e(url($path, ['month' => $month->next()->key()] + $query)) ?>" aria-label="Volgende maand">&rarr;</a>
</form>
