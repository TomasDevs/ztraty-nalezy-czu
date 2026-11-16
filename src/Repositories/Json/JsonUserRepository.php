<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\UserRepository;
use App\Storage\JsonStore;

final class JsonUserRepository implements UserRepository
{
    public function __construct(private readonly JsonStore $store)
    {
    }

    public function create(string $name, string $email, string $password): int
    {
        return $this->store->insert('users', [
            'name' => $name,
            'email' => mb_strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'is_blocked' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function find(int $id): ?array
    {
        $row = $this->store->find('users', $id);

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $email = mb_strtolower($email);

        foreach ($this->store->all('users') as $row) {
            if ($row['email'] === $email) {
                return $this->hydrate($row);
            }
        }

        return null;
    }

    public function all(): array
    {
        $counts = array_count_values(array_column($this->store->all('items'), 'user_id'));
        $users = array_reverse($this->store->all('users'));

        return array_map(fn (array $row) => [...$this->hydrate($row), 'items_count' => $counts[$row['id']] ?? 0], $users);
    }

    public function setBlocked(int $id, bool $blocked): void
    {
        $this->store->update('users', $id, ['is_blocked' => $blocked]);
    }

    public function setRole(int $id, string $role): void
    {
        if (!in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException("Unknown role {$role}");
        }

        $this->store->update('users', $id, ['role' => $role]);
    }

    private function hydrate(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'password_hash' => $row['password_hash'],
            'role' => $row['role'] ?? 'user',
            'is_blocked' => (bool) ($row['is_blocked'] ?? false),
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}
