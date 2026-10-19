<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $db = null;
    private static bool $schemaLoaded = false;

    public static function setUpBeforeClass(): void
    {
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
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$db->inTransaction()) {
            self::$db->rollBack();
        }
    }
}
