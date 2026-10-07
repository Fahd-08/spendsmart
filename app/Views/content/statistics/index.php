<?php
/**
 * Statistieken voor de contentbeheerder (FE-12). Alleen aantallen, geen persoonsgegevens of bedragen.
 *
 * @var array $statistics zie StatisticsService::overview()
 */

use App\Support\CategoryType;

// Totaal over alle maanden; bij 0 tonen we een lege-lijstmelding in plaats van een lege tabel.
$activityTotal = array_sum(array_column($statistics['activity'], 'transaction_count'));
?>
<div class="page-header">
    <div>
        <h1>Statistieken</h1>
        <p class="muted">Anonieme aantallen. Je ziet geen namen, e-mailadressen, bedragen of omschrijvingen van gebruikers.</p>
    </div>
</div>

<?php // Drie kerncijfers: gebruikers, transacties en spaardoelen. ?>
<section class="stats" aria-label="Totalen">
    <div class="stat card">
        <p class="stat__label">Gebruikers</p>
        <p class="stat__value"><?= e($statistics['user_count']) ?></p>
        <p class="muted"><?= e($statistics['new_user_count']) ?> nieuw in de laatste 30 dagen</p>
    </div>
    <div class="stat card">
        <p class="stat__label">Geregistreerde transacties</p>
        <p class="stat__value"><?= e($statistics['transaction_count']) ?></p>
    </div>
    <div class="stat card">
        <p class="stat__label">Spaardoelen</p>
        <p class="stat__value"><?= e($statistics['goal_count']) ?></p>
        <p class="muted"><?= e($statistics['goals_reached_count']) ?> bereikt</p>
    </div>
</section>

<div class="dashboard-grid">
    <section class="card" aria-labelledby="activity-title">
        <h2 id="activity-title">Activiteit per maand</h2>

        <?php if ($activityTotal === 0): ?>
            <?= partial('empty-state', ['text' => 'Er zijn de afgelopen maanden nog geen transacties geregistreerd.']) ?>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <caption class="visually-hidden">Aantal transacties en actieve gebruikers per maand</caption>
                    <thead>
                    <tr>
                        <th scope="col">Maand</th>
                        <th scope="col">Transacties</th>
                        <th scope="col" class="table__number">Actieve gebruikers</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php // Per maand: aantal transacties (met balk) en aantal actieve gebruikers. ?>
                    <?php foreach ($statistics['activity'] as $row): ?>
                        <?php $width = bar_width($row['percentage']); ?>
                        <tr>
                            <td data-label="Maand"><?= e($row['label']) ?></td>
                            <td data-label="Transacties">
                                <div class="bar-with-value">
                                    <div class="bar" aria-hidden="true"><div class="bar__fill bar__fill--progress w-<?= $width ?>"></div></div>
                                    <span><?= e($row['transaction_count']) ?></span>
                                </div>
                            </td>
                            <td data-label="Actieve gebruikers" class="table__number"><?= e($row['active_users']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php // Hoe vaak elk categorievoorstel is overgenomen. ?>
    <section class="card" aria-labelledby="adoption-title">
        <h2 id="adoption-title">Gebruik van categorievoorstellen</h2>

        <?php if ($statistics['suggestions'] === []): ?>
            <?= partial('empty-state', [
                'text' => 'Er zijn nog geen categorievoorstellen.',
                'actionUrl' => url('/content/suggestions/create'),
                'actionLabel' => 'Voorstel toevoegen',
            ]) ?>
        <?php else: ?>
            <ul class="compact-list">
                <?php foreach ($statistics['suggestions'] as $suggestion): ?>
                    <li class="compact-list__item">
                        <span>
                            <span class="chip chip--<?= e($suggestion['type']) ?>"><?= e($suggestion['name']) ?></span>
                            <span class="muted"><?= e(CategoryType::label($suggestion['type'])) ?></span>
                        </span>
                        <span>door <?= (int) $suggestion['adoption_count'] ?> <?= (int) $suggestion['adoption_count'] === 1 ? 'gebruiker' : 'gebruikers' ?> gebruikt</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
