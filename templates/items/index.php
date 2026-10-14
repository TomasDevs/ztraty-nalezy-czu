<?php
use App\Repositories\ItemQuery;

/**
 * @var string $title
 * @var ItemQuery $query
 * @var array{items: array[], total: int, markers: array[]} $result
 * @var array[] $categories
 * @var array[] $buildings
 */
$hasMore = $query->offset() + count($result['items']) < $result['total'];
$hasFilters = array_filter($query->toArray()) !== [];
$typeOptions = array_map(fn (string $slug) => ['slug' => $slug, 'name' => ItemQuery::TYPES[$slug]], array_keys(ItemQuery::TYPES));
$select = fn (string $name, string $label, array $options, ?string $selected) => render('partials/select', compact('name', 'label', 'options', 'selected'));
?>
<div class="page-head">
    <h1 data-items-heading><?= e($title) ?></h1>
    <p class="lead">Všechny inzeráty z kampusu ČZU v Suchdole. Filtrujte podle budovy, kategorie nebo hledejte podle popisu.</p>
</div>

<div class="listing" data-items-filter>
    <form class="filters" action="<?= e(url('/inzeraty')) ?>" method="get" data-api="<?= e(url('/api/items')) ?>">
        <div class="field field--search">
            <label for="filter-q">Hledat</label>
            <input id="filter-q" type="search" name="q" value="<?= e($query->q) ?>" placeholder="Např. ISIC, klíče…">
        </div>
        <?= $select('type', 'Typ', $typeOptions, $query->type) ?>
        <?= $select('category', 'Kategorie', $categories, $query->category) ?>
        <?= $select('building', 'Budova', $buildings, $query->building) ?>
        <button class="button filters__submit" type="submit">Filtrovat</button>
    </form>

    <div class="listing__bar">
        <div class="listing__status">
            <p class="listing__count" aria-live="polite" data-items-count><?= e(items_count($result['total'])) ?></p>
            <span class="spinner" aria-hidden="true"></span>
        </div>
        <a class="listing__reset" href="<?= e(url('/inzeraty')) ?>" data-filter-reset <?= $hasFilters ? '' : 'hidden' ?>>Zrušit filtry</a>
    </div>

    <section class="listing__results" aria-labelledby="results-heading">
        <h2 id="results-heading" class="visually-hidden">Výsledky</h2>
        <div class="grid grid--compact" data-items-list>
            <?php foreach ($result['items'] as $item): ?>
                <?= render('partials/item-card', ['item' => $item]) ?>
            <?php endforeach ?>
        </div>

        <div class="empty" data-items-empty <?= $result['total'] ? 'hidden' : '' ?>>
            <p class="empty__title">Nic jsme nenašli</p>
            <p>Zkuste upravit filtr, nebo se podívejte později. Nové nálezy přibývají každý den.</p>
        </div>

        <a class="button button--secondary listing__more" href="<?= e(url('/inzeraty', [...$query->toArray(), 'page' => $query->page + 1])) ?>" data-load-more data-page="<?= $query->page + 1 ?>" <?= $hasMore ? '' : 'hidden' ?>>
            <span class="spinner" aria-hidden="true"></span>
            Načíst další
        </a>
    </section>

    <div class="listing__map map" data-map data-markers="<?= e(json_encode($result['markers'], JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Mapa inzerátů"></div>
</div>
