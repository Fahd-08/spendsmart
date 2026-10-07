<?php
/**
 * Pagina 'Transactie wijzigen'. Zelfde formulier als toevoegen, maar met de bestaande gegevens ingevuld.
 *
 * @var array $transaction
 * @var array $categories
 * @var array $values
 * @var array $errors
 */
?>
<div class="page-header">
    <h1>Transactie wijzigen</h1>
</div>

<?= partial('forms/transaction', [
    'action' => url('/transactions/' . $transaction['id'] . '/update'),
    'submitLabel' => 'Wijzigingen opslaan',
    'categories' => $categories,
    'values' => $values,
    'errors' => $errors,
]) ?>
