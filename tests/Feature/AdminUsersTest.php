<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class AdminUsersTest extends FeatureTestCase
{
    private array $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser(['role' => 'admin', 'name' => 'Správce']);
        $this->actingAs($this->admin);
    }

    public function testListsUsersWithRoleAndItemCount(): void
    {
        $user = $this->createUser(['name' => 'Jan Novák', 'email' => 'jan@example.com']);
        $this->createItem(['user_id' => $user['id']]);

        $this->get('/admin/uzivatele')
            ->assertOk()
            ->assertSee('Jan Novák')
            ->assertSee('jan@example.com')
            ->assertSee('Správce')
            ->assertSee('<meta name="robots" content="noindex">');
    }

    public function testBlockedUserCannotLogIn(): void
    {
        $user = $this->createUser();

        $this->post("/admin/uzivatele/{$user['id']}/blokace", ['blocked' => '1'])->assertRedirect('/admin/uzivatele');

        $this->assertTrue(App::users()->find($user['id'])['is_blocked']);

        $this->asGuest();
        $this->post('/prihlaseni', ['email' => $user['email'], 'password' => 'password123'])
            ->assertStatus(422)
            ->assertSee('Účet je zablokovaný.');
    }

    public function testUnblockingRestoresAccess(): void
    {
        $user = $this->createUser(['is_blocked' => 1]);

        $this->post("/admin/uzivatele/{$user['id']}/blokace", ['blocked' => '0']);

        $this->assertFalse(App::users()->find($user['id'])['is_blocked']);
    }

    public function testAdminCannotBlockThemselves(): void
    {
        $this->post("/admin/uzivatele/{$this->admin['id']}/blokace", ['blocked' => '1'])->assertRedirect('/admin/uzivatele');

        $this->assertFalse(App::users()->find($this->admin['id'])['is_blocked']);
        $this->get('/admin/uzivatele')->assertSee('Vlastní účet nejde zablokovat.');
    }

    public function testRoleCanBeChanged(): void
    {
        $user = $this->createUser();

        $this->post("/admin/uzivatele/{$user['id']}/role", ['role' => 'admin'])->assertRedirect('/admin/uzivatele');
        $this->assertSame('admin', App::users()->find($user['id'])['role']);

        $this->post("/admin/uzivatele/{$user['id']}/role", ['role' => 'user']);
        $this->assertSame('user', App::users()->find($user['id'])['role']);
    }

    public function testAdminCannotRemoveOwnRole(): void
    {
        $this->post("/admin/uzivatele/{$this->admin['id']}/role", ['role' => 'user'])->assertRedirect('/admin/uzivatele');

        $this->assertSame('admin', App::users()->find($this->admin['id'])['role']);
        $this->get('/admin/uzivatele')->assertSee('Vlastní roli nejde změnit.');
    }

    public function testUnknownRoleIsRejected(): void
    {
        $user = $this->createUser();

        $this->post("/admin/uzivatele/{$user['id']}/role", ['role' => 'superuser']);

        $this->assertSame('user', App::users()->find($user['id'])['role']);
        $this->get('/admin/uzivatele')->assertSee('Neplatná role.');
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->post('/admin/uzivatele/999999/blokace', ['blocked' => '1'])->assertStatus(404);
        $this->post('/admin/uzivatele/999999/role', ['role' => 'admin'])->assertStatus(404);
    }
}
