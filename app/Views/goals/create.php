<?php
/**
 * Pagina 'Spaardoel toevoegen'. Het formulier staat in partials/forms/goal.php.
 *
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Spaardoel toevoegen</h1>
</div>

<?= partial('forms/goal', [
    'action' => url('/goals'),
    'submitLabel' => 'Opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
