<?php
use App\Repositories\ItemQuery;

$nav = [['label' => 'Všechny inzeráty', 'query' => []]];
foreach (ItemQuery::TYPE_LISTS as $type => $label) {
    $nav[] = ['label' => $label, 'query' => ['type' => $type]];
}
$isListing = current_path() === '/inzeraty';
$activeType = $_GET['type'] ?? null;
$user = auth()->user();
$account = $user
    ? [['label' => 'Můj účet', 'path' => '/ucet'], ...($user['role'] === 'admin' ? [['label' => 'Administrace', 'path' => '/admin']] : [])]
    : [['label' => 'Přihlásit se', 'path' => '/prihlaseni']];
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="logo" href="<?= e(url('/')) ?>">
            <svg class="logo__icon" viewBox="0 0 32 32" aria-hidden="true">
                <path d="M11 4h10l5 5v17a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9z" transform="rotate(-10 16 16)" />
                <circle cx="15" cy="10" r="2" />
            </svg>
            <span>Ztráty a nálezy <span class="logo__suffix">ČZU</span></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-menu-toggle>
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <span class="visually-hidden">Menu</span>
        </button>
        <nav id="main-nav" class="site-nav" aria-label="Hlavní navigace">
            <ul class="nav">
                <?php foreach ($nav as $link): ?>
                    <?php $isActive = $isListing && ($link['query']['type'] ?? null) === $activeType ?>
                    <li>
                        <a href="<?= e(url('/inzeraty', $link['query'])) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= e($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
                <?php foreach ($account as $link): ?>
                    <li>
                        <a href="<?= e(url($link['path'])) ?>" <?= str_starts_with(current_path(), $link['path']) ? 'aria-current="page"' : '' ?>>
                            <?= e($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
                <?php if ($user): ?>
                    <li>
                        <form action="<?= e(url('/odhlaseni')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="nav__button" type="submit">Odhlásit</button>
                        </form>
                    </li>
                <?php endif ?>
            </ul>
        </nav>
        <a class="button site-header__cta" href="<?= e(url('/nahlasit')) ?>" <?= current_path() === '/nahlasit' ? 'aria-current="page"' : '' ?>>Nahlásit<span class="site-header__cta-extra">&nbsp;věc</span></a>
    </div>
</header>
