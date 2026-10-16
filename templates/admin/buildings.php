<?php
/**
 * @var array[] $rows
 * @var string $path
 * @var App\Forms\BuildingForm $form
 */
$coordinate = ['inputmode' => 'decimal'];
?>
<div class="page-head">
    <h1>Administrace</h1>
</div>

<?= render('partials/admin-nav', ['active' => 'budovy']) ?>

<h2 class="visually-hidden">Budovy</h2>
<div class="table-wrap" role="region" aria-label="Budovy" tabindex="0">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Název</th>
                <th scope="col">Zkratka</th>
                <th scope="col">Adresa</th>
                <th scope="col">Šířka</th>
                <th scope="col">Délka</th>
                <th scope="col">Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $formId = 'row-' . $row['id'] ?>
                <tr>
                    <?php foreach (['name' => 'Název', 'short' => 'Zkratka', 'slug' => 'Adresa', 'lat' => 'Šířka', 'lng' => 'Délka'] as $field => $label): ?>
                        <td><input type="text" name="<?= e($field) ?>" value="<?= e((string) $row[$field]) ?>" form="<?= e($formId) ?>" aria-label="<?= e("{$label} budovy {$row['short']}") ?>"<?= in_array($field, ['lat', 'lng'], true) ? ' inputmode="decimal"' : '' ?> required></td>
                    <?php endforeach ?>
                    <td>
                        <div class="table__actions">
                            <form id="<?= e($formId) ?>" action="<?= e(url("{$path}/{$row['id']}")) ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="link-button" type="submit">Uložit</button>
                            </form>
                            <form action="<?= e(url("{$path}/{$row['id']}/smazat")) ?>" method="post" data-confirm="Opravdu chcete budovu smazat?">
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
    <h2 id="add-heading" class="panel__title">Přidat budovu</h2>
    <form class="form" action="<?= e(url($path)) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <?= form_field($form, 'name', 'Název') ?>
        <?= form_field($form, 'short', 'Zkratka', ['hint' => 'Zobrazuje se u inzerátů, např. PEF.']) ?>
        <?= form_field($form, 'slug', 'Adresa', ['required' => false, 'hint' => 'Nepovinné, vytvoří se z názvu.']) ?>
        <?= form_field($form, 'lat', 'Zeměpisná šířka', ['attributes' => $coordinate + ['placeholder' => '50.1312']]) ?>
        <?= form_field($form, 'lng', 'Zeměpisná délka', ['attributes' => $coordinate + ['placeholder' => '14.3732']]) ?>
        <button class="button" type="submit">Přidat</button>
    </form>
</section>
