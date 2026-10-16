<?php

declare(strict_types=1);

namespace Tests\Support;

trait Fixtures
{
    private static int $fixtureCounter = 0;

    protected function createUser(array $overrides = []): array
    {
        $n = ++self::$fixtureCounter;
        $user = [
            'name' => "User {$n}",
            'email' => "user{$n}-" . uniqid() . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'is_blocked' => 0,
            ...$overrides,
        ];

        $user['id'] = $this->insert('users', $user);

        return $user;
    }

    protected function createCategory(array $overrides = []): array
    {
        $n = ++self::$fixtureCounter;
        $category = ['slug' => "category-{$n}", 'name' => "Category {$n}", ...$overrides];
        $category['id'] = $this->insert('categories', $category);

        return $category;
    }

    protected function createBuilding(array $overrides = []): array
    {
        $n = ++self::$fixtureCounter;
        $building = [
            'slug' => "building-{$n}",
            'name' => "Building {$n}",
            'short' => "B{$n}",
            'lat' => 50.130000,
            'lng' => 14.373000,
            ...$overrides,
        ];
        $building['id'] = $this->insert('buildings', $building);

        return $building;
    }

    protected function createItem(array $overrides = []): array
    {
        $n = ++self::$fixtureCounter;
        $item = [
            'user_id' => $overrides['user_id'] ?? $this->createUser()['id'],
            'category_id' => $overrides['category_id'] ?? $this->createCategory()['id'],
            'building_id' => $overrides['building_id'] ?? $this->createBuilding()['id'],
            'type' => 'lost',
            'status' => 'active',
            'title' => "Item {$n}",
            'description' => 'Description of the item, long enough to be valid.',
            'location' => 'Somewhere on campus',
            'lat' => 50.130000,
            'lng' => 14.373000,
            'date' => '2026-10-01',
            ...$overrides,
        ];
        $item['slug'] = slugify($item['title']);
        $item['search_text'] = fold("{$item['title']} {$item['description']} {$item['location']}");
        $item['id'] = $this->insert('items', $item);

        return $item;
    }

    private function insert(string $table, array $row): int
    {
        $columns = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));

        $statement = self::$db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
        $statement->execute(array_values($row));

        return (int) self::$db->lastInsertId();
    }
}
