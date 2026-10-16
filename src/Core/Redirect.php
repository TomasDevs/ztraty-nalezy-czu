<?php

declare(strict_types=1);

namespace App\Core;

final class Redirect extends \RuntimeException
{
    public function __construct(public readonly string $location, public readonly int $status = 302)
    {
        parent::__construct("Redirect to {$location}");
    }
}
