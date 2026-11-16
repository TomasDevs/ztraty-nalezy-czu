<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\CatalogRepository;
use App\Storage\JsonStore;

/**
 * Categories and buildings: rows sorted by name in Czech order.
 */
abstract class JsonCatalogRepository implements CatalogRepository
{
    public function __construct(protected readonly JsonStore $store)
    {
    }

    abstract protected function table(): string;

    /**
     * Column in the items table that points to this catalog.
     */
    abstract protected function itemColumn(): string;

    /**
     * Keeps only the catalog columns, with their types.
     */
    abstract protected function hydrate(array $row): array;

    public function all(): array
    {
        $rows = array_map($this->hydrate(...), array_values($this->store->all($this->table())));
        usort($rows, fn (array $a, array $b) => czech_sort_key($a['name']) <=> czech_sort_key($b['name']));

        return $rows;
    }

    public function find(int $id): ?array
    {
        $row = $this->store->find($this->table(), $id);

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->store->all($this->table()) as $row) {
            if ($row['slug'] === $slug) {
                return $this->hydrate($row);
            }
        }

        return null;
    }

    public function create(array $data): int
    {
        return $this->store->insert($this->table(), array_diff_key($this->hydrate(['id' => 0, ...$data]), ['id' => true]));
    }

    public function update(int $id, array $data): void
    {
        $this->store->update($this->table(), $id, $this->hydrate(['id' => $id, ...$data]));
    }

    public function itemCount(int $id): int
    {
        return count(array_filter($this->store->all('items'), fn (array $row) => $row[$this->itemColumn()] === $id));
    }

    public function delete(int $id): void
    {
        $this->store->delete($this->table(), fn (array $row) => $row['id'] === $id);
    }
}
