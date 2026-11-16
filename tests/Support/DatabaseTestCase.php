<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\Database;
use App\Storage\JsonStore;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs against MariaDB, or against JSON files with DATA_DRIVER=json.
 * The helpers below give tests raw access to stored rows in both cases.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $db = null;
    protected static ?JsonStore $store = null;
    private static bool $schemaLoaded = false;
    private ?string $storeDir = null;

    public static function setUpBeforeClass(): void
    {
        if (self::usesJson()) {
            return;
        }

        try {
            self::$db ??= Database::connect();
        } catch (\PDOException) {
            self::markTestSkipped('Test database not available');
        }

        if (!self::$schemaLoaded) {
            Database::runSqlFile(self::$db, ROOT_PATH . '/database/schema.sql');
            self::$schemaLoaded = true;
        }
    }

    protected function setUp(): void
    {
        if (self::usesJson()) {
            $this->storeDir = sys_get_temp_dir() . '/store-' . uniqid();
            self::$store = new JsonStore($this->storeDir);
        } else {
            self::$db->beginTransaction();
        }

        $this->bindStorage();
    }

    protected function tearDown(): void
    {
        if ($this->storeDir !== null) {
            exec('rm -rf ' . escapeshellarg($this->storeDir));
        } elseif (self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        App::reset();
    }

    protected static function usesJson(): bool
    {
        return env('DATA_DRIVER') === 'json';
    }

    /**
     * Resets the services and points them at the test storage.
     */
    protected function bindStorage(): void
    {
        App::reset();
        self::usesJson() ? App::set('store', self::$store) : App::set('db', self::$db);
    }

    protected function insertRow(string $table, array $row): int
    {
        if (self::usesJson()) {
            return self::$store->insert($table, $row);
        }

        $columns = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        self::$db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})")->execute(array_values($row));

        return (int) self::$db->lastInsertId();
    }

    protected function findRow(string $table, int $id): ?array
    {
        if (self::usesJson()) {
            return self::$store->find($table, $id);
        }

        $statement = self::$db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    protected function deleteRow(string $table, int $id): void
    {
        if (self::usesJson()) {
            self::$store->delete($table, fn (array $row) => $row['id'] === $id);

            return;
        }

        self::$db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
    }

    protected function clearTables(string ...$tables): void
    {
        foreach ($tables as $table) {
            self::usesJson() ? self::$store->truncate($table) : self::$db->exec("DELETE FROM {$table}");
        }
    }
}
