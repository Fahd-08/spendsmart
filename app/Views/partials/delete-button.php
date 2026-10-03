<?php
/**
 * Verwijderknop als POST-formulier met CSRF-token en bevestigingsvraag.
 *
 * @var string $action URL
 * @var string $confirm vraag voor de gebruiker
 * @var string|null $label
 */
?>
<form method="post" action="<?= e($action) ?>" class="inline-form" data-confirm="<?= e($confirm) ?>">
    <?= csrf_field() ?>
    <button type="submit" class="button button--danger button--small"><?= e($label ?? 'Verwijderen') ?></button>
</form>
