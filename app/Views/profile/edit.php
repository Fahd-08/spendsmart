<?php
/**
 * @var array $user
 * @var array $values
 * @var array $errors
 * @var bool $canDeleteAccount
 */

use App\Support\Role;
?>
<div class="page-header">
    <div>
        <h1>Profiel</h1>
        <p class="muted">Rol: <?= e(Role::label($user['role'])) ?> &middot; lid sinds <?= e(format_date($user['created_at'])) ?></p>
    </div>
</div>

<?= partial('form-errors', ['errors' => $errors]) ?>

<div class="profile-grid">
    <section class="card" aria-labelledby="profile-title">
        <h2 id="profile-title">Mijn gegevens</h2>
        <form method="post" action="<?= e(url('/profile')) ?>" novalidate>
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

            <button type="submit" class="button button--primary">Gegevens opslaan</button>
        </form>
    </section>

    <section class="card" aria-labelledby="password-title">
        <h2 id="password-title">Wachtwoord wijzigen</h2>
        <form method="post" action="<?= e(url('/profile/password')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="current_password">Huidig wachtwoord</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required<?= field_attributes($errors, 'current_password') ?>>
                <?= field_error($errors, 'current_password') ?>
            </div>

            <div class="field">
                <label for="password">Nieuw wachtwoord</label>
                <p class="field-hint">Minimaal 8 tekens, met minstens één letter en één cijfer.</p>
                <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"<?= field_attributes($errors, 'password') ?>>
                <?= field_error($errors, 'password') ?>
            </div>

            <div class="field">
                <label for="password_confirmation">Herhaal nieuw wachtwoord</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

            <button type="submit" class="button button--primary">Wachtwoord wijzigen</button>
        </form>
    </section>

    <?php if ($canDeleteAccount): ?>
        <section class="card card--danger" aria-labelledby="delete-title">
            <h2 id="delete-title">Account verwijderen</h2>
            <p>Hiermee verwijder je je account en al je categorieën, transacties en spaardoelen. Dit kan niet ongedaan worden gemaakt.</p>
            <form method="post" action="<?= e(url('/profile/delete')) ?>" data-confirm="Weet je zeker dat je je account en al je gegevens definitief wilt verwijderen?" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="delete_password">Bevestig met je wachtwoord</label>
                    <input type="password" id="delete_password" name="delete_password" autocomplete="current-password" required<?= field_attributes($errors, 'delete_password') ?>>
                    <?= field_error($errors, 'delete_password') ?>
                </div>
                <button type="submit" class="button button--danger">Account definitief verwijderen</button>
            </form>
        </section>
    <?php endif; ?>
</div>
