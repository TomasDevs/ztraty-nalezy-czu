<?php
/** @var array $item */
?>
<article class="card<?= $item['status'] === 'returned' ? ' card--returned' : '' ?>">
    <div class="card__top">
        <div class="tags"><?= render('partials/item-tags', ['item' => $item]) ?></div>
        <span class="code"><?= e(item_code($item)) ?></span>
    </div>
    <h3 class="card__title">
        <a href="<?= e(item_url($item)) ?>"><?= e($item['title']) ?></a>
    </h3>
    <p class="card__place"><?= e($item['location']) ?>, <?= e($item['building']['short']) ?></p>
    <div class="card__foot">
        <span><?= e($item['category']['name']) ?></span>
        <time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time>
    </div>
</article>
