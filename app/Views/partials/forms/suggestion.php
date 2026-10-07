<?php
/**
 * Formulier voor een categorievoorstel (toevoegen en wijzigen).
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

    <fieldset class="field">
        <legend>Soort</legend>
        <?php foreach (CategoryType::ALL as $type): ?>
            <label class="radio">
                <input type="radio" name="type" value="<?= e($type) ?>"<?= $values['type'] === $type ? ' checked' : '' ?>>
                <?= e(CategoryType::label($type)) ?>
            </label>
        <?php endforeach; ?>
        <?= field_error($errors, 'type') ?>
    </fieldset>

    <div class="field">
        <label for="description">Omschrijving <span class="optional">(optioneel)</span></label>
        <input type="text" id="description" name="description" value="<?= e($values['description']) ?>" maxlength="255"<?= field_attributes($errors, 'description') ?>>
        <?= field_error($errors, 'description') ?>
    </div>

    <?php // Vinkje actief: niet aangevinkt = het voorstel is verborgen voor gebruikers. ?>
    <div class="field field--checkbox">
        <input type="checkbox" id="is_active" name="is_active" value="1"<?= $values['is_active'] === '1' ? ' checked' : '' ?>>
        <label for="is_active">Actief (zichtbaar als voorstel voor gebruikers)</label>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= e($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= e(url('/content/suggestions')) ?>">Annuleren</a>
    </div>
</form>
