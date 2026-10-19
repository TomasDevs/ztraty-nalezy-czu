<?php

declare(strict_types=1);

namespace App\Forms;

final class RegisterForm extends Form
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly string $password = '',
        public readonly string $passwordConfirmation = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        return new self(
            self::string($input, 'name'),
            mb_strtolower(self::string($input, 'email')),
            is_string($input['password'] ?? null) ? $input['password'] : '',
            is_string($input['password_confirmation'] ?? null) ? $input['password_confirmation'] : '',
        );
    }

    public function validate(bool $emailTaken): bool
    {
        return $this->check([
            'name' => match (true) {
                $this->name === '' => 'Vyplňte své jméno.',
                mb_strlen($this->name) > 100 => 'Jméno může mít nejvýš 100 znaků.',
                default => null,
            },
            'email' => match (true) {
                !filter_var($this->email, FILTER_VALIDATE_EMAIL) => 'Zadejte platný e-mail, např. jan.novak@studenti.czu.cz.',
                $emailTaken => 'Účet s tímto e-mailem už existuje.',
                default => null,
            },
            'password' => mb_strlen($this->password) < self::MIN_PASSWORD_LENGTH
                ? sprintf('Heslo musí mít aspoň %d znaků.', self::MIN_PASSWORD_LENGTH)
                : null,
            'password_confirmation' => $this->password === $this->passwordConfirmation ? null : 'Hesla se neshodují.',
        ]);
    }
}
