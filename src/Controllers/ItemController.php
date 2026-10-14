<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ItemQuery;

final class ItemController extends Controller
{
    public function index(): void
    {
        $query = ItemQuery::fromArray($_GET);

        view('items/index', [
            'title' => 'Ztracené a nalezené věci',
            'description' => 'Přehled ztracených a nalezených věcí na kampusu ČZU. Filtrujte podle budovy, kategorie nebo hledejte.',
            'query' => $query,
            'result' => $this->items()->search($query),
            'categories' => $this->items()->categories(),
            'buildings' => $this->items()->buildings(),
        ]);
    }

    public function show(string $id, string $slug): void
    {
        $item = $this->items()->find((int) $id) ?? abort(404);

        if ($slug !== $item['slug']) {
            redirect("/inzerat/{$item['id']}/{$item['slug']}", 301);
        }

        view('items/show', [
            'title' => $item['title'],
            'description' => mb_strimwidth($item['description'], 0, 155, '…'),
            'item' => $item,
        ]);
    }
}
