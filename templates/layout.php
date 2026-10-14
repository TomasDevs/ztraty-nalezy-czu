<?php
/**
 * @var string $content
 * @var string $title
 * @var string|null $description
 * @var bool|null $noindex
 * @var string|null $canonical Path with query, defaults to the current path without query
 * @var string|null $image Absolute URL of the sharing image
 * @var string|null $imageAlt
 * @var string|null $ogType
 * @var array{0: string, 1: string|null}[]|null $breadcrumbs
 * @var array[]|null $jsonLd Extra schema.org objects
 */
$siteName = 'Ztráty a nálezy ČZU';
$fullTitle = $title === $siteName ? $title : "{$title} | {$siteName}";
$canonicalUrl = absolute_url($canonical ?? current_path());
$image ??= absolute_url('/og-image.png');
$imageAlt ??= 'Ztráty a nálezy ČZU, visačka s logem';
$structuredData = [...($jsonLd ?? []), ...(!empty($breadcrumbs) ? [breadcrumbs_ld($breadcrumbs)] : [])];
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <?php if (!empty($description)): ?>
        <meta name="description" content="<?= e($description) ?>">
    <?php endif ?>
    <?php if (!empty($noindex)): ?>
        <meta name="robots" content="noindex">
    <?php else: ?>
        <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <?php endif ?>
    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <meta property="og:locale" content="cs_CZ">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if (!empty($description)): ?>
        <meta property="og:description" content="<?= e($description) ?>">
    <?php endif ?>
    <meta property="og:image" content="<?= e($image) ?>">
    <meta property="og:image:alt" content="<?= e($imageAlt) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php foreach ($structuredData as $data): ?>
        <?= json_ld($data) ?>
    <?php endforeach ?>
    <link rel="icon" type="image/png" href="<?= e(url('/favicon-96x96.png')) ?>" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/favicon.svg')) ?>">
    <link rel="shortcut icon" href="<?= e(url('/favicon.ico')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('/apple-touch-icon.png')) ?>">
    <meta name="apple-mobile-web-app-title" content="Ztráty ČZU">
    <link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
    <meta name="theme-color" content="#f6f4ef">
    <script>document.documentElement.classList.add('js')</script>
    <?= vite() ?>
</head>
<body>
    <a class="skip-link" href="#main">Přeskočit na obsah</a>
    <?= render('partials/header') ?>
    <main id="main" class="container">
        <?= $content ?>
    </main>
    <?= render('partials/footer') ?>
</body>
</html>
