<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\DatabaseTestCase;

final class DatabaseTest extends DatabaseTestCase
{
    public function testSchemaCreatesAllTables(): void
    {
        $tables = self::$db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        sort($tables);

        $this->assertSame(['buildings', 'categories', 'items', 'messages', 'photos', 'users'], $tables);
    }

    public function testConnectionUsesUtf8(): void
    {
        $this->assertSame('ž', self::$db->query("SELECT 'ž'")->fetchColumn());
    }
}
