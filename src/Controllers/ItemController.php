<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forms\ContactForm;
use App\Repositories\ItemQuery;
use App\Seo\ListingMeta;

final class ItemController extends Controller
{
    public function index(): void
    {
        $query = ItemQuery::fromArray($_GET);
        $meta = ListingMeta::for($query, $this->items());

        view('items/index', [
            'title' => $meta->heading,
            'description' => 'Přehled ztracených a nalezených věcí na kampusu ČZU. Filtrujte podle budovy, kategorie nebo hledejte.',
            'canonical' => $meta->canonical,
            'noindex' => $meta->noindex,
            'query' => $query,
            'result' => $this->items()->search($query),
            'categories' => $this->items()->categories(),
            'buildings' => $this->items()->buildings(),
        ]);
    }

    public function create(): void
    {
        $type = $_GET['type'] ?? null;

        view('items/create', [
            'title' => 'Nahlásit ztracenou nebo nalezenou věc',
            'description' => 'Ztratili nebo našli jste věc na kampusu ČZU? Nahlaste ji a pomozte jí najít cestu zpět k majiteli.',
            'type' => isset(ItemQuery::TYPES[$type]) ? $type : null,
        ]);
    }

    public function show(string $id, string $slug): void
    {
        $item = $this->findOrFail($id, $slug);

        $this->renderDetail($item, new ContactForm(), sent: isset($_GET['odeslano']));
    }

    public function contact(string $id, string $slug): void
    {
        $item = $this->findOrFail($id, $slug);
        $form = ContactForm::fromArray($_POST);

        if ($item['status'] === 'returned') {
            redirect(item_path($item), 303);
        }

        if (!$form->validate()) {
            http_response_code(422);
            $this->renderDetail($item, $form);
            return;
        }

        // TODO: send the message by e-mail once users and the DB exist (CSRF token with sessions)
        redirect(item_path($item) . '?odeslano=1#kontakt', 303);
    }

    private function findOrFail(string $id, string $slug): array
    {
        $item = $this->items()->find((int) $id) ?? abort(404);

        if ($slug !== $item['slug']) {
            redirect(item_path($item), 301);
        }

        return $item;
    }

    private function renderDetail(array $item, ContactForm $form, bool $sent = false): void
    {
        $photo = item_photo_path($item);

        view('items/show', [
            'title' => $item['title'],
            'description' => mb_strimwidth($item['description'], 0, 155, '…'),
            'ogType' => 'article',
            'image' => $photo ? absolute_url($photo) : null,
            'imageAlt' => $photo ? "Fotka: {$item['title']}" : null,
            'breadcrumbs' => [
                ['Úvod', '/'],
                [ItemQuery::TYPE_LISTS[$item['type']], '/inzeraty?' . http_build_query(['type' => $item['type']])],
                [$item['title'], null],
            ],
            'item' => $item,
            'related' => $this->items()->related($item, 3),
            'form' => $form,
            'sent' => $sent,
        ]);
    }
}
