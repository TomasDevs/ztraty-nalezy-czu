<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\FeatureTestCase;

final class AccountTest extends FeatureTestCase
{
    public function testGuestIsSentToLogin(): void
    {
        $this->get('/ucet')->assertRedirect('/prihlaseni?zpet=%2Fucet');
    }

    public function testShowsProfileAndOwnItemsInEveryStatus(): void
    {
        $user = $this->createUser(['name' => 'Jan Novák', 'email' => 'jan@example.com']);
        $this->createItem(['user_id' => $user['id'], 'title' => 'Aktivní klíče']);
        $this->createItem(['user_id' => $user['id'], 'title' => 'Vrácená peněženka', 'status' => 'returned']);
        $this->createItem(['user_id' => $user['id'], 'title' => 'Skrytý batoh', 'status' => 'hidden']);
        $this->createItem(['title' => 'Cizí věc']);
        $this->actingAs($user);

        $this->get('/ucet')
            ->assertOk()
            ->assertSee('Jan Novák')
            ->assertSee('jan@example.com')
            ->assertSee('Aktivní klíče')
            ->assertSee('Vrácená peněženka')
            ->assertSee('Skrytý batoh')
            ->assertSee('Skryto')
            ->assertDontSee('Cizí věc');
    }

    public function testEmptyState(): void
    {
        $this->actingAs($this->createUser());

        $this->get('/ucet')->assertSee('Zatím nemáte žádný inzerát.')->assertSee('/nahlasit');
    }

    public function testActionsDependOnStatus(): void
    {
        $user = $this->createUser();
        $active = $this->createItem(['user_id' => $user['id'], 'title' => 'Aktivní']);
        $returned = $this->createItem(['user_id' => $user['id'], 'title' => 'Vrácený', 'status' => 'returned']);
        $this->actingAs($user);

        $body = $this->get('/ucet')->assertSee("/ucet/inzeraty/{$active['id']}/upravit")->assertSee("/ucet/inzeraty/{$active['id']}/vraceno")->body();

        $this->assertStringContainsString("/ucet/inzeraty/{$returned['id']}/upravit", $body);
        $this->assertStringNotContainsString("/ucet/inzeraty/{$returned['id']}/vraceno", $body);
        $this->assertStringContainsString('data-confirm', $body);
    }

    public function testPageIsNotIndexed(): void
    {
        $this->actingAs($this->createUser());

        $this->get('/ucet')->assertSee('<meta name="robots" content="noindex">');
    }
}
