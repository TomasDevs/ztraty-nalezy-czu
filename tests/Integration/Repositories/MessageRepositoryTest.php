<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\MessageRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class MessageRepositoryTest extends DatabaseTestCase
{
    use Fixtures;

    public function testCreateAndCount(): void
    {
        $messages = new MessageRepository(self::$db);
        $item = $this->createItem();
        $sender = $this->createUser();

        $this->assertSame(0, $messages->countForItem($item['id']));

        $id = $messages->create($item['id'], $sender['id'], 'Mám vaše klíče.');

        $this->assertGreaterThan(0, $id);
        $this->assertSame(1, $messages->countForItem($item['id']));
        $this->assertSame('Mám vaše klíče.', self::$db->query("SELECT body FROM messages WHERE id = {$id}")->fetchColumn());
    }
}
