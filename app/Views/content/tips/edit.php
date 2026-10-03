<?php
/**
 * @var array $tip
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Leertekst wijzigen</h1>
</div>

<?= partial('forms/tip', [
    'action' => url('/content/tips/' . $tip['id'] . '/update'),
    'submitLabel' => 'Wijzigingen opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
