<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\LoginForm;
use PHPUnit\Framework\TestCase;

final class LoginFormTest extends TestCase
{
    public function testBothFieldsAreRequired(): void
    {
        $form = LoginForm::fromArray([]);

        $this->assertFalse($form->validate());
        $this->assertSame(['email', 'password'], array_keys($form->errors));
    }

    public function testValidInputPasses(): void
    {
        $form = LoginForm::fromArray(['email' => ' Jan@Example.com ', 'password' => 'x']);

        $this->assertTrue($form->validate());
        $this->assertSame('jan@example.com', $form->email);
    }
}
