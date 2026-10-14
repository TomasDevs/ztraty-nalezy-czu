<?php

declare(strict_types=1);

namespace App\Repositories;

interface ItemRepository
{
    /**
     * @return array{items: array[], total: int, markers: array[]}
     */
    public function search(ItemQuery $query): array;

    public function find(int $id): ?array;

    public function latest(int $limit): array;

    /**
     * @return array{found: int, lost: int, returned: int}
     */
    public function stats(): array;

    public function categories(): array;

    public function buildings(): array;
}
