<?php
/**
 * Budgetbalk voor één uitgavencategorie. Status met kleur én tekst.
 *
 * @var array $line zie BudgetService::overview()
 */

// Breedte van de balk in stappen van 5%, via CSS-klasse w-0 t/m w-100 (geen inline style nodig).
$width = bar_width($line['percentage']);
?>
<div class="budget">
    <div class="budget__header">
        <span class="budget__name"><?= e($line['name']) ?></span>
        <span class="status status--<?= e($line['status']) ?>"><?= e($line['status_label']) ?></span>
    </div>

    <?php // Met limiet: balk en hoeveel er nog over is (of hoeveel je erboven zit). ?>
    <?php if ($line['budget_cents'] !== null): ?>
        <div class="bar" role="img" aria-label="<?= e($line['percentage'] . '% van de limiet gebruikt') ?>">
            <div class="bar__fill bar__fill--<?= e($line['status']) ?> w-<?= $width ?>"></div>
        </div>
        <p class="budget__meta">
            <?= e(money($line['spent_cents'])) ?> van <?= e(money($line['budget_cents'])) ?>
            (<?= e($line['percentage']) ?>%) &middot;
            <?php if ($line['remaining_cents'] >= 0): ?>
                nog <?= e(money($line['remaining_cents'])) ?> binnen je limiet
            <?php else: ?>
                <?= e(money(-$line['remaining_cents'])) ?> boven je limiet
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p class="budget__meta"><?= e(money($line['spent_cents'])) ?> uitgegeven &middot; geen maandlimiet ingesteld</p>
    <?php endif; ?>
</div>
