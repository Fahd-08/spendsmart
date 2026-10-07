<?php
/**
 * Pagina 'Categorie toevoegen'. Het formulier staat in partials/forms/category.php.
 *
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Categorie toevoegen</h1>
</div>

<?= partial('forms/category', [
    'action' => url('/categories'),
    'submitLabel' => 'Opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
