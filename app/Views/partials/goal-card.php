<?php
/**
 * Spaardoelkaart.
 *
 * @var array $goal
 * @var bool $withActions
 */

use App\Support\Money;

$target = (int) $goal['target_cents'];
$saved = (int) $goal['saved_cents'];
$percentage = Money::percentage($saved, $target);
$width = bar_width($percentage);
$isReached = $saved >= $target;
$withActions ??= false;
?>
<article class="card goal-card">
    <div class="goal-card__header">
        <h3 class="goal-card__title"><?= e($goal['name']) ?></h3>
        <span class="status <?= $isReached ? 'status--ok' : 'status--progress' ?>">
            <?= $isReached ? 'Doel bereikt' : 'Onderweg' ?>
        </span>
    </div>

    <p class="goal-card__amount">
        <strong><?= e(money($saved)) ?></strong> van <?= e(money($target)) ?>
    </p>

    <div class="bar" role="img" aria-label="<?= e($percentage . '% van het doel gespaard') ?>">
        <div class="bar__fill <?= $isReached ? 'bar__fill--ok' : 'bar__fill--progress' ?> w-<?= $width ?>"></div>
    </div>

    <p class="goal-card__meta">
        <?= e($percentage) ?>% gespaard
        <?php if (!$isReached): ?>
            &middot; nog <?= e(money($target - $saved)) ?> te gaan
        <?php endif; ?>
        <?php if ($goal['target_date'] !== null): ?>
            &middot; streefdatum <?= e(format_date($goal['target_date'])) ?>
        <?php endif; ?>
    </p>

    <?php if ($withActions): ?>
        <form class="goal-card__deposit" method="post" action="<?= e(url('/goals/' . $goal['id'] . '/deposit')) ?>">
            <?= csrf_field() ?>
            <label for="deposit-<?= e($goal['id']) ?>">Bedrag toevoegen</label>
            <div class="input-group">
                <span class="input-group__prefix" aria-hidden="true">€</span>
                <input type="text" inputmode="decimal" id="deposit-<?= e($goal['id']) ?>" name="amount" placeholder="25,00" required maxlength="12">
                <button type="submit" class="button button--secondary button--small">Toevoegen</button>
            </div>
        </form>

        <div class="card__actions">
            <a class="button button--ghost button--small" href="<?= e(url('/goals/' . $goal['id'] . '/edit')) ?>">Wijzigen</a>
            <?= partial('delete-button', [
                'action' => url('/goals/' . $goal['id'] . '/delete'),
                'confirm' => 'Weet je zeker dat je spaardoel "' . $goal['name'] . '" wilt verwijderen?',
            ]) ?>
        </div>
    <?php endif; ?>
</article>
