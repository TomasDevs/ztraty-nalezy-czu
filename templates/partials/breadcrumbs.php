<?php
/** @var array{0: string, 1: string|null}[] $breadcrumbs Label and path, the last one is the current page */
$last = array_key_last($breadcrumbs);
?>
<nav class="breadcrumbs" aria-label="Drobečková navigace">
    <ol>
        <?php foreach ($breadcrumbs as $i => [$label, $path]): ?>
            <?php if ($i === $last): ?>
                <li aria-current="page"><?= e($label) ?></li>
            <?php else: ?>
                <li><a href="<?= e(url($path)) ?>"><?= e($label) ?></a></li>
            <?php endif ?>
        <?php endforeach ?>
    </ol>
</nav>
