<?php
/**
 * Spaardoelenpagina (FE-08): alle spaardoelen als kaarten.
 *
 * @var array $goals
 */
?>
<div class="page-header">
    <div>
        <h1>Spaardoelen</h1>
        <p class="muted">Houd bij hoeveel je al hebt gespaard voor iets wat je graag wilt.</p>
    </div>
    <a class="button button--primary" href="<?= e(url('/goals/create')) ?>">Spaardoel toevoegen</a>
</div>

<?php if ($goals === []): ?>
    <?= partial('empty-state', [
        'text' => 'Je hebt nog geen spaardoelen. Voeg er een toe om je voortgang te volgen.',
        'actionUrl' => url('/goals/create'),
        'actionLabel' => 'Spaardoel toevoegen',
    ]) ?>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($goals as $goal): ?>
            <?php // withActions: hier wel de knoppen (bedrag toevoegen, wijzigen, verwijderen), op het dashboard niet. ?>
            <?= partial('goal-card', ['goal' => $goal, 'withActions' => true]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
