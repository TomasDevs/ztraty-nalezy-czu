<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\RegisterForm;
use PHPUnit\Framework\TestCase;

final class RegisterFormTest extends TestCase
{
    private function form(array $overrides = []): RegisterForm
    {
        return RegisterForm::fromArray([
            'name' => 'Jan Novák',
            'email' => 'jan.novak@studenti.czu.cz',
            'password' => 'tajne-heslo',
            'password_confirmation' => 'tajne-heslo',
            ...$overrides,
        ]);
    }

    public function testValidInputPasses(): void
    {
        $form = $this->form();

        $this->assertTrue($form->validate(emailTaken: false));
        $this->assertSame([], $form->errors);
    }

    public function testEmailIsNormalizedToLowercase(): void
    {
        $this->assertSame('jan@example.com', $this->form(['email' => '  Jan@Example.COM '])->email);
    }

    public function testNameIsRequiredAndLimited(): void
    {
        $this->assertArrayHasKey('name', $this->invalid(['name' => '  ']));
        $this->assertArrayHasKey('name', $this->invalid(['name' => str_repeat('a', 101)]));
    }

    public function testEmailMustBeValidAndFree(): void
    {
        $this->assertArrayHasKey('email', $this->invalid(['email' => 'nope']));

        $form = $this->form();
        $this->assertFalse($form->validate(emailTaken: true));
        $this->assertSame('Účet s tímto e-mailem už existuje.', $form->errors['email']);
    }

    public function testPasswordNeedsEightCharactersAndConfirmation(): void
    {
        $this->assertArrayHasKey('password', $this->invalid(['password' => '1234567', 'password_confirmation' => '1234567']));
        $this->assertArrayHasKey('password_confirmation', $this->invalid(['password_confirmation' => 'jine-heslo']));
    }

    public function testPasswordIsNotTrimmed(): void
    {
        $form = $this->form(['password' => ' heslo123 ', 'password_confirmation' => ' heslo123 ']);

        $this->assertSame(' heslo123 ', $form->password);
        $this->assertTrue($form->validate(emailTaken: false));
    }

    private function invalid(array $overrides): array
    {
        $form = $this->form($overrides);
        $this->assertFalse($form->validate(emailTaken: false));

        return $form->errors;
    }
}
