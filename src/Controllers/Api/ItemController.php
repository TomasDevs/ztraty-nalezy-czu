<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\App;
use App\Repositories\ItemQuery;
use App\Seo\ListingMeta;

final class ItemController extends Controller
{
    /**
     * Returns rendered cards so the page and AJAX share one template.
     */
    public function index(): void
    {
        $query = ItemQuery::fromArray($_GET);
        $result = App::items()->search($query);

        json([
            'html' => implode('', array_map(
                fn (array $item) => render('partials/item-card', ['item' => $item]),
                $result['items']
            )),
            'total' => $result['total'],
            'totalLabel' => items_count($result['total']),
            'heading' => ListingMeta::for($query, App::categories()->all(), App::buildings()->all())->heading,
            'markers' => $result['markers'],
            'nextPage' => $query->offset() + count($result['items']) < $result['total'] ? $query->page + 1 : null,
        ]);
    }
}
