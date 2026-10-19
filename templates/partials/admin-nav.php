<?php
/** @var string $active inzeraty|uzivatele|kategorie|budovy */
$links = ['inzeraty' => 'Inzeráty', 'uzivatele' => 'Uživatelé', 'kategorie' => 'Kategorie', 'budovy' => 'Budovy'];
?>
<nav class="admin-nav" aria-label="Administrace">
    <ul>
        <?php foreach ($links as $slug => $label): ?>
            <li>
                <a href="<?= e(url("/admin/{$slug}")) ?>" <?= $slug === $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
