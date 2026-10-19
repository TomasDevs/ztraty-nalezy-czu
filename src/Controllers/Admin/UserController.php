<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\App;
use App\Repositories\UserRepository;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();

        view('admin/users', [
            'title' => 'Administrace: uživatelé',
            'noindex' => true,
            'users' => App::users()->all(),
            'currentId' => auth()->user()['id'],
        ]);
    }

    public function block(string $id): void
    {
        $admin = $this->requireAdmin();
        $user = $this->findUser($id);
        $blocked = ($_POST['blocked'] ?? '') === '1';

        if ($user['id'] === $admin['id']) {
            $this->back('error', 'Vlastní účet nejde zablokovat.');
        }

        App::users()->setBlocked($user['id'], $blocked);

        $this->back('success', $blocked ? 'Účet je zablokovaný.' : 'Účet je odblokovaný.');
    }

    public function changeRole(string $id): void
    {
        $admin = $this->requireAdmin();
        $user = $this->findUser($id);
        $role = $_POST['role'] ?? null;

        if (!is_string($role) || !in_array($role, UserRepository::ROLES, true)) {
            $this->back('error', 'Neplatná role.');
        }

        if ($user['id'] === $admin['id']) {
            $this->back('error', 'Vlastní roli nejde změnit.');
        }

        App::users()->setRole($user['id'], $role);

        $this->back('success', 'Role je změněná.');
    }

    private function findUser(string $id): array
    {
        return App::users()->find((int) $id) ?? abort(404);
    }

    private function back(string $type, string $message): never
    {
        App::session()->flash($type, $message);

        redirect('/admin/uzivatele', 303);
    }
}
