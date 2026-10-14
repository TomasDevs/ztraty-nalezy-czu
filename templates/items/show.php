<?php
use App\Repositories\ItemQuery;

/** @var array $item */
$marker = [['lat' => $item['lat'], 'lng' => $item['lng'], 'title' => $item['title'], 'type' => $item['type']]];
?>
<nav class="breadcrumbs" aria-label="Drobečková navigace">
    <ol>
        <li><a href="<?= e(url('/')) ?>">Úvod</a></li>
        <li><a href="<?= e(url('/inzeraty', ['type' => $item['type']])) ?>"><?= e(ItemQuery::TYPES[$item['type']]) ?></a></li>
        <li aria-current="page"><?= e($item['title']) ?></li>
    </ol>
</nav>

<article class="detail">
    <header class="detail__header">
        <div class="card__badges">
            <span class="badge badge--<?= e($item['type']) ?>"><?= e(ItemQuery::TYPES[$item['type']]) ?></span>
            <?php if ($item['status'] === 'returned'): ?>
                <span class="badge badge--returned">Vráceno</span>
            <?php endif ?>
        </div>
        <h1><?= e($item['title']) ?></h1>
    </header>

    <div class="detail__body">
        <div>
            <p><?= e($item['description']) ?></p>
            <dl class="detail__meta">
                <dt>Kategorie</dt>
                <dd><a href="<?= e(url('/inzeraty', ['category' => $item['category']['slug']])) ?>"><?= e($item['category']['name']) ?></a></dd>
                <dt>Budova</dt>
                <dd><a href="<?= e(url('/inzeraty', ['building' => $item['building']['slug']])) ?>"><?= e($item['building']['name']) ?></a></dd>
                <dt>Místo</dt>
                <dd><?= e($item['location']) ?></dd>
                <dt>Datum</dt>
                <dd><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></dd>
            </dl>
        </div>
        <div class="detail__map map" data-map data-markers="<?= e(json_encode($marker, JSON_UNESCAPED_UNICODE)) ?>" aria-label="Místo na mapě"></div>
    </div>
</article>
