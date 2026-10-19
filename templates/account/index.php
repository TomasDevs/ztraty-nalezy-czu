<?php
use App\Repositories\ItemQuery;

/**
 * @var array $user
 * @var array[] $items
 * @var array{0: string, 1: string|null}[] $breadcrumbs
 */
$statuses = ['active' => 'Aktivní', 'returned' => 'Vráceno', 'hidden' => 'Skryto'];
?>
<?= render('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="page-head">
    <h1>Můj účet</h1>
    <p class="lead"><?= e($user['name']) ?> &middot; <?= e($user['email']) ?></p>
</div>

<section aria-labelledby="my-items-heading">
    <div class="section__head">
        <h2 id="my-items-heading">Moje inzeráty</h2>
        <a class="button" href="<?= e(url('/nahlasit')) ?>">Nahlásit věc</a>
    </div>

    <?php if (!$items): ?>
        <p class="panel">Zatím nemáte žádný inzerát.</p>
    <?php else: ?>
        <div class="table-wrap" role="region" aria-label="Tabulka mých inzerátů" tabindex="0">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Číslo</th>
                        <th scope="col">Název</th>
                        <th scope="col">Typ</th>
                        <th scope="col">Stav</th>
                        <th scope="col">Datum</th>
                        <th scope="col">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="code"><?= e(item_code($item)) ?></td>
                            <td><a href="<?= e(item_url($item)) ?>"><?= e($item['title']) ?></a></td>
                            <td><?= e(ItemQuery::TYPES[$item['type']]) ?></td>
                            <td><?= e($statuses[$item['status']]) ?></td>
                            <td><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></td>
                            <td>
                                <div class="table__actions">
                                    <a href="<?= e(url("/ucet/inzeraty/{$item['id']}/upravit")) ?>">Upravit</a>
                                    <?php if ($item['status'] === 'active'): ?>
                                        <form action="<?= e(url("/ucet/inzeraty/{$item['id']}/vraceno")) ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button class="link-button" type="submit">Vráceno</button>
                                        </form>
                                    <?php endif ?>
                                    <form action="<?= e(url("/ucet/inzeraty/{$item['id']}/smazat")) ?>" method="post" data-confirm="Opravdu chcete inzerát smazat? Nejde to vrátit.">
                                        <?= csrf_field() ?>
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
</section>
