<?php
/**
 * Duidelijke melding bij een lege lijst.
 *
 * @var string $text
 * @var string|null $actionUrl
 * @var string|null $actionLabel
 */
?>
<div class="empty-state" role="status">
    <p><?= e($text) ?></p>
    <?php // Optionele knop, bijv. 'Transactie toevoegen'. ?>
    <?php if (!empty($actionUrl) && !empty($actionLabel)): ?>
        <a class="button button--primary" href="<?= e($actionUrl) ?>"><?= e($actionLabel) ?></a>
    <?php endif; ?>
</div>
