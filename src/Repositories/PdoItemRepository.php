<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PdoItemRepository implements ItemRepository
{
    private const WRITABLE = ['category_id', 'building_id', 'type', 'title', 'description', 'location', 'lat', 'lng', 'date'];

    public function __construct(private readonly PDO $db)
    {
    }

    public function search(ItemQuery $query): array
    {
        [$where, $params] = $this->filters($query);

        $markers = $this->run(
            'SELECT i.id, i.slug, i.title, i.type, i.lat, i.lng ' . self::FROM . " WHERE {$where} " . self::ORDER,
            $params
        );
        $page = $this->fetchItems(
            "{$this->select()} WHERE {$where} " . self::ORDER . sprintf(' LIMIT %d OFFSET %d', ItemQuery::PER_PAGE, $query->offset()),
            $params
        );

        return [
            'items' => $page,
            'total' => count($markers),
            'markers' => array_map($this->marker(...), $markers),
        ];
    }

    public function find(int $id): ?array
    {
        return $this->fetchItems("{$this->select()} WHERE i.id = ?", [$id])[0] ?? null;
    }

    public function all(): array
    {
        return $this->fetchItems("{$this->select()} WHERE i.status <> 'hidden' " . self::ORDER);
    }

    public function latest(int $limit): array
    {
        return $this->fetchItems("{$this->select()} WHERE i.status <> 'hidden' " . self::ORDER . sprintf(' LIMIT %d', $limit));
    }

    public function related(array $item, int $limit): array
    {
        $score = '(i.building_id = ?) * 2 + (i.category_id = ?)';

        return $this->fetchItems(
            "{$this->select()} WHERE i.id <> ? AND i.status = 'active' AND (i.building_id = ? OR i.category_id = ?) "
            . "ORDER BY {$score} DESC, i.date DESC, i.id DESC" . sprintf(' LIMIT %d', $limit),
            [$item['id'], $item['building']['id'], $item['category']['id'], $item['building']['id'], $item['category']['id']]
        );
    }

    public function stats(): array
    {
        $stats = ['found' => 0, 'lost' => 0, 'returned' => 0];

        $rows = $this->run("SELECT type, status, COUNT(*) AS total FROM items WHERE status <> 'hidden' GROUP BY type, status");

        foreach ($rows as $row) {
            $stats[$row['status'] === 'returned' ? 'returned' : $row['type']] += (int) $row['total'];
        }

        return $stats;
    }

    public function forUser(int $userId): array
    {
        return $this->fetchItems("{$this->select()} WHERE i.user_id = ? " . self::ORDER, [$userId]);
    }

    public function forAdmin(?string $status = null): array
    {
        return $this->fetchItems(
            $this->select(withAuthor: true) . ($status !== null ? ' WHERE i.status = ? ' : ' ') . self::ORDER,
            $status !== null ? [$status] : []
        );
    }

    /**
     * @param array{user_id: int, category_id: int, building_id: int, type: string, title: string, description: string, location: string, lat: float, lng: float, date: string, status?: string, id?: int} $data
     */
    public function create(array $data): int
    {
        $row = [
            ...array_intersect_key($data, array_flip([...self::WRITABLE, 'user_id', 'status', 'id'])),
            ...$this->derived($data),
        ];

        $columns = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        $this->run("INSERT INTO items ({$columns}) VALUES ({$placeholders})", array_values($row));

        return (int) ($data['id'] ?? $this->db->lastInsertId());
    }

    public function update(int $id, array $data): void
    {
        $row = [...array_intersect_key($data, array_flip(self::WRITABLE)), ...$this->derived($data)];
        $assignments = implode(', ', array_map(fn (string $column) => "{$column} = ?", array_keys($row)));

        $this->run("UPDATE items SET {$assignments} WHERE id = ?", [...array_values($row), $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->run('UPDATE items SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function setPhoto(int $id, ?string $path): void
    {
        if ($path === null) {
            $this->run('DELETE FROM photos WHERE item_id = ?', [$id]);

            return;
        }

        $this->run('INSERT INTO photos (item_id, path) VALUES (?, ?) ON DUPLICATE KEY UPDATE path = VALUES(path)', [$id, $path]);
    }

    public function delete(int $id): void
    {
        $this->run('DELETE FROM items WHERE id = ?', [$id]);
    }

    private const ORDER = 'ORDER BY i.date DESC, i.id DESC';
    private const FROM = 'FROM items i JOIN categories c ON c.id = i.category_id JOIN buildings b ON b.id = i.building_id';

    private function select(bool $withAuthor = false): string
    {
        return 'SELECT i.*, c.slug AS category_slug, c.name AS category_name, '
            . 'b.slug AS building_slug, b.name AS building_name, b.short AS building_short, b.lat AS building_lat, b.lng AS building_lng, '
            . 'p.path AS photo' . ($withAuthor ? ', u.email AS author_email' : '')
            . ' ' . self::FROM . ' LEFT JOIN photos p ON p.item_id = i.id' . ($withAuthor ? ' JOIN users u ON u.id = i.user_id' : '');
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function filters(ItemQuery $query): array
    {
        $where = ["i.status <> 'hidden'"];
        $params = [];

        foreach (['type' => 'i.type', 'category' => 'c.slug', 'building' => 'b.slug'] as $property => $column) {
            if ($query->$property !== null) {
                $where[] = "{$column} = ?";
                $params[] = $query->$property;
            }
        }

        if ($query->q !== null) {
            $where[] = "i.search_text LIKE ? ESCAPE '|'";
            $params[] = '%' . strtr(fold($query->q), ['|' => '||', '%' => '|%', '_' => '|_']) . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    private function derived(array $data): array
    {
        $slug = trim(substr(slugify($data['title']), 0, 160), '-');

        return [
            'slug' => $slug !== '' ? $slug : 'inzerat',
            'search_text' => fold("{$data['title']} {$data['description']} {$data['location']}"),
        ];
    }

    private function fetchItems(string $sql, array $params = []): array
    {
        return array_map($this->hydrate(...), $this->run($sql, $params));
    }

    private function run(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->columnCount() > 0 ? $statement->fetchAll() : [];
    }

    private function hydrate(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'type' => $row['type'],
            'status' => $row['status'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'location' => $row['location'],
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
            'date' => $row['date'],
            'photo' => $row['photo'],
            'category' => ['id' => (int) $row['category_id'], 'slug' => $row['category_slug'], 'name' => $row['category_name']],
            'building' => [
                'id' => (int) $row['building_id'],
                'slug' => $row['building_slug'],
                'name' => $row['building_name'],
                'short' => $row['building_short'],
                'lat' => (float) $row['building_lat'],
                'lng' => (float) $row['building_lng'],
            ],
            ...(isset($row['author_email']) ? ['author_email' => $row['author_email']] : []),
        ];
    }

    private function marker(array $row): array
    {
        return [
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
            'title' => $row['title'],
            'type' => $row['type'],
            'url' => item_url($row),
        ];
    }
}
