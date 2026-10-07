<?php
/**
 * Formulier voor een spaardoel (toevoegen en wijzigen).
 *
 * @var string $action
 * @var string $submitLabel
 * @var array $values
 * @var array $errors
 */
?>
<?= partial('form-errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="card form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Naam van het doel</label>
        <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" placeholder="Bijvoorbeeld: nieuwe fiets" required maxlength="100"<?= field_attributes($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
    </div>

    <div class="form__row">
        <div class="field">
            <?php // Doelbedrag is verplicht en moet groter zijn dan € 0,00. ?>
            <label for="target_amount">Doelbedrag</label>
            <div class="input-group">
                <span class="input-group__prefix" aria-hidden="true">€</span>
                <input type="text" inputmode="decimal" id="target_amount" name="target_amount" value="<?= e($values['target_amount']) ?>" placeholder="500,00" required maxlength="12"<?= field_attributes($errors, 'target_amount') ?>>
            </div>
            <?= field_error($errors, 'target_amount') ?>
        </div>

        <div class="field">
            <label for="saved_amount">Al gespaard <span class="optional">(optioneel)</span></label>
            <div class="input-group">
                <span class="input-group__prefix" aria-hidden="true">€</span>
                <input type="text" inputmode="decimal" id="saved_amount" name="saved_amount" value="<?= e($values['saved_amount']) ?>" placeholder="0,00" maxlength="12"<?= field_attributes($errors, 'saved_amount') ?>>
            </div>
            <?= field_error($errors, 'saved_amount') ?>
        </div>
    </div>

    <div class="field">
        <?php // Streefdatum is optioneel; bij een nieuw doel mag hij niet in het verleden liggen. ?>
        <label for="target_date">Streefdatum <span class="optional">(optioneel)</span></label>
        <input type="date" id="target_date" name="target_date" value="<?= e($values['target_date']) ?>" min="2000-01-01" max="2100-12-31"<?= field_attributes($errors, 'target_date') ?>>
        <?= field_error($errors, 'target_date') ?>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= e($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= e(url('/goals')) ?>">Annuleren</a>
    </div>
</form>
