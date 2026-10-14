<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ItemRepository;
use App\Repositories\JsonItemRepository;

abstract class Controller
{
    protected function items(): ItemRepository
    {
        static $repository = null;

        // TODO: switch to a PDO repository when DATA_SOURCE=db
        return $repository ??= new JsonItemRepository();
    }
}
