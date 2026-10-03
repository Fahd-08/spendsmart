<?php
/**
 * @var array $values
 * @var array $errors
 */
?>
<section class="auth-card card">
    <h1>Account maken</h1>
    <p class="muted">Je gegevens zijn alleen voor jou zichtbaar.</p>

    <?= partial('form-errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/register')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Naam</label>
            <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" autocomplete="name" required maxlength="100"<?= field_attributes($errors, 'name') ?>>
            <?= field_error($errors, 'name') ?>
        </div>

        <div class="field">
            <label for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required maxlength="190"<?= field_attributes($errors, 'email') ?>>
            <?= field_error($errors, 'email') ?>
        </div>

        <div class="field">
            <label for="password">Wachtwoord</label>
            <p class="field-hint" id="password-hint">Minimaal 8 tekens, met minstens één letter en één cijfer.</p>
            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"<?= field_attributes($errors, 'password') ?>>
            <?= field_error($errors, 'password') ?>
        </div>

        <div class="field">
            <label for="password_confirmation">Herhaal wachtwoord</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <div class="field field--checkbox">
            <input type="checkbox" id="practice_data" name="practice_data" value="1" required<?= field_attributes($errors, 'practice_data') ?>>
            <label for="practice_data">Ik begrijp dat SpendSmart bedoeld is om te oefenen, geen bankkoppeling heeft en geen financieel advies geeft.</label>
            <?= field_error($errors, 'practice_data') ?>
        </div>

        <button type="submit" class="button button--primary button--block">Account maken</button>
    </form>

    <p class="auth-card__footer">Heb je al een account? <a href="<?= e(url('/login')) ?>">Log in</a></p>
</section>
