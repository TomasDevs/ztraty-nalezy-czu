<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\ContactMail;
use PHPUnit\Framework\TestCase;

final class ContactMailTest extends TestCase
{
    private const ITEM = ['id' => 7, 'slug' => 'ztracene-klice', 'title' => 'Ztracené klíče'];
    private const AUTHOR = ['name' => 'Autor', 'email' => 'autor@example.com'];
    private const SENDER = ['name' => 'Jan Novák', 'email' => 'jan@example.com'];

    public function testAddressesAndSubject(): void
    {
        $mail = ContactMail::create(self::ITEM, self::AUTHOR, self::SENDER, 'Ahoj, mám vaše klíče.');

        $this->assertSame('autor@example.com', $mail->to);
        $this->assertSame('jan@example.com', $mail->replyTo);
        $this->assertSame('Zpráva k inzerátu #007: Ztracené klíče', $mail->subject);
    }

    public function testBodyContainsMessageSenderAndAbsoluteLink(): void
    {
        $mail = ContactMail::create(self::ITEM, self::AUTHOR, self::SENDER, 'Ahoj, mám vaše klíče.');

        $this->assertStringContainsString('Ahoj, mám vaše klíče.', $mail->body);
        $this->assertStringContainsString('Jan Novák', $mail->body);
        $this->assertStringContainsString('http://localhost/inzerat/7/ztracene-klice', $mail->body);
    }

    public function testSubjectCannotContainLineBreaks(): void
    {
        $mail = ContactMail::create([...self::ITEM, 'title' => "Klíče\r\nBcc: x@example.com"], self::AUTHOR, self::SENDER, 'Zpráva');

        $this->assertStringNotContainsString("\n", $mail->subject);
        $this->assertStringNotContainsString("\r", $mail->subject);
    }
}
