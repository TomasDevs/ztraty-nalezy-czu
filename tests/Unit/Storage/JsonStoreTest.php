<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use App\Storage\JsonStore;
use PHPUnit\Framework\TestCase;

final class JsonStoreTest extends TestCase
{
    private string $dir;
    private JsonStore $store;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/json-store-' . uniqid();
        $this->store = new JsonStore($this->dir);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function testEmptyTable(): void
    {
        $this->assertSame([], $this->store->all('users'));
        $this->assertNull($this->store->find('users', 1));
    }

    public function testInsertAssignsIncreasingIds(): void
    {
        $first = $this->store->insert('users', ['name' => 'Jana']);
        $second = $this->store->insert('users', ['name' => 'Petr']);

        $this->assertSame([1, 2], [$first, $second]);
        $this->assertSame(['id' => 2, 'name' => 'Petr'], $this->store->find('users', 2));
        $this->assertSame([1, 2], array_keys($this->store->all('users')));
    }

    public function testExplicitIdIsKeptAndNextIdsFollowIt(): void
    {
        $this->assertSame(10, $this->store->insert('items', ['id' => 10, 'title' => 'Klíče']));
        $this->assertSame(11, $this->store->insert('items', ['title' => 'Deštník']));
    }

    public function testIdsAreNotReusedAfterDelete(): void
    {
        $this->store->insert('items', ['title' => 'A']);
        $id = $this->store->insert('items', ['title' => 'B']);
        $this->store->delete('items', fn (array $row) => $row['id'] === $id);

        $this->assertSame(3, $this->store->insert('items', ['title' => 'C']));
    }

    public function testUpdateMergesChangesAndKeepsId(): void
    {
        $id = $this->store->insert('users', ['name' => 'Jana', 'role' => 'user']);

        $this->store->update('users', $id, ['role' => 'admin', 'id' => 99]);
        $this->store->update('users', 404, ['role' => 'admin']);

        $this->assertSame(['id' => $id, 'name' => 'Jana', 'role' => 'admin'], $this->store->find('users', $id));
        $this->assertCount(1, $this->store->all('users'));
    }

    public function testDeleteReturnsNumberOfRemovedRows(): void
    {
        $this->store->insert('messages', ['item_id' => 1]);
        $this->store->insert('messages', ['item_id' => 2]);
        $this->store->insert('messages', ['item_id' => 1]);

        $this->assertSame(2, $this->store->delete('messages', fn (array $row) => $row['item_id'] === 1));
        $this->assertSame([2], array_column($this->store->all('messages'), 'item_id'));
    }

    public function testDataIsPersistedAsReadableJson(): void
    {
        $this->store->insert('categories', ['name' => 'Klíče a peněženky']);

        $this->assertSame('Klíče a peněženky', (new JsonStore($this->dir))->find('categories', 1)['name']);
        $this->assertStringContainsString('"name": "Klíče a peněženky"', (string) file_get_contents($this->dir . '/categories.json'));
    }

    public function testTruncateEmptiesTablesAndResetsIds(): void
    {
        $this->store->insert('users', ['name' => 'Jana']);
        $this->store->insert('items', ['title' => 'A']);

        $this->store->truncate('users', 'items');

        $this->assertSame([], $this->store->all('users'));
        $this->assertSame(1, $this->store->insert('items', ['title' => 'B']));
    }

    public function testRejectsUnsafeTableNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->store->all('../secret');
    }
}
