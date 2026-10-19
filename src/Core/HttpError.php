<?php

declare(strict_types=1);

namespace App\Core;

final class HttpError extends \RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct("HTTP {$status}");
    }
}
