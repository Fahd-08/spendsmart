<?php
/**
 * Kaart met één tip (op de startpagina en het dashboard).
 *
 * @var array $tip
 */
?>
<aside class="card tip-card">
    <p class="tip-card__label">Tip</p>
    <h2><?= e($tip['title']) ?></h2>
    <p><?= nl2br(e($tip['body'])) ?></p>
</aside>
