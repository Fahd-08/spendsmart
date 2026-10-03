<?php
/**
 * @var array $categories
 * @var array $suggestions
 */

use App\Support\CategoryType;
?>
<div class="page-header">
    <div>
        <h1>Categorieën</h1>
        <p class="muted">Stel bij uitgaven een maandlimiet in. Je krijgt een melding als je daar boven komt.</p>
    </div>
    <a class="button button--primary" href="<?= e(url('/categories/create')) ?>">Categorie toevoegen</a>
</div>

<?php if ($categories === []): ?>
    <?= partial('empty-state', [
        'text' => 'Je hebt nog geen categorieën. Maak er een aan of neem hieronder een voorstel over.',
        'actionUrl' => url('/categories/create'),
        'actionLabel' => 'Categorie toevoegen',
    ]) ?>
<?php else: ?>
    <?php foreach (CategoryType::ALL as $type): ?>
        <?php $categoriesOfType = array_filter($categories, static fn (array $category): bool => $category['type'] === $type); ?>
        <section class="card section" aria-labelledby="categories-<?= e($type) ?>">
            <h2 id="categories-<?= e($type) ?>"><?= e(CategoryType::pluralLabel($type)) ?></h2>

            <?php if ($categoriesOfType === []): ?>
                <p class="muted">Nog geen <?= e(mb_strtolower(CategoryType::label($type))) ?>categorieën.</p>
            <?php else: ?>
                <ul class="compact-list">
                    <?php foreach ($categoriesOfType as $category): ?>
                        <li class="compact-list__item">
                            <div>
                                <span class="chip chip--<?= e($category['type']) ?>"><?= e($category['name']) ?></span>
                                <span class="muted">
                                    <?php if ($type === CategoryType::EXPENSE): ?>
                                        <?= $category['monthly_budget_cents'] === null
                                            ? 'Geen maandlimiet'
                                            : 'Limiet ' . e(money((int) $category['monthly_budget_cents'])) . ' per maand' ?> &middot;
                                    <?php endif; ?>
                                    <?= (int) $category['transaction_count'] ?> <?= (int) $category['transaction_count'] === 1 ? 'transactie' : 'transacties' ?>
                                </span>
                            </div>
                            <div class="table__actions">
                                <a class="button button--ghost button--small" href="<?= e(url('/categories/' . $category['id'] . '/edit')) ?>">Wijzigen</a>
                                <?= partial('delete-button', [
                                    'action' => url('/categories/' . $category['id'] . '/delete'),
                                    'confirm' => 'Weet je zeker dat je categorie "' . $category['name'] . '" wilt verwijderen?',
                                ]) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<section class="card section" aria-labelledby="suggestions-title">
    <h2 id="suggestions-title">Voorstellen van MoneyMinds</h2>

    <?php if ($suggestions === []): ?>
        <p class="muted">Je hebt alle beschikbare voorstellen al overgenomen.</p>
    <?php else: ?>
        <p class="muted">Klik op een voorstel om het toe te voegen aan je eigen categorieën.</p>
        <div class="chips">
            <?php foreach ($suggestions as $suggestion): ?>
                <form method="post" action="<?= e(url('/categories/adopt/' . $suggestion['id'])) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="chip chip--<?= e($suggestion['type']) ?> chip--button" title="<?= e((string) $suggestion['description']) ?>">
                        + <?= e($suggestion['name']) ?> <span class="visually-hidden">(<?= e(mb_strtolower(CategoryType::label($suggestion['type']))) ?>) toevoegen</span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
