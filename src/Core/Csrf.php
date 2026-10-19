<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        if (!$this->session->has('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        return $this->session->get('csrf_token');
    }

    public function check(?string $token): bool
    {
        return $token !== null && hash_equals($this->token(), $token);
    }
}
