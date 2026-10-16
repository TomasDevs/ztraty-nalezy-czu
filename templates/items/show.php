<?php
use App\Forms\ContactForm;

/**
 * @var array $item
 * @var array{0: string, 1: string|null}[] $breadcrumbs
 * @var array[] $related
 * @var ContactForm $form
 * @var bool $sent
 * @var bool $canManage
 * @var bool $canContact
 * @var bool $isGuest
 */
$isFound = $item['type'] === 'found';
$isReturned = $item['status'] === 'returned';
$copy = $isFound
    ? ['date' => 'Nalezeno', 'lead' => 'Je to vaše?', 'action' => 'Kontaktovat nálezce', 'hint' => 'Popište věc tak, aby nálezce poznal, že je opravdu vaše: barva, obsah, poškození, gravírování…']
    : ['date' => 'Ztraceno', 'lead' => 'Našli jste tuto věc?', 'action' => 'Napsat majiteli', 'hint' => 'Napište, kde a kdy jste věc našli a kde si ji majitel může vyzvednout.'];
$marker = [['lat' => $item['lat'], 'lng' => $item['lng'], 'title' => $item['title'], 'type' => $item['type']]];
$largeMapUrl = sprintf('https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=18/%1$s/%2$s', $item['lat'], $item['lng']);
$field = fn (string $name, string $label, array $options = []) => form_field($form, $name, $label, $options);
?>
<?= render('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<?php if ($item['status'] === 'hidden'): ?>
    <p class="alert alert--error" role="status">Inzerát je skrytý administrátorem. Návštěvníkům se nezobrazuje.</p>
<?php endif ?>

<article class="detail">
    <header class="detail__header">
        <div class="tags"><?= render('partials/item-tags', ['item' => $item]) ?></div>
        <h1><?= e($item['title']) ?></h1>
        <p class="detail__subtitle">
            <?= e($copy['date']) ?> <time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time>
            &middot; <?= e($item['location']) ?>, <?= e($item['building']['short']) ?>
        </p>
    </header>

    <div class="detail__layout">
        <div class="detail__main">
            <?= render('partials/item-photo', ['item' => $item, 'large' => true]) ?>
            <h2 class="detail__heading">Popis</h2>
            <p class="detail__description"><?= e($item['description']) ?></p>
        </div>

        <aside class="detail__aside" aria-label="Akce a údaje o inzerátu">
            <div class="panel action">
                <?php if ($isReturned): ?>
                    <p class="action__returned">Věc už je zpátky u majitele. Děkujeme všem, kdo pomohli.</p>
                <?php elseif ($canContact || $isGuest): ?>
                    <p class="action__lead"><?= e($copy['lead']) ?></p>
                    <a class="button button--block" href="#kontakt"><?= e($copy['action']) ?></a>
                <?php endif ?>
                <button class="button button--secondary button--block" type="button" data-share data-url="<?= e(absolute_url(item_path($item))) ?>" data-title="<?= e($item['title']) ?>" hidden>
                    Sdílet inzerát
                </button>
                <span class="visually-hidden" aria-live="polite" data-share-status></span>
            </div>

            <?php if ($canManage): ?>
                <section class="panel manage" aria-labelledby="manage-heading">
                    <h2 id="manage-heading" class="panel__title">Správa inzerátu</h2>
                    <a class="button button--secondary button--block" href="<?= e(url("/ucet/inzeraty/{$item['id']}/upravit")) ?>">Upravit</a>
                    <?php if ($item['status'] === 'active'): ?>
                        <form action="<?= e(url("/ucet/inzeraty/{$item['id']}/vraceno")) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="button button--secondary button--block" type="submit">Označit jako vrácené</button>
                        </form>
                    <?php endif ?>
                    <form action="<?= e(url("/ucet/inzeraty/{$item['id']}/smazat")) ?>" method="post" data-confirm="Opravdu chcete inzerát smazat? Nejde to vrátit.">
                        <?= csrf_field() ?>
                        <button class="button button--danger button--block" type="submit">Smazat</button>
                    </form>
                </section>
            <?php endif ?>

            <section class="panel facts" aria-labelledby="facts-heading">
                <h2 id="facts-heading" class="panel__title">Údaje o inzerátu</h2>
                <dl>
                    <dt>Číslo</dt>
                    <dd class="code"><?= e(item_code($item)) ?></dd>
                    <dt>Kategorie</dt>
                    <dd><a href="<?= e(url('/inzeraty', ['category' => $item['category']['slug']])) ?>"><?= e($item['category']['name']) ?></a></dd>
                    <dt>Budova</dt>
                    <dd><a href="<?= e(url('/inzeraty', ['building' => $item['building']['slug']])) ?>"><?= e($item['building']['name']) ?></a></dd>
                    <dt>Místo</dt>
                    <dd><?= e($item['location']) ?></dd>
                    <dt><?= e($copy['date']) ?></dt>
                    <dd><time datetime="<?= e($item['date']) ?>"><?= e(format_date($item['date'])) ?></time></dd>
                </dl>
                <div class="detail__map map" data-map data-markers="<?= e(json_encode($marker, JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Místo na mapě"></div>
                <a class="facts__map-link" href="<?= e($largeMapUrl) ?>">Otevřít ve větší mapě</a>
            </section>
        </aside>

        <?php if (!$isReturned && ($canContact || $isGuest)): ?>
            <section id="kontakt" class="panel contact" aria-labelledby="contact-heading">
                <h2 id="contact-heading"><?= e($copy['action']) ?></h2>
                <?php if ($isGuest): ?>
                    <?= render('partials/login-prompt', ['message' => 'Pro kontaktování autora se musíte přihlásit.']) ?>
                <?php else: ?>
                    <p class="contact__intro">Zprávu přepošleme autorovi e-mailem. Odpověď vám přijde na adresu vašeho účtu, autorovi ji nezobrazujeme v inzerátu.</p>

                    <?php if ($sent): ?>
                        <p class="alert alert--success" role="status">Zpráva je odeslaná. Autor vám odpoví e-mailem.</p>
                    <?php elseif ($form->errors): ?>
                        <p class="alert alert--error" role="alert">Zprávu se nepodařilo odeslat. Opravte prosím zvýrazněné pole.</p>
                    <?php endif ?>

                    <form class="contact__form" action="<?= e(item_url($item)) ?>#kontakt" method="post" novalidate>
                        <?= csrf_field() ?>
                        <?= $field('message', 'Zpráva', ['type' => 'textarea', 'hint' => $copy['hint']]) ?>
                        <button class="button" type="submit">Odeslat zprávu</button>
                    </form>
                <?php endif ?>
            </section>
        <?php endif ?>
    </div>
</article>

<?php if ($related): ?>
    <section class="section" aria-labelledby="related-heading">
        <div class="section__head">
            <h2 id="related-heading">Podobné inzeráty</h2>
            <a href="<?= e(url('/inzeraty')) ?>">Všechny inzeráty</a>
        </div>
        <div class="grid">
            <?php foreach ($related as $relatedItem): ?>
                <?= render('partials/item-card', ['item' => $relatedItem]) ?>
            <?php endforeach ?>
        </div>
    </section>
<?php endif ?>
