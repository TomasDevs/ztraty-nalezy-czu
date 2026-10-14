<?php
/** @var array{0: string, 1: string|null}[] $urls */
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as [$path, $lastmod]): ?>
    <url>
        <loc><?= e(absolute_url($path)) ?></loc>
        <?php if ($lastmod): ?><lastmod><?= e($lastmod) ?></lastmod><?php endif ?>
    </url>
<?php endforeach ?>
</urlset>
