<?php

declare(strict_types=1);

namespace App\Forms;

final class ContactForm
{
    public const MIN_MESSAGE_LENGTH = 20;

    /** @var array<string, string> */
    public array $errors = [];

    public function __construct(
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly string $message = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        $string = fn (string $key) => is_string($input[$key] ?? null) ? trim($input[$key]) : '';

        return new self($string('name'), $string('email'), $string('message'));
    }

    public function validate(): bool
    {
        $this->errors = array_filter([
            'name' => $this->name === '' ? 'Vyplňte své jméno.' : null,
            'email' => filter_var($this->email, FILTER_VALIDATE_EMAIL) ? null : 'Zadejte platný e-mail, např. jan.novak@studenti.czu.cz.',
            'message' => mb_strlen($this->message) < self::MIN_MESSAGE_LENGTH
                ? sprintf('Zpráva musí mít aspoň %d znaků.', self::MIN_MESSAGE_LENGTH)
                : null,
        ]);

        return $this->errors === [];
    }
}
