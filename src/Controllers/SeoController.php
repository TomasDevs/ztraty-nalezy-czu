<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ItemQuery;

final class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /api/\n\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
    }

    public function sitemap(): void
    {
        $items = $this->items()->all();
        $latest = $items[0]['date'] ?? null;

        $urls = [
            ['/', $latest],
            ['/inzeraty', $latest],
            ['/nahlasit', null],
        ];

        foreach (array_keys(ItemQuery::TYPE_LISTS) as $type) {
            $urls[] = ['/inzeraty?' . http_build_query(['type' => $type]), $latest];
        }

        foreach (['category' => $this->items()->categories(), 'building' => $this->items()->buildings()] as $key => $rows) {
            foreach ($rows as $row) {
                $urls[] = ['/inzeraty?' . http_build_query([$key => $row['slug']]), null];
            }
        }

        foreach ($items as $item) {
            $urls[] = [item_path($item), $item['date']];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo render('seo/sitemap', ['urls' => $urls]);
    }
}
