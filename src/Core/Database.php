<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', 'localhost'),
            env('DB_NAME', '')
        );

        $db = new PDO($dsn, (string) env('DB_USER', ''), (string) env('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Keep CURRENT_TIMESTAMP in the app timezone; an offset works without MariaDB tz tables.
        $db->exec(sprintf("SET time_zone = '%s'", date('P')));

        return $db;
    }

    public static function runSqlFile(PDO $db, string $file): void
    {
        $statements = preg_split('/;\s*$/m', (string) file_get_contents($file));

        foreach ($statements as $statement) {
            if (trim($statement) !== '') {
                $db->exec($statement);
            }
        }
    }
}
