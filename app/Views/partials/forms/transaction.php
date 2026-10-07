<?php
/**
 * Formulier voor een transactie (toevoegen en wijzigen).
 *
 * @var string $action
 * @var string $submitLabel
 * @var array $categories
 * @var array $values
 * @var array $errors
 */

use App\Support\CategoryType;
?>
<?= partial('form-errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="card form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="category_id">Categorie</label>
        <p class="field-hint">De categorie bepaalt of het een inkomst of een uitgave is.</p>
        <?php // Keuzelijst met eigen categorieën, gegroepeerd per soort (optgroup). ?>
        <select id="category_id" name="category_id" required<?= field_attributes($errors, 'category_id') ?>>
            <option value="">Kies een categorie</option>
            <?php foreach (CategoryType::ALL as $type): ?>
                <optgroup label="<?= e(CategoryType::pluralLabel($type)) ?>">
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category['type'] === $type): ?>
                            <option value="<?= e($category['id']) ?>"<?= (string) $values['category_id'] === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'category_id') ?>
    </div>

    <div class="form__row">
        <div class="field">
            <label for="amount">Bedrag</label>
            <div class="input-group">
                <span class="input-group__prefix" aria-hidden="true">€</span>
                <?php // Tekstveld (geen number-veld) zodat '12,50' met komma werkt; inputmode toont op mobiel een cijfertoetsenbord. ?>
                <input type="text" inputmode="decimal" id="amount" name="amount" value="<?= e($values['amount']) ?>" placeholder="12,50" required maxlength="12"<?= field_attributes($errors, 'amount') ?>>
            </div>
            <?= field_error($errors, 'amount') ?>
        </div>

        <div class="field">
            <label for="transaction_date">Datum</label>
            <?php // Datumkiezer; de server controleert de datum daarna nog een keer (Validator). ?>
            <input type="date" id="transaction_date" name="transaction_date" value="<?= e($values['transaction_date']) ?>" required min="2000-01-01" max="2100-12-31"<?= field_attributes($errors, 'transaction_date') ?>>
            <?= field_error($errors, 'transaction_date') ?>
        </div>
    </div>

    <div class="field">
        <label for="description">Omschrijving <span class="optional">(optioneel)</span></label>
        <input type="text" id="description" name="description" value="<?= e($values['description']) ?>" maxlength="255"<?= field_attributes($errors, 'description') ?>>
        <?= field_error($errors, 'description') ?>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary"><?= e($submitLabel) ?></button>
        <a class="button button--ghost" href="<?= e(url('/transactions')) ?>">Annuleren</a>
    </div>
</form>
