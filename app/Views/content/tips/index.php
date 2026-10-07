<?php
/**
 * Beheerpagina leerteksten (FE-11): alle teksten, ook concepten.
 *
 * @var array $tips
 */
?>
<div class="page-header">
    <div>
        <h1>Leerteksten beheren</h1>
        <p class="muted">Algemene, niet-persoonlijke teksten die gebruikers bij Tips zien.</p>
    </div>
    <a class="button button--primary" href="<?= e(url('/content/tips/create')) ?>">Leertekst toevoegen</a>
</div>

<?php if ($tips === []): ?>
    <?= partial('empty-state', [
        'text' => 'Er zijn nog geen leerteksten.',
        'actionUrl' => url('/content/tips/create'),
        'actionLabel' => 'Leertekst toevoegen',
    ]) ?>
<?php else: ?>
    <section class="card">
        <div class="table-wrapper">
            <table class="table">
                <caption class="visually-hidden">Alle leerteksten</caption>
                <thead>
                <tr>
                    <th scope="col">Titel</th>
                    <th scope="col">Status</th>
                    <th scope="col">Gepubliceerd op</th>
                    <th scope="col">Auteur</th>
                    <th scope="col"><span class="visually-hidden">Acties</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tips as $tip): ?>
                    <tr>
                        <td data-label="Titel"><?= e($tip['title']) ?></td>
                        <?php // Gepubliceerd (zichtbaar voor gebruikers) of Concept (alleen voor de contentbeheerder). ?>
                        <td data-label="Status">
                            <span class="status <?= $tip['is_published'] ? 'status--ok' : 'status--none' ?>">
                                <?= $tip['is_published'] ? 'Gepubliceerd' : 'Concept' ?>
                            </span>
                        </td>
                        <td data-label="Gepubliceerd op"><?= e($tip['published_at'] ? format_date($tip['published_at']) : '—') ?></td>
                        <td data-label="Auteur"><?= e($tip['author_name'] ?? 'Onbekend') ?></td>
                        <td class="table__actions">
                            <a class="button button--ghost button--small" href="<?= e(url('/content/tips/' . $tip['id'] . '/edit')) ?>">Wijzigen</a>
                            <?= partial('delete-button', [
                                'action' => url('/content/tips/' . $tip['id'] . '/delete'),
                                'confirm' => 'Weet je zeker dat je leertekst "' . $tip['title'] . '" wilt verwijderen?',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
