<?php
/**
 * @var array $user
 * @var App\Support\Month $month
 * @var array{income: int, expense: int} $totals
 * @var int $balance
 * @var array $budgets
 * @var array $exceeded
 * @var array $goals
 * @var array $recentTransactions
 * @var array|null $tip
 */

use App\Services\BudgetService;
use App\Support\CategoryType;

$budgetsWithLimit = array_filter($budgets, static fn (array $line): bool => $line['budget_cents'] !== null);
?>
<div class="page-header">
    <div>
        <h1>Hoi <?= e($user['name']) ?></h1>
        <p class="muted">Overzicht van <?= e($month->label()) ?></p>
    </div>
    <?= partial('month-filter', ['month' => $month, 'path' => '/dashboard']) ?>
</div>

<?php foreach ($exceeded as $line): ?>
    <?= partial('alert', [
        'type' => 'warning',
        'text' => BudgetService::overLimitMessage($line['name'], $month, $line['spent_cents'], $line['budget_cents']),
    ]) ?>
<?php endforeach; ?>

<section class="stats" aria-label="Maandtotalen">
    <div class="stat card">
        <p class="stat__label">Inkomsten</p>
        <p class="stat__value amount--income"><?= e(money($totals['income'])) ?></p>
    </div>
    <div class="stat card">
        <p class="stat__label">Uitgaven</p>
        <p class="stat__value amount--expense"><?= e(money($totals['expense'])) ?></p>
    </div>
    <div class="stat card">
        <p class="stat__label">Saldo deze maand</p>
        <p class="stat__value"><?= e(money($balance)) ?></p>
        <span class="status <?= $balance >= 0 ? 'status--ok' : 'status--over' ?>">
            <?= $balance >= 0 ? 'Meer ontvangen dan uitgegeven' : 'Meer uitgegeven dan ontvangen' ?>
        </span>
    </div>
</section>

<div class="dashboard-grid">
    <section class="card" aria-labelledby="budgets-title">
        <div class="section-header">
            <h2 id="budgets-title">Limieten per categorie</h2>
            <a href="<?= e(url('/categories')) ?>">Limieten instellen</a>
        </div>

        <?php if ($budgetsWithLimit === []): ?>
            <?= partial('empty-state', [
                'text' => 'Je hebt nog geen maandlimieten ingesteld. Stel een limiet in bij een uitgavencategorie om hier je voortgang te zien.',
                'actionUrl' => url('/categories'),
                'actionLabel' => 'Naar categorieën',
            ]) ?>
        <?php else: ?>
            <?php foreach ($budgetsWithLimit as $line): ?>
                <?= partial('budget-bar', ['line' => $line]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="recent-title">
        <div class="section-header">
            <h2 id="recent-title">Laatste transacties</h2>
            <a href="<?= e(url('/transactions', ['month' => $month->key()])) ?>">Alles bekijken</a>
        </div>

        <?php if ($recentTransactions === []): ?>
            <?= partial('empty-state', [
                'text' => 'Nog geen inkomsten of uitgaven in ' . $month->label() . '.',
                'actionUrl' => url('/transactions/create'),
                'actionLabel' => 'Transactie toevoegen',
            ]) ?>
        <?php else: ?>
            <ul class="compact-list">
                <?php foreach ($recentTransactions as $transaction): ?>
                    <li class="compact-list__item">
                        <div>
                            <span class="compact-list__title"><?= e($transaction['description'] ?: $transaction['category_name']) ?></span>
                            <span class="muted"><?= e(format_date($transaction['transaction_date'])) ?> &middot; <?= e($transaction['category_name']) ?></span>
                        </div>
                        <span class="amount amount--<?= e($transaction['type']) ?>">
                            <?= $transaction['type'] === CategoryType::INCOME ? '+' : '-' ?> <?= e(money((int) $transaction['amount_cents'])) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="button button--primary button--small" href="<?= e(url('/transactions/create')) ?>">Transactie toevoegen</a>
        <?php endif; ?>
    </section>
</div>

<section aria-labelledby="goals-title" class="section">
    <div class="section-header">
        <h2 id="goals-title">Spaardoelen</h2>
        <a href="<?= e(url('/goals')) ?>">Alle spaardoelen</a>
    </div>

    <?php if ($goals === []): ?>
        <?= partial('empty-state', [
            'text' => 'Je hebt nog geen spaardoelen.',
            'actionUrl' => url('/goals/create'),
            'actionLabel' => 'Spaardoel toevoegen',
        ]) ?>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($goals as $goal): ?>
                <?= partial('goal-card', ['goal' => $goal]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($tip !== null): ?>
    <?= partial('tip-card', ['tip' => $tip]) ?>
<?php endif; ?>
