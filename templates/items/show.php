<?php
use App\Repositories\ItemQuery;

/** @var array $item */
$marker = [['lat' => $item['lat'], 'lng' => $item['lng'], 'title' => $item['title'], 'type' => $item['type']]];
$dateLabel = $item['type'] === 'found' ? 'Nalezeno' : 'Ztraceno';
?>
<nav class="breadcrumbs" aria-label="Drobečková navigace">
    <ol>
        <li><a href="<?= e(url('/')) ?>">Úvod</a></li>
        <li><a href="<?= e(url('/inzeraty', ['type' => $item['type']])) ?>"><?= e(ItemQuery::TYPE_LISTS[$item['type']]) ?></a></li>
        <li aria-current="page"><?= e($item['title']) ?></li>
    </ol>
</nav>

<article class="detail">
    <header class="detail__header">
        <div class="tags"><?= render('partials/item-tags', ['item' => $item]) ?></div>
        <h1><?= e($item['title']) ?></h1>
        <p class="detail__subtitle">
            <?= e($dateLabel) ?> <time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time>,
            <?= e($item['location']) ?>
        </p>
    </header>

    <div class="detail__body">
        <div class="detail__main">
            <h2 class="visually-hidden">Popis</h2>
            <p class="detail__description"><?= e($item['description']) ?></p>
            <div class="detail__map map" data-map data-markers="<?= e(json_encode($marker, JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Místo na mapě"></div>
        </div>

        <aside class="facts" aria-labelledby="facts-heading">
            <h2 id="facts-heading" class="facts__title">Údaje o inzerátu</h2>
            <dl>
                <dt>Číslo</dt>
                <dd class="code"><?= e(item_code($item)) ?></dd>
                <dt>Kategorie</dt>
                <dd><a href="<?= e(url('/inzeraty', ['category' => $item['category']['slug']])) ?>"><?= e($item['category']['name']) ?></a></dd>
                <dt>Budova</dt>
                <dd><a href="<?= e(url('/inzeraty', ['building' => $item['building']['slug']])) ?>"><?= e($item['building']['name']) ?></a></dd>
                <dt>Místo</dt>
                <dd><?= e($item['location']) ?></dd>
                <dt><?= e($dateLabel) ?></dt>
                <dd><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></dd>
            </dl>
        </aside>
    </div>
</article>
