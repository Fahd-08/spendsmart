<?php
/**
 * @var array|null $tip
 */
?>
<section class="hero">
    <div class="hero__text">
        <p class="eyebrow">MoneyMinds leeromgeving</p>
        <h1>Zie waar je geld blijft</h1>
        <p class="lead">Met SpendSmart houd je je inkomsten en uitgaven per maand bij, stel je zelf limieten in per categorie en werk je aan je spaardoelen. Je oefent met gegevens die je zelf invoert.</p>
        <div class="hero__actions">
            <a class="button button--primary" href="<?= e(url('/register')) ?>">Account maken</a>
            <a class="button button--ghost" href="<?= e(url('/login')) ?>">Inloggen</a>
        </div>
    </div>

    <ul class="feature-list">
        <li class="card"><h2>Maandoverzicht</h2><p>Inkomsten, uitgaven en saldo per maand, netjes opgeteld.</p></li>
        <li class="card"><h2>Eigen limieten</h2><p>Een melding als je boven de limiet komt die je zelf hebt ingesteld.</p></li>
        <li class="card"><h2>Spaardoelen</h2><p>Zie hoe ver je bent met sparen voor iets wat je graag wilt.</p></li>
    </ul>
</section>

<?php if ($tip !== null): ?>
    <?= partial('tip-card', ['tip' => $tip]) ?>
<?php endif; ?>

<p class="notice">Let op: SpendSmart heeft geen bankkoppeling en geeft geen financieel advies. Voer geen echte bankgegevens in.</p>
