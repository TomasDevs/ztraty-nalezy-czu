<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Mock data source used until the database is ready.
 */
final class JsonItemRepository implements ItemRepository
{
    private array $categories;
    private array $buildings;
    private array $items;

    public function __construct(string $dir = ROOT_PATH . '/database/mock')
    {
        $this->categories = $this->indexBySlug($this->load("{$dir}/categories.json"));
        $this->buildings = $this->indexBySlug($this->load("{$dir}/buildings.json"));
        $this->items = array_map($this->hydrate(...), $this->load("{$dir}/items.json"));

        usort($this->items, fn (array $a, array $b) => strcmp($b['date'], $a['date']));
    }

    public function search(ItemQuery $query): array
    {
        $matches = array_values(array_filter($this->items, fn (array $item) => $this->matches($item, $query)));

        return [
            'items' => array_slice($matches, $query->offset(), ItemQuery::PER_PAGE),
            'total' => count($matches),
            'markers' => array_map($this->marker(...), $matches),
        ];
    }

    public function find(int $id): ?array
    {
        foreach ($this->items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    public function latest(int $limit): array
    {
        return array_slice($this->items, 0, $limit);
    }

    public function categories(): array
    {
        return array_values($this->categories);
    }

    public function buildings(): array
    {
        return array_values($this->buildings);
    }

    private function matches(array $item, ItemQuery $query): bool
    {
        if ($query->type && $item['type'] !== $query->type) {
            return false;
        }

        if ($query->category && $item['category']['slug'] !== $query->category) {
            return false;
        }

        if ($query->building && $item['building']['slug'] !== $query->building) {
            return false;
        }

        if ($query->q) {
            $haystack = mb_strtolower("{$item['title']} {$item['description']} {$item['location']}");

            return str_contains($haystack, mb_strtolower($query->q));
        }

        return true;
    }

    private function hydrate(array $item): array
    {
        return [
            ...$item,
            'category' => $this->categories[$item['category']],
            'building' => $this->buildings[$item['building']],
        ];
    }

    private function marker(array $item): array
    {
        return [
            'lat' => $item['lat'],
            'lng' => $item['lng'],
            'title' => $item['title'],
            'type' => $item['type'],
            'url' => item_url($item),
        ];
    }

    private function load(string $file): array
    {
        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private function indexBySlug(array $rows): array
    {
        return array_column($rows, null, 'slug');
    }
}
