<?php
/**
 * @var array $item
 * @var App\Forms\ItemForm $form
 * @var array[] $categories
 * @var array[] $buildings
 * @var array{0: string, 1: string|null}[] $breadcrumbs
 */
?>
<?= render('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="page-head">
    <h1>Upravit inzerát</h1>
    <p class="lead"><span class="code"><?= e(item_code($item)) ?></span> &middot; <a href="<?= e(item_url($item)) ?>">Zpět na inzerát</a></p>
</div>

<section class="panel create-step" aria-label="Úprava inzerátu">
    <?= render('partials/item-form', [
        'form' => $form,
        'item' => $item,
        'action' => url("/ucet/inzeraty/{$item['id']}/upravit"),
        'submit' => 'Uložit změny',
        'categories' => $categories,
        'buildings' => $buildings,
    ]) ?>
</section>
