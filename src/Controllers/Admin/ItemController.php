<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\App;

final class ItemController extends Controller
{
    public const STATUSES = ['active' => 'Aktivní', 'returned' => 'Vráceno', 'hidden' => 'Skryto'];

    private const STATUS_MESSAGES = [
        'active' => 'Inzerát je zobrazený.',
        'returned' => 'Inzerát je označený jako vrácený.',
        'hidden' => 'Inzerát je skrytý.',
    ];

    public function index(): void
    {
        $this->requireAdmin();
        $status = $this->statusFilter();

        view('admin/items', [
            'title' => 'Administrace: inzeráty',
            'noindex' => true,
            'items' => App::items()->forAdmin($status),
            'status' => $status,
            'statuses' => self::STATUSES,
        ]);
    }

    public function setStatus(string $id): void
    {
        $this->requireAdmin();
        $item = App::items()->find((int) $id) ?? abort(404);
        $status = $_POST['status'] ?? null;

        if (is_string($status) && isset(self::STATUSES[$status])) {
            App::items()->setStatus($item['id'], $status);
            App::session()->flash('success', self::STATUS_MESSAGES[$status]);
        } else {
            App::session()->flash('error', 'Neplatný stav inzerátu.');
        }

        $this->backToList();
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();

        $this->deleteItem(App::items()->find((int) $id) ?? abort(404));
        App::session()->flash('success', 'Inzerát je smazaný.');

        $this->backToList();
    }

    private function statusFilter(): ?string
    {
        $status = $_POST['stav'] ?? $_GET['stav'] ?? null;

        return is_string($status) && isset(self::STATUSES[$status]) ? $status : null;
    }

    private function backToList(): never
    {
        $status = $this->statusFilter();

        redirect('/admin/inzeraty' . ($status ? '?' . http_build_query(['stav' => $status]) : ''), 303);
    }
}
