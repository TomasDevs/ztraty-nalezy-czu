<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Lookup tables managed in the administration (categories, buildings).
 */
interface CatalogRepository
{
    public function all(): array;

    public function find(int $id): ?array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function itemCount(int $id): int;

    public function delete(int $id): void;
}
