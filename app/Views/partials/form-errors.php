<?php
/**
 * Samenvatting boven een formulier als er fouten zijn.
 *
 * @var array $errors
 */
?>
<?php if ($errors !== []): ?>
    <?= partial('alert', [
        'type' => 'error',
        'text' => count($errors) === 1
            ? 'Er is 1 veld niet goed ingevuld. Bekijk de melding bij het veld.'
            : 'Er zijn ' . count($errors) . ' velden niet goed ingevuld. Bekijk de meldingen bij de velden.',
    ]) ?>
<?php endif; ?>
