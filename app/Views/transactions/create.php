<?php
/**
 * Pagina 'Transactie toevoegen'. Het formulier zelf staat in partials/forms/transaction.php.
 *
 * @var array $categories
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Transactie toevoegen</h1>
</div>

<?= partial('forms/transaction', [
    'action' => url('/transactions'),
    'submitLabel' => 'Opslaan',
    'categories' => $categories,
    'values' => $values,
    'errors' => $errors,
]) ?>
