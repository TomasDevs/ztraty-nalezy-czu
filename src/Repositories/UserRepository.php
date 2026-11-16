<?php

declare(strict_types=1);

namespace App\Repositories;

interface UserRepository
{
    public const ROLES = ['user', 'admin'];

    /**
     * Stores a lowercased e-mail and a password hash, never the password itself.
     */
    public function create(string $name, string $email, string $password): int;

    public function find(int $id): ?array;

    public function findByEmail(string $email): ?array;

    /**
     * Newest first, with the number of items each user posted.
     */
    public function all(): array;

    public function setBlocked(int $id, bool $blocked): void;

    /**
     * @throws \InvalidArgumentException for a role outside ROLES
     */
    public function setRole(int $id, string $role): void;
}
