<?php
use App\Forms\ItemForm;

/**
 * @var ItemForm $form
 * @var string $action
 * @var string $submit
 * @var array[] $categories
 * @var array[] $buildings
 * @var array|null $item Edited item, used for the current photo
 */
$field = fn (string $name, string $label, array $options = []) => form_field($form, $name, $label, $options);
$buildingPoints = array_map(fn (array $b) => ['slug' => $b['slug'], 'lat' => $b['lat'], 'lng' => $b['lng']], $buildings);
$photoPreview = isset($item) ? item_photo_url($item, thumb: true) : null;
?>
<form class="form item-form" action="<?= e($action) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($form->type) ?>">
    <?php if (isset($form->errors['type'])): ?>
        <p class="field__error"><?= e($form->errors['type']) ?></p>
    <?php endif ?>

    <?= $field('title', 'Název', ['hint' => 'Stručně, např. „Černá peněženka“ nebo „Klíče s modrou klíčenkou“.']) ?>
    <div class="item-form__row">
        <?= $field('category', 'Kategorie', ['type' => 'select', 'options' => $categories]) ?>
        <?= $field('building', 'Budova', ['type' => 'select', 'options' => $buildings]) ?>
    </div>
    <?= $field('location', 'Přesné místo', ['hint' => 'Např. učebna E127, druhá řada.']) ?>
    <?= $field('date', $form->type === 'lost' ? 'Datum ztráty' : 'Datum nálezu', ['type' => 'date']) ?>
    <?= $field('description', 'Popis', ['type' => 'textarea', 'hint' => 'Barva, značka, poškození, obsah. U cizí věci neuvádějte údaje, které má znát jen majitel.']) ?>

    <div class="field field--map">
        <span class="field__label" id="location-picker-label">Místo na mapě</span>
        <p class="field__hint" id="location-picker-hint">Klikněte do mapy, kde jste věc našli nebo ztratili. Když místo nevyberete, použije se budova.</p>
        <div class="location-picker map" data-location-picker
             data-buildings="<?= e(json_encode($buildingPoints)) ?>"
             data-type="<?= e($form->type) ?>"
             role="region" aria-label="Vyberte místo na mapě" aria-describedby="location-picker-hint"></div>
        <button class="button button--secondary" type="button" data-location-reset hidden>Použít místo budovy</button>
        <input type="hidden" name="lat" value="<?= e($form->lat) ?>">
        <input type="hidden" name="lng" value="<?= e($form->lng) ?>">
        <?php if (isset($form->errors['lat'])): ?>
            <p class="field__error"><?= e($form->errors['lat']) ?></p>
        <?php endif ?>
    </div>

    <?php if ($photoPreview): ?>
        <img class="photo item-form__photo" src="<?= e($photoPreview) ?>" alt="Současná fotka" width="120" height="120">
    <?php endif ?>
    <?= $field('photo', $photoPreview ? 'Nahradit fotku' : 'Fotka', [
        'type' => 'file',
        'required' => false,
        'hint' => 'JPEG, PNG nebo WebP do 5 MB. Fotky dokladů nezveřejňujeme.',
        'attributes' => ['accept' => implode(',', ItemForm::PHOTO_TYPES)],
    ]) ?>

    <button class="button" type="submit"><?= e($submit) ?></button>
</form>
