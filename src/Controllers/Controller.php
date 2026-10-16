<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class Controller
{
    protected function requireUser(): array
    {
        return auth()->user() ?? redirect('/prihlaseni?zpet=' . rawurlencode(current_uri()));
    }

    protected function requireAdmin(): array
    {
        $user = $this->requireUser();

        return $user['role'] === 'admin' ? $user : abort(403);
    }
}
