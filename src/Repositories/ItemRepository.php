<?php

declare(strict_types=1);

namespace App\Repositories;

interface ItemRepository
{
    /**
     * Hidden items are never returned by the public listing methods.
     *
     * @return array{items: array[], total: int, markers: array[]}
     */
    public function search(ItemQuery $query): array;

    /**
     * Finds an item in any status, visibility is decided by the caller.
     */
    public function find(int $id): ?array;

    /**
     * All visible items, newest first.
     */
    public function all(): array;

    public function latest(int $limit): array;

    /**
     * Active items from the same building first, then from the same category.
     */
    public function related(array $item, int $limit): array;

    /**
     * @return array{found: int, lost: int, returned: int}
     */
    public function stats(): array;

    /**
     * Own items in every status.
     */
    public function forUser(int $userId): array;

    /**
     * Items in every status with the author's e-mail.
     */
    public function forAdmin(?string $status = null): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function setStatus(int $id, string $status): void;

    public function setPhoto(int $id, ?string $path): void;

    public function delete(int $id): void;
}
