<?php
/**
 * @var array[] $rows
 * @var string $path
 * @var App\Forms\CategoryForm $form
 */
?>
<div class="page-head">
    <h1>Administrace</h1>
</div>

<?= render('partials/admin-nav', ['active' => 'kategorie']) ?>

<h2 class="visually-hidden">Kategorie</h2>
<div class="table-wrap" role="region" aria-label="Kategorie" tabindex="0">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Název</th>
                <th scope="col">Adresa</th>
                <th scope="col">Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="text" name="name" value="<?= e($row['name']) ?>" form="row-<?= e((string) $row['id']) ?>" aria-label="Název kategorie <?= e($row['name']) ?>" required></td>
                    <td><input type="text" name="slug" value="<?= e($row['slug']) ?>" form="row-<?= e((string) $row['id']) ?>" aria-label="Adresa kategorie <?= e($row['name']) ?>" required></td>
                    <td>
                        <div class="table__actions">
                            <form id="row-<?= e((string) $row['id']) ?>" action="<?= e(url("{$path}/{$row['id']}")) ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="link-button" type="submit">Uložit</button>
                            </form>
                            <form action="<?= e(url("{$path}/{$row['id']}/smazat")) ?>" method="post" data-confirm="Opravdu chcete kategorii smazat?">
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

<section class="panel admin-add" aria-labelledby="add-heading">
    <h2 id="add-heading" class="panel__title">Přidat kategorii</h2>
    <form class="form" action="<?= e(url($path)) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <?= form_field($form, 'name', 'Název') ?>
        <?= form_field($form, 'slug', 'Adresa', ['required' => false, 'hint' => 'Nepovinné, vytvoří se z názvu. Jen malá písmena bez diakritiky, čísla a pomlčky.']) ?>
        <button class="button" type="submit">Přidat</button>
    </form>
</section>
