<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\BuildingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PdoItemRepository;
use PDO;

final class App
{
    /** @var array<string, object> */
    private static array $services = [];

    public static function set(string $id, object $service): void
    {
        self::$services[$id] = $service;
    }

    public static function reset(): void
    {
        self::$services = [];
    }

    public static function db(): PDO
    {
        return self::$services['db'] ??= Database::connect();
    }

    public static function items(): ItemRepository
    {
        return self::$services['items'] ??= new PdoItemRepository(self::db());
    }

    public static function categories(): CategoryRepository
    {
        return self::$services['categories'] ??= new CategoryRepository(self::db());
    }

    public static function buildings(): BuildingRepository
    {
        return self::$services['buildings'] ??= new BuildingRepository(self::db());
    }
}
