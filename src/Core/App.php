<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Auth;
use App\Repositories\BuildingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PdoItemRepository;
use App\Repositories\UserRepository;
use App\Uploads\PhotoProcessor;
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

    public static function session(): Session
    {
        $_SESSION ??= [];

        return self::$services['session'] ??= new Session($_SESSION);
    }

    public static function csrf(): Csrf
    {
        return self::$services['csrf'] ??= new Csrf(self::session());
    }

    public static function users(): UserRepository
    {
        return self::$services['users'] ??= new UserRepository(self::db());
    }

    public static function auth(): Auth
    {
        return self::$services['auth'] ??= new Auth(self::session(), self::users());
    }

    public static function photos(): PhotoProcessor
    {
        return self::$services['photos'] ??= new PhotoProcessor();
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
