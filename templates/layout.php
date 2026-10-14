<?php
/**
 * @var string $content
 * @var string $title
 * @var string|null $description
 * @var bool|null $noindex
 */
$siteName = 'Ztráty a nálezy ČZU';
$fullTitle = $title === $siteName ? $title : "{$title} | {$siteName}";
$canonical = absolute_url(current_path());
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
        <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <?php if (!empty($description)): ?>
        <meta property="og:description" content="<?= e($description) ?>">
    <?php endif ?>
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
