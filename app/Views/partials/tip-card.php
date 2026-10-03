<?php
/**
 * @var array $tip
 */
?>
<aside class="card tip-card">
    <p class="eyebrow">Tip</p>
    <h2><?= e($tip['title']) ?></h2>
    <p><?= nl2br(e($tip['body'])) ?></p>
</aside>
