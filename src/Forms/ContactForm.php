<?php

declare(strict_types=1);

namespace App\Forms;

final class ContactForm extends Form
{
    public const MIN_MESSAGE_LENGTH = 20;
    public const MAX_MESSAGE_LENGTH = 2000;

    public function __construct(public readonly string $message = '')
    {
    }

    public static function fromArray(array $input): self
    {
        return new self(self::string($input, 'message'));
    }

    public function validate(): bool
    {
        $length = mb_strlen($this->message);

        return $this->check([
            'message' => match (true) {
                $length < self::MIN_MESSAGE_LENGTH => sprintf('Zpráva musí mít aspoň %d znaků.', self::MIN_MESSAGE_LENGTH),
                $length > self::MAX_MESSAGE_LENGTH => sprintf('Zpráva může mít nejvýš %d znaků.', self::MAX_MESSAGE_LENGTH),
                default => null,
            },
        ]);
    }
}
