<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

final class AccountController extends Controller
{
    public function index(): void
    {
        $user = $this->requireUser();

        view('account/index', [
            'title' => 'Můj účet',
            'noindex' => true,
            'user' => $user,
            'items' => App::items()->forUser($user['id']),
            'breadcrumbs' => [['Úvod', '/'], ['Můj účet', null]],
        ]);
    }
}
