<?php
/**
 * @var array $values
 * @var array $errors
 */
?>
<section class="auth-card card">
    <h1>Inloggen</h1>

    <?= partial('form-errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required maxlength="190"<?= field_attributes($errors, 'email') ?>>
            <?= field_error($errors, 'email') ?>
        </div>

        <div class="field">
            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required<?= field_attributes($errors, 'password') ?>>
            <?= field_error($errors, 'password') ?>
        </div>

        <button type="submit" class="button button--primary button--block">Inloggen</button>
    </form>

    <p class="auth-card__footer">Nog geen account? <a href="<?= e(url('/register')) ?>">Maak een account</a></p>
</section>
