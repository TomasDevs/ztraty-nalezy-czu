<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MessageRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $itemId, int $senderId, string $body): int
    {
        $this->db
            ->prepare('INSERT INTO messages (item_id, sender_id, body) VALUES (?, ?, ?)')
            ->execute([$itemId, $senderId, $body]);

        return (int) $this->db->lastInsertId();
    }

    public function countForItem(int $itemId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE item_id = ?');
        $statement->execute([$itemId]);

        return (int) $statement->fetchColumn();
    }
}
