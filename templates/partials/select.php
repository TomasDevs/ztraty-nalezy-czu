<?php
/**
 * @var string $name
 * @var string $label
 * @var array{slug: string, name: string}[] $options
 * @var string|null $selected
 */
?>
<div class="field">
    <label for="filter-<?= e($name) ?>"><?= e($label) ?></label>
    <select id="filter-<?= e($name) ?>" name="<?= e($name) ?>">
        <option value="">Vše</option>
        <?php foreach ($options as $option): ?>
            <option value="<?= e($option['slug']) ?>" <?= $option['slug'] === $selected ? 'selected' : '' ?>>
                <?= e($option['name']) ?>
            </option>
        <?php endforeach ?>
    </select>
</div>
