<?php
/**
 * Tips-pagina voor gebruikers (FE-11): alleen gepubliceerde leerteksten.
 *
 * @var array $tips
 */
?>
<div class="page-header">
    <div>
        <h1>Tips</h1>
        <p class="muted">Algemene leerteksten van MoneyMinds. Dit is geen persoonlijk financieel advies.</p>
    </div>
</div>

<?php if ($tips === []): ?>
    <?= partial('empty-state', ['text' => 'Er zijn nog geen tips gepubliceerd. Kijk later nog eens.']) ?>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($tips as $tip): ?>
            <article class="card">
                <h2><?= e($tip['title']) ?></h2>
                <?php // Eerst e() (veilig maken), dan nl2br() (enters worden <br>). Andersom zou onveilig zijn. ?>
                <p><?= nl2br(e($tip['body'])) ?></p>
                <p class="muted">Gepubliceerd op <?= e(format_date($tip['published_at'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
