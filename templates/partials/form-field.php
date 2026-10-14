<?php
/**
 * @var string $name
 * @var string $label
 * @var string $value
 * @var string|null $error
 * @var string|null $hint
 * @var string|null $type text|email|textarea
 * @var string|null $autocomplete
 * @var bool|null $autofocus
 */
$type ??= 'text';
$id = "field-{$name}";
$describedBy = implode(' ', array_filter([
    !empty($hint) ? "{$id}-hint" : null,
    !empty($error) ? "{$id}-error" : null,
]));
$attributes = sprintf(
    'id="%s" name="%s" required%s%s%s%s',
    e($id),
    e($name),
    $describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '',
    !empty($error) ? ' aria-invalid="true"' : '',
    !empty($autocomplete) ? ' autocomplete="' . e($autocomplete) . '"' : '',
    !empty($autofocus) ? ' autofocus' : ''
);
?>
<div class="field">
    <label for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if (!empty($hint)): ?>
        <p class="field__hint" id="<?= e($id) ?>-hint"><?= e($hint) ?></p>
    <?php endif ?>
    <?php if ($type === 'textarea'): ?>
        <textarea <?= $attributes ?> rows="5"><?= e($value) ?></textarea>
    <?php else: ?>
        <input type="<?= e($type) ?>" value="<?= e($value) ?>" <?= $attributes ?>>
    <?php endif ?>
    <?php if (!empty($error)): ?>
        <p class="field__error" id="<?= e($id) ?>-error"><?= e($error) ?></p>
    <?php endif ?>
</div>
