<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly ?string $location = null,
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);

        if ($this->location !== null) {
            header("Location: {$this->location}");
        }

        echo $this->body;
    }
}
