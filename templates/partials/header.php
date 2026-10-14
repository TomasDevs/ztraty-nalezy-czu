<?php
$nav = [
    ['label' => 'Všechny inzeráty', 'path' => '/inzeraty', 'query' => []],
    ['label' => 'Ztráty', 'path' => '/inzeraty', 'query' => ['type' => 'lost']],
    ['label' => 'Nálezy', 'path' => '/inzeraty', 'query' => ['type' => 'found']],
];
$activeType = $_GET['type'] ?? null;
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="logo" href="<?= e(url('/')) ?>">
            <span class="logo__mark" aria-hidden="true">?!</span>
            Ztráty a nálezy <strong>ČZU</strong>
        </a>
        <nav aria-label="Hlavní navigace">
            <ul class="nav">
                <?php foreach ($nav as $link): ?>
                    <?php $isActive = current_path() === $link['path'] && ($link['query']['type'] ?? null) === $activeType ?>
                    <li>
                        <a href="<?= e(url($link['path'], $link['query'])) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= e($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>
    </div>
</header>
