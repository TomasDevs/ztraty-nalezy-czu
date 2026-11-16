<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\ItemQuery;
use App\Repositories\ItemRepository;
use App\Repositories\ItemRows;
use App\Storage\JsonStore;

/**
 * Same results as PdoItemRepository: joins, filters and ordering are done in PHP.
 * The photo path is kept on the item row instead of a separate table.
 */
final class JsonItemRepository implements ItemRepository
{
    public function __construct(private readonly JsonStore $store)
    {
    }

    public function search(ItemQuery $query): array
    {
        $q = $query->q !== null ? fold($query->q) : null;

        $rows = array_filter($this->rows(), fn (array $row) => $row['status'] !== 'hidden'
            && ($query->type === null || $row['type'] === $query->type)
            && ($query->category === null || $row['category']['slug'] === $query->category)
            && ($query->building === null || $row['building']['slug'] === $query->building)
            && ($q === null || str_contains($row['search_text'], $q)));

        return [
            'items' => array_map($this->hydrate(...), array_slice($rows, $query->offset(), ItemQuery::PER_PAGE)),
            'total' => count($rows),
            'markers' => array_map(ItemRows::marker(...), array_values($rows)),
        ];
    }

    public function find(int $id): ?array
    {
        foreach ($this->rows() as $row) {
            if ($row['id'] === $id) {
                return $this->hydrate($row);
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->visible();
    }

    public function latest(int $limit): array
    {
        return array_slice($this->visible(), 0, $limit);
    }

    public function related(array $item, int $limit): array
    {
        $buildingId = $item['building']['id'];
        $categoryId = $item['category']['id'];
        $score = fn (array $row) => ($row['building']['id'] === $buildingId ? 2 : 0) + ($row['category']['id'] === $categoryId ? 1 : 0);

        $rows = array_filter($this->rows(), fn (array $row) => $row['id'] !== $item['id']
            && $row['status'] === 'active'
            && $score($row) > 0);

        // rows() is already newest first and usort keeps that order for equal scores
        usort($rows, fn (array $a, array $b) => $score($b) <=> $score($a));

        return array_map($this->hydrate(...), array_slice($rows, 0, $limit));
    }

    public function stats(): array
    {
        $stats = ['found' => 0, 'lost' => 0, 'returned' => 0];

        foreach ($this->store->all('items') as $row) {
            if ($row['status'] !== 'hidden') {
                $stats[$row['status'] === 'returned' ? 'returned' : $row['type']]++;
            }
        }

        return $stats;
    }

    public function forUser(int $userId): array
    {
        return array_values(array_map(
            $this->hydrate(...),
            array_filter($this->rows(), fn (array $row) => $row['user_id'] === $userId)
        ));
    }

    public function forAdmin(?string $status = null): array
    {
        $emails = array_column($this->store->all('users'), 'email', 'id');

        $rows = array_filter($this->rows(), fn (array $row) => isset($emails[$row['user_id']])
            && ($status === null || $row['status'] === $status));

        return array_values(array_map(
            fn (array $row) => [...$this->hydrate($row), 'author_email' => $emails[$row['user_id']]],
            $rows
        ));
    }

    public function create(array $data): int
    {
        $row = $this->typed([
            ...array_intersect_key($data, array_flip([...ItemRows::WRITABLE, 'user_id', 'id'])),
            'status' => $data['status'] ?? 'active',
            ...ItemRows::derived($data),
        ]);

        return $this->store->insert('items', [
            ...$row,
            'photo' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $this->store->update('items', $id, [
            ...$this->typed([...array_intersect_key($data, array_flip(ItemRows::WRITABLE)), ...ItemRows::derived($data)]),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->store->update('items', $id, ['status' => $status]);
    }

    public function setPhoto(int $id, ?string $path): void
    {
        $this->store->update('items', $id, ['photo' => $path]);
    }

    public function delete(int $id): void
    {
        $this->store->delete('items', fn (array $row) => $row['id'] === $id);
        $this->store->delete('messages', fn (array $row) => $row['item_id'] === $id);
    }

    /**
     * Items joined with their category and building, newest first.
     * Like an inner join, items with a missing category or building are left out.
     *
     * @return array<int, array> keyed in order, not by id
     */
    private function rows(): array
    {
        $categories = $this->store->all('categories');
        $buildings = $this->store->all('buildings');
        $rows = [];

        foreach ($this->store->all('items') as $row) {
            if (isset($categories[$row['category_id']], $buildings[$row['building_id']])) {
                $rows[] = [...$row, 'category' => $categories[$row['category_id']], 'building' => $buildings[$row['building_id']]];
            }
        }

        usort($rows, fn (array $a, array $b) => [$b['date'], $b['id']] <=> [$a['date'], $a['id']]);

        return $rows;
    }

    private function visible(): array
    {
        return array_values(array_map(
            $this->hydrate(...),
            array_filter($this->rows(), fn (array $row) => $row['status'] !== 'hidden')
        ));
    }

    /**
     * Casts form values the way the database columns would.
     */
    private function typed(array $row): array
    {
        foreach (['id', 'user_id', 'category_id', 'building_id'] as $column) {
            if (isset($row[$column])) {
                $row[$column] = (int) $row[$column];
            }
        }

        foreach (['lat', 'lng'] as $column) {
            if (isset($row[$column])) {
                $row[$column] = round((float) $row[$column], 6);
            }
        }

        return $row;
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
            'photo' => $row['photo'] ?? null,
            'category' => ['id' => (int) $row['category']['id'], 'slug' => $row['category']['slug'], 'name' => $row['category']['name']],
            'building' => [
                'id' => (int) $row['building']['id'],
                'slug' => $row['building']['slug'],
                'name' => $row['building']['name'],
                'short' => $row['building']['short'],
                'lat' => (float) $row['building']['lat'],
                'lng' => (float) $row['building']['lng'],
            ],
        ];
    }
}
