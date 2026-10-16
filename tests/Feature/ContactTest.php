<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class ContactTest extends FeatureTestCase
{
    private array $author;
    private array $item;
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = $this->createUser(['name' => 'Autor', 'email' => 'autor@example.com']);
        $this->item = $this->createItem(['user_id' => $this->author['id'], 'title' => 'Ztracené klíče']);
        $this->path = "/inzerat/{$this->item['id']}/ztracene-klice";
    }

    private function messageCount(): int
    {
        return (int) self::$db->query("SELECT COUNT(*) FROM messages WHERE item_id = {$this->item['id']}")->fetchColumn();
    }

    public function testGuestSeesLoginPromptInsteadOfForm(): void
    {
        $this->get($this->path)
            ->assertDontSee('name="message"')
            ->assertSee('Pro kontaktování autora se musíte přihlásit')
            ->assertSee('/prihlaseni?zpet=');
    }

    public function testGuestPostIsRedirectedToLogin(): void
    {
        $this->post($this->path, ['message' => str_repeat('a', 30)])
            ->assertRedirect('/prihlaseni?zpet=' . rawurlencode($this->path));

        $this->assertSame(0, $this->messageCount());
        $this->assertSame([], $this->mailer->sent);
    }

    public function testLoggedInUserSeesMessageFieldOnly(): void
    {
        $this->actingAs($this->createUser());

        $this->get($this->path)
            ->assertSee('name="message"')
            ->assertDontSee('name="email"')
            ->assertDontSee('name="name"');
    }

    public function testValidMessageIsStoredAndMailed(): void
    {
        $sender = $this->createUser(['name' => 'Jan Novák', 'email' => 'jan@example.com']);
        $this->actingAs($sender);

        $this->post($this->path, ['message' => 'Ahoj, ty klíče mám, napiš mi kde se sejdeme.'])
            ->assertRedirect($this->path . '?odeslano=1#kontakt');

        $this->assertSame(1, $this->messageCount());
        $this->assertCount(1, $this->mailer->sent);
        $mail = $this->mailer->sent[0];
        $this->assertSame('autor@example.com', $mail->to);
        $this->assertSame('jan@example.com', $mail->replyTo);
        $this->assertStringContainsString('Ahoj, ty klíče mám', $mail->body);
    }

    public function testSentConfirmationIsShown(): void
    {
        $this->actingAs($this->createUser());

        $this->get($this->path, ['odeslano' => '1'])->assertSee('Zpráva je odeslaná.');
    }

    public function testShortMessageShowsErrorAndSendsNothing(): void
    {
        $this->actingAs($this->createUser());

        $this->post($this->path, ['message' => 'krátká'])
            ->assertStatus(422)
            ->assertSee('Zpráva musí mít aspoň 20 znaků.');

        $this->assertSame(0, $this->messageCount());
        $this->assertSame([], $this->mailer->sent);
    }

    public function testMailFailureIsReported(): void
    {
        $this->mailer->succeeds = false;
        $this->actingAs($this->createUser());

        $this->post($this->path, ['message' => str_repeat('a', 30)])->assertRedirect($this->path . '#kontakt');

        $this->get($this->path)->assertSee('Zprávu se nepodařilo odeslat e-mailem');
    }

    public function testAuthorHasNoContactForm(): void
    {
        $this->actingAs($this->author);

        $this->get($this->path)->assertDontSee('name="message"');
        $this->post($this->path, ['message' => str_repeat('a', 30)])->assertRedirect($this->path);

        $this->assertSame([], $this->mailer->sent);
    }

    public function testReturnedItemCannotBeContacted(): void
    {
        $returned = $this->createItem(['user_id' => $this->author['id'], 'title' => 'Vrácené', 'status' => 'returned']);
        $this->actingAs($this->createUser());

        $this->get("/inzerat/{$returned['id']}/vracene")->assertDontSee('name="message"');
        $this->post("/inzerat/{$returned['id']}/vracene", ['message' => str_repeat('a', 30)])
            ->assertRedirect("/inzerat/{$returned['id']}/vracene");

        $this->assertSame([], $this->mailer->sent);
    }
}
