<?php
/**
 * Inlogpagina (FE-02). Het formulier wordt verstuurd naar POST /login (AuthController::login).
 *
 * @var array $values
 * @var array $errors
 */
?>
<section class="auth-card card">
    <h1>Inloggen</h1>

    <?php // Rode melding bovenaan als er fouten zijn. ?>
    <?= partial('form-errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
        <?php // Verborgen CSRF-token: bewijst dat het formulier van onze eigen site komt. ?>
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">E-mailadres</label>
            <?php // e() maakt de waarde HTML-veilig; field_attributes() en field_error() tonen een fout bij het veld. ?>
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
