<?php
/**
 * @var App\Support\Month $month
 * @var array $categories
 * @var int|null $selectedCategoryId
 * @var string|null $selectedType
 * @var array $transactions
 * @var array{income: int, expense: int} $totals
 * @var bool $isFiltered
 */

use App\Support\CategoryType;

$filterQuery = ['category' => $selectedCategoryId, 'type' => $selectedType];
?>
<div class="page-header">
    <div>
        <h1>Transacties</h1>
        <p class="muted"><?= e($month->label()) ?></p>
    </div>
    <a class="button button--primary" href="<?= e(url('/transactions/create')) ?>">Transactie toevoegen</a>
</div>

<section class="card filters" aria-label="Filters">
    <?= partial('month-filter', ['month' => $month, 'path' => '/transactions', 'query' => $filterQuery]) ?>

    <form method="get" action="<?= e(url('/transactions')) ?>" class="filters__form">
        <input type="hidden" name="month" value="<?= e($month->key()) ?>">

        <div class="field field--inline">
            <label for="filter-type">Soort</label>
            <select id="filter-type" name="type">
                <option value="">Alles</option>
                <?php foreach (CategoryType::ALL as $type): ?>
                    <option value="<?= e($type) ?>"<?= $selectedType === $type ? ' selected' : '' ?>><?= e(CategoryType::pluralLabel($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field field--inline">
            <label for="filter-category">Categorie</label>
            <select id="filter-category" name="category">
                <option value="">Alle categorieën</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>"<?= $selectedCategoryId === (int) $category['id'] ? ' selected' : '' ?>>
                        <?= e($category['name']) ?> (<?= e(mb_strtolower(CategoryType::label($category['type']))) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="button button--secondary button--small">Filter toepassen</button>
        <?php if ($isFiltered): ?>
            <a class="button button--ghost button--small" href="<?= e(url('/transactions', ['month' => $month->key()])) ?>">Filters wissen</a>
        <?php endif; ?>
    </form>

    <div class="chips" aria-label="Snel filteren op categorie">
        <?php foreach ($categories as $category): ?>
            <a class="chip chip--<?= e($category['type']) ?><?= $selectedCategoryId === (int) $category['id'] ? ' chip--active' : '' ?>"
               href="<?= e(url('/transactions', ['month' => $month->key(), 'category' => $category['id']])) ?>"
               <?= $selectedCategoryId === (int) $category['id'] ? 'aria-current="true"' : '' ?>><?= e($category['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($transactions === []): ?>
    <?= partial('empty-state', [
        'text' => $isFiltered
            ? 'Geen transacties gevonden in ' . $month->label() . ' met deze filters.'
            : 'Je hebt nog geen inkomsten of uitgaven in ' . $month->label() . '.',
        'actionUrl' => url('/transactions/create'),
        'actionLabel' => 'Transactie toevoegen',
    ]) ?>
<?php else: ?>
    <section class="card">
        <div class="totals-row">
            <span>Inkomsten: <strong class="amount--income"><?= e(money($totals['income'])) ?></strong></span>
            <span>Uitgaven: <strong class="amount--expense"><?= e(money($totals['expense'])) ?></strong></span>
            <span>Verschil: <strong><?= e(money($totals['income'] - $totals['expense'])) ?></strong></span>
            <span class="muted"><?= count($transactions) ?> <?= count($transactions) === 1 ? 'transactie' : 'transacties' ?></span>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <caption class="visually-hidden">Transacties in <?= e($month->label()) ?></caption>
                <thead>
                <tr>
                    <th scope="col">Datum</th>
                    <th scope="col">Omschrijving</th>
                    <th scope="col">Categorie</th>
                    <th scope="col">Soort</th>
                    <th scope="col" class="table__number">Bedrag</th>
                    <th scope="col"><span class="visually-hidden">Acties</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td data-label="Datum"><?= e(format_date($transaction['transaction_date'])) ?></td>
                        <td data-label="Omschrijving"><?= e($transaction['description'] ?: '—') ?></td>
                        <td data-label="Categorie"><span class="chip chip--<?= e($transaction['type']) ?>"><?= e($transaction['category_name']) ?></span></td>
                        <td data-label="Soort"><?= e(CategoryType::label($transaction['type'])) ?></td>
                        <td data-label="Bedrag" class="table__number amount amount--<?= e($transaction['type']) ?>">
                            <?= $transaction['type'] === CategoryType::INCOME ? '+' : '-' ?> <?= e(money((int) $transaction['amount_cents'])) ?>
                        </td>
                        <td class="table__actions">
                            <a class="button button--ghost button--small" href="<?= e(url('/transactions/' . $transaction['id'] . '/edit')) ?>">Wijzigen</a>
                            <?= partial('delete-button', [
                                'action' => url('/transactions/' . $transaction['id'] . '/delete'),
                                'confirm' => 'Weet je zeker dat je deze transactie wilt verwijderen?',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
