<?php
/**
 * Pagina 'Leertekst toevoegen'. Het formulier staat in partials/forms/tip.php.
 *
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Leertekst toevoegen</h1>
</div>

<?= partial('forms/tip', [
    'action' => url('/content/tips'),
    'submitLabel' => 'Opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
