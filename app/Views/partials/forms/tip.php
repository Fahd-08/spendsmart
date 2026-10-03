<?php
/**
 * Formulier voor een leertekst (toevoegen en wijzigen).
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
        <label for="title">Titel</label>
        <input type="text" id="title" name="title" value="<?= e($values['title']) ?>" required maxlength="150"<?= field_attributes($errors, 'title') ?>>
        <?= field_error($errors, 'title') ?>
    </div>

    <div class="field">
        <label for="body">Tekst</label>
        <p class="field-hint">Schrijf algemeen en niet-persoonlijk. Geef geen advies over wat iemand met zijn geld moet doen. Tussen 20 en 5000 tekens.</p>
        <textarea id="body" name="body" rows="8" required maxlength="5000"<?= field_attributes($errors, 'body') ?>><?= e($values['body']) ?></textarea>
        <?= field_error($errors, 'body') ?>
    </div>

    <div class="field field--checkbox">
        <input type="checkbox" id="is_published" name="is_published" value="1"<?= $values['is_published'] === '1' ? ' checked' : '' ?>>
        <label for="is_published">Publiceren (zichtbaar voor gebruikers)</label>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= e($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= e(url('/content/tips')) ?>">Annuleren</a>
    </div>
</form>
