<?php
/**
 * @var array $goal
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Spaardoel wijzigen</h1>
</div>

<?= partial('forms/goal', [
    'action' => url('/goals/' . $goal['id'] . '/update'),
    'submitLabel' => 'Wijzigingen opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
