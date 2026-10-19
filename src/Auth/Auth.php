<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Repositories\UserRepository;

final class Auth
{
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private readonly Session $session, private readonly UserRepository $users)
    {
    }

    public function attempt(string $email, string $password): LoginResult
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return LoginResult::Invalid;
        }

        if ($user['is_blocked']) {
            return LoginResult::Blocked;
        }

        $this->login($user);

        return LoginResult::Success;
    }

    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->session->set('user_id', $user['id']);
        $this->user = $user;
        $this->loaded = true;
    }

    public function logout(): void
    {
        $this->session->remove('user_id');
        $this->session->regenerate();
        $this->user = null;
        $this->loaded = true;
    }

    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }

        $this->loaded = true;
        $id = $this->session->get('user_id');

        if ($id === null) {
            return null;
        }

        $user = is_int($id) ? $this->users->find($id) : null;

        if ($user === null || $user['is_blocked']) {
            $this->logout();

            return null;
        }

        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? null) === 'admin';
    }
}
