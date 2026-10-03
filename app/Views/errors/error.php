<?php
/**
 * @var int $status
 * @var string $title
 * @var string $message
 */
?>
<section class="card error-page" role="alert">
    <p class="error-page__code">Foutcode <?= e($status) ?></p>
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <div class="form__actions">
        <a class="button button--primary" href="<?= e(url('/')) ?>">Naar de startpagina</a>
        <?php if ($status === 401 || $status === 419): ?>
            <a class="button button--ghost" href="<?= e(url('/login')) ?>">Inloggen</a>
        <?php endif; ?>
    </div>
</section>
