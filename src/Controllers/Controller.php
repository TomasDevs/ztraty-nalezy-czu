<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

abstract class Controller
{
    protected function requireUser(): array
    {
        return auth()->user() ?? redirect('/prihlaseni?zpet=' . rawurlencode(current_uri()));
    }

    protected function deleteItem(array $item): void
    {
        App::photos()->delete($item['photo']);
        App::items()->delete($item['id']);
    }

    protected function requireAdmin(): array
    {
        $user = $this->requireUser();

        return $user['role'] === 'admin' ? $user : abort(403);
    }
}
