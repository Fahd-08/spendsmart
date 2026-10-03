<?php
/**
 * @var array $suggestion
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Categorievoorstel wijzigen</h1>
</div>

<?= partial('forms/suggestion', [
    'action' => url('/content/suggestions/' . $suggestion['id'] . '/update'),
    'submitLabel' => 'Wijzigingen opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
