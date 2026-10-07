<?php
/**
 * Pagina 'Categorievoorstel toevoegen'. Het formulier staat in partials/forms/suggestion.php.
 *
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Categorievoorstel toevoegen</h1>
</div>

<?= partial('forms/suggestion', [
    'action' => url('/content/suggestions'),
    'submitLabel' => 'Opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
