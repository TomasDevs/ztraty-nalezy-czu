<?php
/**
 * @var string $name
 * @var string $label
 * @var string $value
 * @var string|null $error
 * @var string|null $hint
 * @var string|null $type text|email|password|date|file|select|textarea
 * @var string|null $autocomplete
 * @var bool|null $autofocus
 * @var bool|null $required Defaults to true
 * @var array{slug: string, name: string}[]|null $options For select
 * @var array<string, string>|null $attributes Extra HTML attributes, e.g. accept
 */
$type ??= 'text';
$required ??= true;
$attributes ??= [];
$id = "field-{$name}";
$describedBy = implode(' ', array_filter([
    !empty($hint) ? "{$id}-hint" : null,
    !empty($error) ? "{$id}-error" : null,
]));

if ($type === 'date') {
    $attributes += ['max' => date('Y-m-d')];
}

$extra = array_filter([
    'aria-describedby' => $describedBy,
    'autocomplete' => $autocomplete ?? null,
    ...$attributes,
]);
$attributeHtml = sprintf('id="%s" name="%s"', e($id), e($name))
    . ($required ? ' required' : '')
    . (!empty($error) ? ' aria-invalid="true"' : '')
    . (!empty($autofocus) ? ' autofocus' : '')
    . implode('', array_map(fn ($key, $val) => sprintf(' %s="%s"', e($key), e($val)), array_keys($extra), $extra));
$hasValue = !in_array($type, ['password', 'file'], true);
?>
<div class="field">
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if (!empty($hint)): ?>
        <p class="field__hint" id="<?= e($id) ?>-hint"><?= e($hint) ?></p>
    <?php endif ?>
    <?php if ($type === 'textarea'): ?>
        <textarea <?= $attributeHtml ?> rows="5"><?= e($value) ?></textarea>
    <?php elseif ($type === 'select'): ?>
        <select <?= $attributeHtml ?>>
            <option value="">Vyberte…</option>
            <?php foreach ($options as $option): ?>
                <option value="<?= e($option['slug']) ?>" <?= $option['slug'] === $value ? 'selected' : '' ?>>
                    <?= e($option['name']) ?>
                </option>
            <?php endforeach ?>
        </select>
    <?php else: ?>
        <input type="<?= e($type) ?>"<?= $hasValue ? ' value="' . e($value) . '"' : '' ?> <?= $attributeHtml ?>>
    <?php endif ?>
    <?php if (!empty($error)): ?>
        <p class="field__error" id="<?= e($id) ?>-error"><?= e($error) ?></p>
    <?php endif ?>
</div>
