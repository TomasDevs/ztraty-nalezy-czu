<?php

declare(strict_types=1);

namespace App\Forms;

final class LoginForm extends Form
{
    public function __construct(
        public readonly string $email = '',
        public readonly string $password = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        return new self(
            mb_strtolower(self::string($input, 'email')),
            is_string($input['password'] ?? null) ? $input['password'] : '',
        );
    }

    public function validate(): bool
    {
        return $this->check([
            'email' => $this->email === '' ? 'Vyplňte e-mail.' : null,
            'password' => $this->password === '' ? 'Vyplňte heslo.' : null,
        ]);
    }
}
