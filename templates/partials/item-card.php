<?php
use App\Repositories\ItemQuery;

/** @var array $item */
$isReturned = $item['status'] === 'returned';
?>
<article class="card<?= $isReturned ? ' card--returned' : '' ?>">
    <div class="card__badges">
        <span class="badge badge--<?= e($item['type']) ?>"><?= e(ItemQuery::TYPES[$item['type']]) ?></span>
        <?php if ($isReturned): ?>
            <span class="badge badge--returned">Vráceno</span>
        <?php endif ?>
    </div>
    <h3 class="card__title">
        <a href="<?= e(item_url($item)) ?>"><?= e($item['title']) ?></a>
    </h3>
    <dl class="card__meta">
        <div><dt>Kategorie</dt><dd><?= e($item['category']['name']) ?></dd></div>
        <div><dt>Místo</dt><dd><?= e($item['location']) ?></dd></div>
        <div><dt>Datum</dt><dd><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></dd></div>
    </dl>
</article>
