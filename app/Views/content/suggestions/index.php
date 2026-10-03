<?php
/**
 * @var array $suggestions
 */

use App\Support\CategoryType;
?>
<div class="page-header">
    <div>
        <h1>Categorievoorstellen</h1>
        <p class="muted">Actieve voorstellen krijgen nieuwe gebruikers automatisch. Bestaande gebruikers kunnen ze overnemen.</p>
    </div>
    <a class="button button--primary" href="<?= e(url('/content/suggestions/create')) ?>">Voorstel toevoegen</a>
</div>

<?php if ($suggestions === []): ?>
    <?= partial('empty-state', [
        'text' => 'Er zijn nog geen categorievoorstellen.',
        'actionUrl' => url('/content/suggestions/create'),
        'actionLabel' => 'Voorstel toevoegen',
    ]) ?>
<?php else: ?>
    <section class="card">
        <div class="table-wrapper">
            <table class="table">
                <caption class="visually-hidden">Alle categorievoorstellen</caption>
                <thead>
                <tr>
                    <th scope="col">Naam</th>
                    <th scope="col">Soort</th>
                    <th scope="col">Omschrijving</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="table__number">Overgenomen</th>
                    <th scope="col"><span class="visually-hidden">Acties</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($suggestions as $suggestion): ?>
                    <tr>
                        <td data-label="Naam"><span class="chip chip--<?= e($suggestion['type']) ?>"><?= e($suggestion['name']) ?></span></td>
                        <td data-label="Soort"><?= e(CategoryType::label($suggestion['type'])) ?></td>
                        <td data-label="Omschrijving"><?= e($suggestion['description'] ?: '—') ?></td>
                        <td data-label="Status">
                            <span class="status <?= $suggestion['is_active'] ? 'status--ok' : 'status--none' ?>">
                                <?= $suggestion['is_active'] ? 'Actief' : 'Inactief' ?>
                            </span>
                        </td>
                        <td data-label="Overgenomen" class="table__number"><?= (int) $suggestion['adoption_count'] ?>&times;</td>
                        <td class="table__actions">
                            <a class="button button--ghost button--small" href="<?= e(url('/content/suggestions/' . $suggestion['id'] . '/edit')) ?>">Wijzigen</a>
                            <?= partial('delete-button', [
                                'action' => url('/content/suggestions/' . $suggestion['id'] . '/delete'),
                                'confirm' => 'Weet je zeker dat je voorstel "' . $suggestion['name'] . '" wilt verwijderen? Categorieën van gebruikers blijven bestaan.',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
