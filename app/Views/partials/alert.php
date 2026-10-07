<?php
/**
 * Melding met kleur én tekst (niet alleen kleur).
 *
 * @var string $type success|error|warning|info
 * @var string $text
 */

// Tekst vóór de melding ('Gelukt:', 'Fout:'), zodat de soort ook zonder kleur duidelijk is.
$prefixes = [
    'success' => 'Gelukt',
    'error' => 'Fout',
    'warning' => 'Let op',
    'info' => 'Info',
];
$prefix = $prefixes[$type] ?? 'Info';
// role='alert' laat schermlezers fouten en waarschuwingen meteen voorlezen.
$role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';
?>
<div class="alert alert--<?= e($type) ?>" role="<?= $role ?>">
    <span class="alert__icon" aria-hidden="true"></span>
    <p><strong><?= e($prefix) ?>:</strong> <?= e($text) ?></p>
</div>
