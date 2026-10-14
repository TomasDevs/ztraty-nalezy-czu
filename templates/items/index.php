<?php
use App\Repositories\ItemQuery;

/**
 * @var ItemQuery $query
 * @var array{items: array[], total: int, markers: array[]} $result
 * @var array[] $categories
 * @var array[] $buildings
 */
$hasMore = $query->offset() + count($result['items']) < $result['total'];
$typeOptions = array_map(fn (string $slug) => ['slug' => $slug, 'name' => ItemQuery::TYPES[$slug]], array_keys(ItemQuery::TYPES));
$select = fn (string $name, string $label, array $options, ?string $selected) => render('partials/select', compact('name', 'label', 'options', 'selected'));
?>
<h1>Ztracené a nalezené věci</h1>

<div class="listing" data-items-filter>
    <form class="filters" action="<?= e(url('/inzeraty')) ?>" method="get" data-api="<?= e(url('/api/items')) ?>">
        <div class="field">
            <label for="filter-q">Hledat</label>
            <input id="filter-q" type="search" name="q" value="<?= e($query->q) ?>" placeholder="Např. ISIC">
        </div>
        <?= $select('type', 'Typ', $typeOptions, $query->type) ?>
        <?= $select('category', 'Kategorie', $categories, $query->category) ?>
        <?= $select('building', 'Budova', $buildings, $query->building) ?>
        <button class="button" type="submit" data-filter-submit>Filtrovat</button>
    </form>

    <div class="listing__map map" data-map data-markers="<?= e(json_encode($result['markers'], JSON_UNESCAPED_UNICODE)) ?>" aria-label="Mapa inzerátů"></div>

    <p class="listing__count" aria-live="polite" data-items-count>Nalezeno: <?= $result['total'] ?></p>

    <div class="grid" data-items-list>
        <?php foreach ($result['items'] as $item): ?>
            <?= render('partials/item-card', ['item' => $item]) ?>
        <?php endforeach ?>
    </div>

    <p class="empty" data-items-empty <?= $result['total'] ? 'hidden' : '' ?>>Žádné inzeráty neodpovídají filtru.</p>

    <a class="button button--secondary listing__more" href="<?= e(url('/inzeraty', [...$query->toArray(), 'page' => $query->page + 1])) ?>" data-load-more data-page="<?= $query->page + 1 ?>" <?= $hasMore ? '' : 'hidden' ?>>
        Načíst další
    </a>
</div>
