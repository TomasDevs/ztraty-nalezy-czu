<?php

declare(strict_types=1);

namespace App\Repositories;

interface MessageRepository
{
    public function create(int $itemId, int $senderId, string $body): int;

    public function countForItem(int $itemId): int;
}
