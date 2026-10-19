<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\ItemPolicy;
use App\Core\App;
use App\Forms\ContactForm;
use App\Forms\ItemForm;
use App\Mail\ContactMail;
use App\Repositories\ItemQuery;
use App\Seo\ListingMeta;
use App\Uploads\PhotoProcessor;

final class ItemController extends Controller
{
    public function index(): void
    {
        $query = ItemQuery::fromArray($_GET);
        $meta = ListingMeta::for($query, App::categories()->all(), App::buildings()->all());

        view('items/index', [
            'title' => $meta->heading,
            'description' => 'Přehled ztracených a nalezených věcí na kampusu ČZU. Filtrujte podle budovy, kategorie nebo hledejte.',
            'canonical' => $meta->canonical,
            'noindex' => $meta->noindex,
            'query' => $query,
            'result' => App::items()->search($query),
            'categories' => App::categories()->all(),
            'buildings' => App::buildings()->all(),
        ]);
    }

    public function create(): void
    {
        $type = $_GET['type'] ?? null;
        $type = is_string($type) && isset(ItemQuery::TYPES[$type]) ? $type : null;

        $this->renderCreate($type !== null && auth()->check() ? new ItemForm(type: $type, date: date('Y-m-d')) : null, $type);
    }

    public function store(): void
    {
        $user = $this->requireUser();
        $form = ItemForm::fromArray($_POST, $_FILES['photo'] ?? null);

        if (!$form->validate(App::categories()->all(), App::buildings()->all())) {
            http_response_code(422);
            $this->renderCreate($form, isset(ItemQuery::TYPES[$form->type]) ? $form->type : null);
            return;
        }

        $id = App::items()->create([...$form->toData(), 'user_id' => $user['id']]);
        $this->storePhoto($form, $id);
        App::session()->flash('success', 'Inzerát je zveřejněný.');

        redirect(item_path(App::items()->find($id)), 303);
    }

    public function edit(string $id): void
    {
        $item = $this->findManageable($id);

        $this->renderEdit($item, ItemForm::fromItem($item));
    }

    public function update(string $id): void
    {
        $item = $this->findManageable($id);
        $form = ItemForm::fromArray($_POST, $_FILES['photo'] ?? null);

        if (!$form->validate(App::categories()->all(), App::buildings()->all())) {
            http_response_code(422);
            $this->renderEdit($item, $form);
            return;
        }

        App::items()->update($item['id'], $form->toData());

        if (!$form->allowsPhoto()) {
            $this->replacePhoto($item, null);
        } else {
            $this->storePhoto($form, $item['id'], $item['photo']);
        }

        App::session()->flash('success', 'Inzerát je upravený.');

        redirect(item_path(App::items()->find($item['id'])), 303);
    }

    public function markReturned(string $id): void
    {
        $item = $this->findManageable($id);

        App::items()->setStatus($item['id'], 'returned');
        App::session()->flash('success', 'Věc je označená jako vrácená.');

        redirect(item_path($item), 303);
    }

    public function destroy(string $id): void
    {
        $this->deleteItem($this->findManageable($id));
        App::session()->flash('success', 'Inzerát je smazaný.');

        redirect('/ucet', 303);
    }

    public function show(string $id, string $slug): void
    {
        $item = $this->findOrFail($id, $slug);

        $this->renderDetail($item, new ContactForm(), sent: isset($_GET['odeslano']));
    }

    public function contact(string $id, string $slug): void
    {
        $item = $this->findOrFail($id, $slug);
        $user = $this->requireUser();

        if (!ItemPolicy::canContact($user, $item)) {
            redirect(item_path($item), 303);
        }

        $form = ContactForm::fromArray($_POST);

        if (!$form->validate()) {
            http_response_code(422);
            $this->renderDetail($item, $form);
            return;
        }

        App::messages()->create($item['id'], $user['id'], $form->message);

        $mail = ContactMail::create($item, App::users()->find($item['user_id']), $user, $form->message);

        if (!App::mailer()->send($mail)) {
            App::session()->flash('error', 'Zprávu se nepodařilo odeslat e-mailem, zkuste to později.');
            redirect(item_path($item) . '#kontakt', 303);
        }

        redirect(item_path($item) . '?odeslano=1#kontakt', 303);
    }

    private function findOrFail(string $id, string $slug): array
    {
        $item = App::items()->find((int) $id) ?? abort(404);

        if (!ItemPolicy::canView(auth()->user(), $item)) {
            abort(404);
        }

        if ($slug !== $item['slug']) {
            redirect(item_path($item), 301);
        }

        return $item;
    }

    private function findManageable(string $id): array
    {
        $user = $this->requireUser();
        $item = App::items()->find((int) $id) ?? abort(404);

        return ItemPolicy::canManage($user, $item) ? $item : abort(403);
    }

    private function storePhoto(ItemForm $form, int $id, ?string $oldPhoto = null): void
    {
        if (!$form->hasPhoto()) {
            return;
        }

        $path = PhotoProcessor::newPath($id);

        try {
            App::photos()->store($form->photo['tmp_name'], $path);
        } catch (\RuntimeException) {
            App::session()->flash('error', 'Fotku se nepodařilo zpracovat.');
            return;
        }

        App::items()->setPhoto($id, $path);
        App::photos()->delete($oldPhoto);
    }

    private function replacePhoto(array $item, ?string $path): void
    {
        App::photos()->delete($item['photo']);
        App::items()->setPhoto($item['id'], $path);
    }

    private function renderCreate(?ItemForm $form, ?string $type): void
    {
        view('items/create', [
            'title' => 'Nahlásit ztracenou nebo nalezenou věc',
            'description' => 'Ztratili nebo našli jste věc na kampusu ČZU? Nahlaste ji a pomozte jí najít cestu zpět k majiteli.',
            'type' => $type,
            'form' => $form,
            'categories' => App::categories()->all(),
            'buildings' => App::buildings()->all(),
        ]);
    }

    private function renderEdit(array $item, ItemForm $form): void
    {
        view('items/edit', [
            'title' => 'Upravit inzerát',
            'noindex' => true,
            'item' => $item,
            'form' => $form,
            'categories' => App::categories()->all(),
            'buildings' => App::buildings()->all(),
            'breadcrumbs' => [['Úvod', '/'], ['Můj účet', '/ucet'], ['Upravit inzerát', null]],
        ]);
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
            'related' => App::items()->related($item, 3),
            'form' => $form,
            'sent' => $sent,
            'canManage' => ItemPolicy::canManage(auth()->user(), $item),
            'canContact' => ItemPolicy::canContact(auth()->user(), $item),
            'isGuest' => !auth()->check(),
        ]);
    }
}
