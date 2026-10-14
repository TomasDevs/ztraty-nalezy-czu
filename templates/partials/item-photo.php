<?php
/**
 * @var array $item
 * @var bool|null $large Detail variant: real alt text, and a note instead of the placeholder for documents.
 */
$large ??= false;
$photo = item_photo_url($item, thumb: !$large);
?>
<?php if ($photo): ?>
    <img class="photo<?= $large ? ' photo--large' : '' ?>" src="<?= e($photo) ?>" alt="<?= $large ? e("Fotka: {$item['title']}") : '' ?>" width="<?= $large ? 1000 : 240 ?>" height="<?= $large ? 750 : 240 ?>" <?= $large ? '' : 'loading="lazy"' ?>>
<?php elseif (!$large): ?>
    <div class="photo photo--empty"><?= render('partials/icon', ['name' => $item['category']['slug']]) ?></div>
<?php elseif (is_private_item($item)): ?>
    <p class="photo-note">
        <?= render('partials/icon', ['name' => $item['category']['slug']]) ?>
        Fotky dokladů nezveřejňujeme, chráníme tím osobní údaje majitele.
    </p>
<?php endif ?>
