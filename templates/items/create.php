<?php
use App\Repositories\ItemQuery;

/**
 * @var string|null $type
 * @var App\Forms\ItemForm|null $form
 * @var array[] $categories
 * @var array[] $buildings
 */
$options = [
    'found' => ['Něco jsem našel', 'Věc, kterou jste našli na kampusu. Majitel se vám ozve přes formulář u inzerátu.', 'pin'],
    'lost' => ['Něco jsem ztratil', 'Popište věc a místo, kde jste ji mohli ztratit. Nálezce vám napíše.', 'search'],
];
?>
<div class="page-head">
    <h1>Nahlásit věc</h1>
    <p class="lead">Vyberte, co se stalo. Vyplnění inzerátu zabere asi minutu.</p>
</div>

<ul class="choices">
    <?php foreach ($options as $slug => [$heading, $text, $icon]): ?>
        <li>
            <a class="choice" href="<?= e(url('/nahlasit', ['type' => $slug])) ?>" <?= $type === $slug ? 'aria-current="true"' : '' ?>>
                <span class="choice__icon"><?= render('partials/icon', ['name' => $icon]) ?></span>
                <span class="choice__title"><?= e($heading) ?></span>
                <span class="choice__text"><?= e($text) ?></span>
            </a>
        </li>
    <?php endforeach ?>
</ul>

<?php if ($type): ?>
    <section class="panel create-step" aria-labelledby="step-heading">
        <h2 id="step-heading"><?= e(ItemQuery::TYPES[$type]) ?>: údaje o věci</h2>
        <?php if ($form): ?>
            <?= render('partials/item-form', [
                'form' => $form,
                'action' => url('/nahlasit'),
                'submit' => 'Zveřejnit inzerát',
                'categories' => $categories,
                'buildings' => $buildings,
            ]) ?>
        <?php else: ?>
            <?= render('partials/login-prompt', ['message' => 'Inzerát mohou přidávat jen přihlášení uživatelé.']) ?>
        <?php endif ?>
        <?php if ($type === 'found'): ?>
            <p class="create-step__tip">
                Nalezenou věc můžete mezitím odevzdat na
                <a href="https://www.pef.czu.cz/cs/r-7008-studium/r-7026-informace-pro-studenty/r-10500-ztraty-a-nalezy">Správě učeben PEF (E250)</a>
                nebo v <a href="https://lib.czu.cz/cs/r-8832-knihovna/r-15180-ztraty-a-nalezy">knihovně ČZU</a>.
            </p>
        <?php endif ?>
    </section>
<?php endif ?>
