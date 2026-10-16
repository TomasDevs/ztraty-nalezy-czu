<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public const ROLES = ['user', 'admin'];

    public function __construct(private readonly PDO $db)
    {
    }

    public function create(string $name, string $email, string $password): int
    {
        $this->db
            ->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$name, mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT)]);

        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('id', $id);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('email', mb_strtolower($email));
    }

    /**
     * Newest first, with the number of items each user posted.
     */
    public function all(): array
    {
        $rows = $this->db->query(
            'SELECT u.*, COUNT(i.id) AS items_count FROM users u LEFT JOIN items i ON i.user_id = u.id '
            . 'GROUP BY u.id ORDER BY u.id DESC'
        )->fetchAll();

        return array_map(fn (array $row) => [...$this->hydrate($row), 'items_count' => (int) $row['items_count']], $rows);
    }

    public function setBlocked(int $id, bool $blocked): void
    {
        $this->db->prepare('UPDATE users SET is_blocked = ? WHERE id = ?')->execute([(int) $blocked, $id]);
    }

    public function setRole(int $id, string $role): void
    {
        if (!in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException("Unknown role {$role}");
        }

        $this->db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
    }

    private function fetchOne(string $column, int|string $value): ?array
    {
        $statement = $this->db->prepare("SELECT * FROM users WHERE {$column} = ?");
        $statement->execute([$value]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        return [...$row, 'id' => (int) $row['id'], 'is_blocked' => (bool) $row['is_blocked']];
    }
}
