<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class AdminItemsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser(['role' => 'admin']));
    }

    public function testListsItemsInEveryStatusWithAuthor(): void
    {
        $author = $this->createUser(['email' => 'autor@example.com']);
        $this->createItem(['user_id' => $author['id'], 'title' => 'Aktivní klíče']);
        $this->createItem(['title' => 'Skrytý batoh', 'status' => 'hidden']);
        $this->createItem(['title' => 'Vrácená peněženka', 'status' => 'returned']);

        $this->get('/admin/inzeraty')
            ->assertOk()
            ->assertSee('Aktivní klíče')
            ->assertSee('Skrytý batoh')
            ->assertSee('Vrácená peněženka')
            ->assertSee('autor@example.com')
            ->assertSee('<meta name="robots" content="noindex">');
    }

    public function testFiltersByStatus(): void
    {
        $this->createItem(['title' => 'Aktivní klíče']);
        $this->createItem(['title' => 'Skrytý batoh', 'status' => 'hidden']);

        $this->get('/admin/inzeraty', ['stav' => 'hidden'])
            ->assertSee('Skrytý batoh')
            ->assertDontSee('Aktivní klíče');
    }

    public function testDashboardShowsTheSameListing(): void
    {
        $this->createItem(['title' => 'Aktivní klíče']);

        $this->get('/admin')->assertOk()->assertSee('Aktivní klíče');
    }

    public function testHidingRemovesItemFromPublicPages(): void
    {
        $item = $this->createItem(['title' => 'Aktivní klíče']);

        $this->post("/admin/inzeraty/{$item['id']}/stav", ['status' => 'hidden'])->assertRedirect('/admin/inzeraty');

        $this->assertSame('hidden', App::items()->find($item['id'])['status']);
        $this->get('/admin/inzeraty')->assertSee('Inzerát je skrytý.');
        $this->get('/inzeraty')->assertDontSee('Aktivní klíče');
        $this->get('/sitemap.xml')->assertDontSee("/inzerat/{$item['id']}/");
    }

    public function testShowingRestoresItem(): void
    {
        $item = $this->createItem(['title' => 'Skrytý batoh', 'status' => 'hidden']);

        $this->post("/admin/inzeraty/{$item['id']}/stav", ['status' => 'active']);

        $this->assertSame('active', App::items()->find($item['id'])['status']);
        $this->get('/inzeraty')->assertSee('Skrytý batoh');
    }

    public function testUnknownStatusIsRejected(): void
    {
        $item = $this->createItem();

        $this->post("/admin/inzeraty/{$item['id']}/stav", ['status' => 'banned'])->assertRedirect('/admin/inzeraty');

        $this->assertSame('active', App::items()->find($item['id'])['status']);
        $this->get('/admin/inzeraty')->assertSee('Neplatný stav inzerátu.');
    }

    public function testDeleteRemovesItem(): void
    {
        $item = $this->createItem();

        $this->post("/admin/inzeraty/{$item['id']}/smazat")->assertRedirect('/admin/inzeraty');

        $this->assertNull(App::items()->find($item['id']));
    }

    public function testUnknownItemIsNotFound(): void
    {
        $this->post('/admin/inzeraty/999999/stav', ['status' => 'hidden'])->assertStatus(404);
        $this->post('/admin/inzeraty/999999/smazat')->assertStatus(404);
    }

    public function testStatusFilterIsKeptAfterAction(): void
    {
        $item = $this->createItem();

        $this->post("/admin/inzeraty/{$item['id']}/stav", ['status' => 'hidden', 'stav' => 'active'])
            ->assertRedirect('/admin/inzeraty?stav=active');
    }
}
