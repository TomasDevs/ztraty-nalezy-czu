<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository implements CatalogRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, slug, name FROM categories ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('id', $id);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetchOne('slug', $slug);
    }

    public function create(array $data): int
    {
        $this->db->prepare('INSERT INTO categories (slug, name) VALUES (?, ?)')->execute([$data['slug'], $data['name']]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->prepare('UPDATE categories SET slug = ?, name = ? WHERE id = ?')->execute([$data['slug'], $data['name'], $id]);
    }

    public function itemCount(int $id): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM items WHERE category_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    private function fetchOne(string $column, int|string $value): ?array
    {
        $statement = $this->db->prepare("SELECT id, slug, name FROM categories WHERE {$column} = ?");
        $statement->execute([$value]);

        return $statement->fetch() ?: null;
    }
}
