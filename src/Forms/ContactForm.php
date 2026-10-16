<?php

declare(strict_types=1);

namespace App\Forms;

final class ContactForm extends Form
{
    public const MIN_MESSAGE_LENGTH = 20;

    public function __construct(
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly string $message = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        return new self(self::string($input, 'name'), self::string($input, 'email'), self::string($input, 'message'));
    }

    public function validate(): bool
    {
        return $this->check([
            'name' => $this->name === '' ? 'Vyplňte své jméno.' : null,
            'email' => filter_var($this->email, FILTER_VALIDATE_EMAIL) ? null : 'Zadejte platný e-mail, např. jan.novak@studenti.czu.cz.',
            'message' => mb_strlen($this->message) < self::MIN_MESSAGE_LENGTH
                ? sprintf('Zpráva musí mít aspoň %d znaků.', self::MIN_MESSAGE_LENGTH)
                : null,
        ]);
    }
}
