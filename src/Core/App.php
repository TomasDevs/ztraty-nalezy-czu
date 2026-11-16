<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Auth;
use App\Mail\LogMailer;
use App\Mail\Mailer;
use App\Mail\NativeMailer;
use App\Repositories\BuildingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ItemRepository;
use App\Repositories\Json\JsonBuildingRepository;
use App\Repositories\Json\JsonCategoryRepository;
use App\Repositories\Json\JsonItemRepository;
use App\Repositories\Json\JsonMessageRepository;
use App\Repositories\Json\JsonUserRepository;
use App\Repositories\MessageRepository;
use App\Repositories\PdoBuildingRepository;
use App\Repositories\PdoCategoryRepository;
use App\Repositories\PdoItemRepository;
use App\Repositories\PdoMessageRepository;
use App\Repositories\PdoUserRepository;
use App\Repositories\UserRepository;
use App\Storage\JsonStore;
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

    /**
     * JSON files instead of MariaDB, for hosting without database access (DATA_DRIVER=json).
     */
    public static function store(): JsonStore
    {
        return self::$services['store'] ??= new JsonStore(ROOT_PATH . '/storage/data');
    }

    public static function usesJson(): bool
    {
        return env('DATA_DRIVER', 'mysql') === 'json';
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
        return self::$services['users'] ??= (self::usesJson() ? new JsonUserRepository(self::store()) : new PdoUserRepository(self::db()));
    }

    public static function auth(): Auth
    {
        return self::$services['auth'] ??= new Auth(self::session(), self::users());
    }

    public static function photos(): PhotoProcessor
    {
        return self::$services['photos'] ??= new PhotoProcessor();
    }

    public static function messages(): MessageRepository
    {
        return self::$services['messages'] ??= (self::usesJson() ? new JsonMessageRepository(self::store()) : new PdoMessageRepository(self::db()));
    }

    public static function mailer(): Mailer
    {
        return self::$services['mailer'] ??= env('MAIL_DRIVER', 'log') === 'mail'
            ? new NativeMailer((string) env('MAIL_FROM', 'noreply@localhost'))
            : new LogMailer(ROOT_PATH . '/storage/logs/mail.log');
    }

    public static function items(): ItemRepository
    {
        return self::$services['items'] ??= (self::usesJson() ? new JsonItemRepository(self::store()) : new PdoItemRepository(self::db()));
    }

    public static function categories(): CategoryRepository
    {
        return self::$services['categories'] ??= (self::usesJson() ? new JsonCategoryRepository(self::store()) : new PdoCategoryRepository(self::db()));
    }

    public static function buildings(): BuildingRepository
    {
        return self::$services['buildings'] ??= (self::usesJson() ? new JsonBuildingRepository(self::store()) : new PdoBuildingRepository(self::db()));
    }
}
