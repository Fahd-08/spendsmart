<?php
/**
 * Formulier voor een eigen categorie (toevoegen en wijzigen).
 *
 * @var string $action
 * @var string $submitLabel
 * @var array $values
 * @var array $errors
 */

use App\Support\CategoryType;
?>
<?= partial('form-errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="card form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Naam</label>
        <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required maxlength="60"<?= field_attributes($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
    </div>

    <?php // Soort kiezen. data-toggle-budget: app.js verbergt de maandlimiet bij een inkomstencategorie. ?>
    <fieldset class="field">
        <legend>Soort</legend>
        <?php foreach (CategoryType::ALL as $type): ?>
            <label class="radio">
                <input type="radio" name="type" value="<?= e($type) ?>"<?= $values['type'] === $type ? ' checked' : '' ?> data-toggle-budget>
                <?= e(CategoryType::label($type)) ?>
            </label>
        <?php endforeach; ?>
        <?= field_error($errors, 'type') ?>
    </fieldset>

    <?php // Maandlimiet (optioneel). Leeg = geen limiet, 0 = limiet van € 0,00. ?>
    <div class="field" data-budget-field>
        <label for="monthly_budget">Maandlimiet <span class="optional">(optioneel, alleen voor uitgaven)</span></label>
        <p class="field-hint">Je eigen grens voor deze categorie per maand. Laat leeg als je geen limiet wilt.</p>
        <div class="input-group">
            <span class="input-group__prefix" aria-hidden="true">€</span>
            <input type="text" inputmode="decimal" id="monthly_budget" name="monthly_budget" value="<?= e($values['monthly_budget']) ?>" placeholder="150,00" maxlength="12"<?= field_attributes($errors, 'monthly_budget') ?>>
        </div>
        <?= field_error($errors, 'monthly_budget') ?>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= e($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= e(url('/categories')) ?>">Annuleren</a>
    </div>
</form>
