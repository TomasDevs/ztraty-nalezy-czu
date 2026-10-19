<?php
use App\Repositories\ItemQuery;

/**
 * @var array[] $items
 * @var string|null $status
 * @var array<string, string> $statuses
 */
?>
<div class="page-head">
    <h1>Administrace</h1>
</div>

<?= render('partials/admin-nav', ['active' => 'inzeraty']) ?>

<h2 class="visually-hidden">Inzeráty</h2>
<ul class="filter-links" aria-label="Filtr podle stavu">
    <li><a href="<?= e(url('/admin/inzeraty')) ?>" <?= $status === null ? 'aria-current="page"' : '' ?>>Všechny</a></li>
    <?php foreach ($statuses as $key => $label): ?>
        <li><a href="<?= e(url('/admin/inzeraty', ['stav' => $key])) ?>" <?= $status === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a></li>
    <?php endforeach ?>
</ul>

<?php if (!$items): ?>
    <p class="panel">Žádné inzeráty.</p>
<?php else: ?>
    <div class="table-wrap" role="region" aria-label="Inzeráty" tabindex="0">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Číslo</th>
                    <th scope="col">Název</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Typ</th>
                    <th scope="col">Stav</th>
                    <th scope="col">Datum</th>
                    <th scope="col">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $isHidden = $item['status'] === 'hidden' ?>
                    <tr>
                        <td class="code"><?= e(item_code($item)) ?></td>
                        <td><a href="<?= e(item_url($item)) ?>"><?= e($item['title']) ?></a></td>
                        <td><?= e($item['author_email']) ?></td>
                        <td><?= e(ItemQuery::TYPES[$item['type']]) ?></td>
                        <td><?= e($statuses[$item['status']]) ?></td>
                        <td><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></td>
                        <td>
                            <div class="table__actions">
                                <form action="<?= e(url("/admin/inzeraty/{$item['id']}/stav")) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="stav" value="<?= e((string) $status) ?>">
                                    <input type="hidden" name="status" value="<?= $isHidden ? 'active' : 'hidden' ?>">
                                    <button class="link-button" type="submit"><?= $isHidden ? 'Zobrazit' : 'Skrýt' ?></button>
                                </form>
                                <form action="<?= e(url("/admin/inzeraty/{$item['id']}/smazat")) ?>" method="post" data-confirm="Opravdu chcete inzerát smazat? Nejde to vrátit.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="stav" value="<?= e((string) $status) ?>">
                                    <button class="link-button link-button--danger" type="submit">Smazat</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
