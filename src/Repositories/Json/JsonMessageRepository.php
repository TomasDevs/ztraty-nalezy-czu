<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\MessageRepository;
use App\Storage\JsonStore;

final class JsonMessageRepository implements MessageRepository
{
    public function __construct(private readonly JsonStore $store)
    {
    }

    public function create(int $itemId, int $senderId, string $body): int
    {
        return $this->store->insert('messages', [
            'item_id' => $itemId,
            'sender_id' => $senderId,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function countForItem(int $itemId): int
    {
        return count(array_filter($this->store->all('messages'), fn (array $row) => $row['item_id'] === $itemId));
    }
}
