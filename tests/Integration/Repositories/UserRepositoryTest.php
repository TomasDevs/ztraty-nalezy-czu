<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\UserRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class UserRepositoryTest extends DatabaseTestCase
{
    use Fixtures;

    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository(self::$db);
    }

    public function testCreateHashesPasswordAndLowercasesEmail(): void
    {
        $id = $this->users->create('Jan Novák', 'Jan.Novak@Studenti.CZU.cz', 'tajne-heslo');

        $user = $this->users->find($id);

        $this->assertSame('jan.novak@studenti.czu.cz', $user['email']);
        $this->assertSame('Jan Novák', $user['name']);
        $this->assertSame('user', $user['role']);
        $this->assertFalse($user['is_blocked']);
        $this->assertNotSame('tajne-heslo', $user['password_hash']);
        $this->assertTrue(password_verify('tajne-heslo', $user['password_hash']));
    }

    public function testFindByEmailIgnoresCase(): void
    {
        $user = $this->createUser(['email' => 'someone@example.com']);

        $this->assertSame($user['id'], $this->users->findByEmail('SomeOne@Example.com')['id']);
        $this->assertNull($this->users->findByEmail('nobody@example.com'));
        $this->assertNull($this->users->find(999999));
    }

    public function testAllIncludesItemCountsNewestFirst(): void
    {
        self::$db->exec('DELETE FROM items');
        self::$db->exec('DELETE FROM users');
        $first = $this->createUser();
        $second = $this->createUser();
        $this->createItem(['user_id' => $first['id']]);
        $this->createItem(['user_id' => $first['id']]);

        $all = $this->users->all();

        $this->assertSame([$second['id'], $first['id']], array_column($all, 'id'));
        $this->assertSame([0, 2], array_column($all, 'items_count'));
    }

    public function testSetBlocked(): void
    {
        $user = $this->createUser();

        $this->users->setBlocked($user['id'], true);
        $this->assertTrue($this->users->find($user['id'])['is_blocked']);

        $this->users->setBlocked($user['id'], false);
        $this->assertFalse($this->users->find($user['id'])['is_blocked']);
    }

    public function testSetRoleAcceptsOnlyKnownRoles(): void
    {
        $user = $this->createUser();

        $this->users->setRole($user['id'], 'admin');
        $this->assertSame('admin', $this->users->find($user['id'])['role']);

        $this->expectException(\InvalidArgumentException::class);
        $this->users->setRole($user['id'], 'superuser');
    }
}
