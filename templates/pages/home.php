<?php
/**
 * @var array[] $latest
 * @var array{found: int, lost: int, returned: int} $stats
 */
$popular = ['ISIC', 'Klíče', 'Sluchátka', 'Bunda'];
$statTiles = [
    [$stats['found'], plural_form($stats['found'], 'nález čeká', 'nálezy čekají', 'nálezů čeká') . ' na majitele', 'found'],
    [$stats['lost'], plural_form($stats['lost'], 'věc se hledá', 'věci se hledají', 'věcí se hledá'), 'lost'],
];
$steps = [
    ['Nahlaste věc', 'Ztrátu i nález zapíšete za minutu. Stačí popis, místo na mapě a ideálně fotka.'],
    ['Hledejte na kampusu', 'Filtrujte podle budovy a kategorie, nebo projděte mapu celého Suchdolu.'],
    ['Vraťte ji majiteli', 'Domluvíte se přes formulář, aniž byste zveřejnili svůj e-mail.'],
];
?>
<section class="hero">
    <div class="hero__text">
        <h1>Ztratili jste něco na&nbsp;kampusu?</h1>
        <p class="lead">ISIC v posluchárně, klíče cestou z menzy, sluchátka ve studovně. Ztráty a nálezy z celé ČZU na jednom místě.</p>

        <form class="search" action="<?= e(url('/inzeraty')) ?>" method="get" role="search">
            <label class="visually-hidden" for="hero-q">Co hledáte?</label>
            <input id="hero-q" type="search" name="q" placeholder="Co hledáte?">
            <button class="button" type="submit">Hledat</button>
        </form>

        <p class="popular">
            <span>Často hledané:</span>
            <?php foreach ($popular as $term): ?>
                <a href="<?= e(url('/inzeraty', ['q' => mb_strtolower($term)])) ?>"><?= e($term) ?></a>
            <?php endforeach ?>
        </p>
    </div>

    <ul class="stats">
        <?php foreach ($statTiles as [$value, $label, $type]): ?>
            <li>
                <a class="stat stat--<?= e($type) ?>" href="<?= e(url('/inzeraty', ['type' => $type])) ?>">
                    <span class="stat__value"><?= $value ?></span>
                    <span class="stat__label"><?= e($label) ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</section>

<section class="section" aria-labelledby="latest-heading">
    <div class="section__head">
        <h2 id="latest-heading">Nejnovější inzeráty</h2>
        <a href="<?= e(url('/inzeraty')) ?>">Všechny inzeráty</a>
    </div>
    <div class="grid">
        <?php foreach ($latest as $item): ?>
            <?= render('partials/item-card', ['item' => $item]) ?>
        <?php endforeach ?>
    </div>
</section>

<section class="section" aria-labelledby="steps-heading">
    <h2 id="steps-heading">Jak to funguje</h2>
    <ol class="steps">
        <?php foreach ($steps as [$heading, $text]): ?>
            <li>
                <h3><?= e($heading) ?></h3>
                <p><?= e($text) ?></p>
            </li>
        <?php endforeach ?>
    </ol>
</section>
