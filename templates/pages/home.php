<?php
/** @var array[] $latest */
?>
<section class="hero">
    <h1>Ztratili jste něco na kampusu ČZU?</h1>
    <p class="hero__lead">
        ISIC, klíče, sluchátka nebo bunda. Všechny ztráty a nálezy z celého kampusu na jednom místě, s mapou a filtrováním podle budovy.
    </p>
    <form class="search" action="<?= e(url('/inzeraty')) ?>" method="get" role="search">
        <label class="visually-hidden" for="hero-q">Co hledáte?</label>
        <input id="hero-q" type="search" name="q" placeholder="Např. ISIC, klíče, sluchátka…">
        <button class="button" type="submit">Hledat</button>
    </form>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= e(url('/inzeraty', ['type' => 'found'])) ?>">Prohlédnout nálezy</a>
        <a class="button button--secondary" href="<?= e(url('/inzeraty', ['type' => 'lost'])) ?>">Prohlédnout ztráty</a>
    </div>
</section>

<section aria-labelledby="latest-heading">
    <h2 id="latest-heading">Nejnovější inzeráty</h2>
    <div class="grid">
        <?php foreach ($latest as $item): ?>
            <?= render('partials/item-card', ['item' => $item]) ?>
        <?php endforeach ?>
    </div>
    <p><a href="<?= e(url('/inzeraty')) ?>">Zobrazit všechny inzeráty &rarr;</a></p>
</section>
