<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\ContactForm;
use PHPUnit\Framework\TestCase;

final class ContactFormTest extends TestCase
{
    public function testValidInputPasses(): void
    {
        $form = ContactForm::fromArray([
            'name' => 'Jan',
            'email' => 'jan@studenti.czu.cz',
            'message' => str_repeat('a', ContactForm::MIN_MESSAGE_LENGTH),
        ]);

        $this->assertTrue($form->validate());
    }

    public function testReportsEveryInvalidField(): void
    {
        $form = ContactForm::fromArray(['email' => 'nope', 'message' => 'short']);

        $this->assertFalse($form->validate());
        $this->assertSame(['name', 'email', 'message'], array_keys($form->errors));
    }
}
