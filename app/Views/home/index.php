<?php
/**
 * @var array|null $tip
 */

use App\Services\BudgetService;

// Vast voorbeeld (geen echte gegevens) om te laten zien hoe een maandoverzicht eruitziet.
$exampleBudgets = [
    ['name' => 'Boodschappen', 'budget_cents' => 20000, 'spent_cents' => 12840, 'remaining_cents' => 7160, 'percentage' => 64, 'status' => BudgetService::STATUS_OK],
    ['name' => 'Uitgaan', 'budget_cents' => 7500, 'spent_cents' => 8240, 'remaining_cents' => -740, 'percentage' => 109, 'status' => BudgetService::STATUS_OVER],
];
?>
<section class="hero">
    <div class="hero__text">
        <h1>Zie waar je geld blijft</h1>
        <p class="lead">Houd je inkomsten en uitgaven per maand bij, stel zelf een limiet in per categorie en spaar voor iets wat je graag wilt. Je oefent met gegevens die je zelf invoert.</p>
        <div class="hero__actions">
            <a class="button button--primary" href="<?= e(url('/register')) ?>">Account maken</a>
            <a class="button button--ghost" href="<?= e(url('/login')) ?>">Inloggen</a>
        </div>
    </div>

    <figure class="card example" aria-labelledby="example-title">
        <p class="example__label" id="example-title">Voorbeeld van een maandoverzicht</p>

        <table class="ledger">
            <tr><th scope="row">Inkomsten</th><td class="amount--income"><?= e(money(72500)) ?></td></tr>
            <tr><th scope="row">Uitgaven</th><td><?= e(money(31558)) ?></td></tr>
            <tr class="ledger__total"><th scope="row">Over deze maand</th><td><?= e(money(40942)) ?></td></tr>
        </table>

        <?php foreach ($exampleBudgets as $line): ?>
            <?= partial('budget-bar', ['line' => $line + ['status_label' => BudgetService::statusLabel($line['status'])]]) ?>
        <?php endforeach; ?>
    </figure>
</section>

<ol class="steps">
    <li><p><strong>Maak je categorieën</strong>Bijvoorbeeld Bijbaan, Boodschappen en Vervoer.</p></li>
    <li><p><strong>Noteer wat er in- en uitgaat</strong>Met bedrag, datum en categorie.</p></li>
    <li><p><strong>Kijk hoe je maand ervoor staat</strong>Totalen, je limieten en je spaardoelen.</p></li>
</ol>

<?php if ($tip !== null): ?>
    <?= partial('tip-card', ['tip' => $tip]) ?>
<?php endif; ?>

<p class="notice">Let op: SpendSmart heeft geen bankkoppeling en geeft geen financieel advies. Voer geen echte bankgegevens in.</p>
