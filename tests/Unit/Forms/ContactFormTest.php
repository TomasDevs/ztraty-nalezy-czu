<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\ContactForm;
use PHPUnit\Framework\TestCase;

final class ContactFormTest extends TestCase
{
    public function testValidMessagePasses(): void
    {
        $form = ContactForm::fromArray(['message' => str_repeat('a', ContactForm::MIN_MESSAGE_LENGTH)]);

        $this->assertTrue($form->validate());
    }

    public function testShortMessageIsRejected(): void
    {
        $form = ContactForm::fromArray(['message' => 'short']);

        $this->assertFalse($form->validate());
        $this->assertSame(['message'], array_keys($form->errors));
    }

    public function testTooLongMessageIsRejected(): void
    {
        $form = ContactForm::fromArray(['message' => str_repeat('a', ContactForm::MAX_MESSAGE_LENGTH + 1)]);

        $this->assertFalse($form->validate());
    }

    public function testMessageIsTrimmed(): void
    {
        $this->assertSame('hello', ContactForm::fromArray(['message' => "  hello \n"])->message);
    }
}
