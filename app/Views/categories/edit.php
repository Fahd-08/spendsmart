<?php
/**
 * Pagina 'Categorie wijzigen'. Zelfde formulier als toevoegen.
 *
 * @var array $category
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Categorie wijzigen</h1>
</div>

<?= partial('forms/category', [
    'action' => url('/categories/' . $category['id'] . '/update'),
    'submitLabel' => 'Wijzigingen opslaan',
    'values' => $values,
    'errors' => $errors,
]) ?>
