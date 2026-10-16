<?php

declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BuildingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PdoItemRepository;

require dirname(__DIR__) . '/src/bootstrap.php';

$db = Database::connect();
$load = fn (string $name) => json_decode((string) file_get_contents(__DIR__ . "/mock/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);

echo "Recreating tables in " . env('DB_NAME') . "\n";
Database::runSqlFile($db, __DIR__ . '/schema.sql');

$categories = new CategoryRepository($db);
$buildings = new BuildingRepository($db);
$items = new PdoItemRepository($db);

$categoryIds = [];
foreach ($load('categories') as $category) {
    $categoryIds[$category['slug']] = $categories->create($category);
}

$buildingIds = [];
foreach ($load('buildings') as $building) {
    $buildingIds[$building['slug']] = $buildings->create($building);
}

// The seed user cannot log in: "!" is not a valid password hash.
$db->prepare("INSERT INTO users (name, email, password_hash) VALUES ('Tým Ztráty a nálezy', 'tym@ztraty.example', '!')")->execute();
$userId = (int) $db->lastInsertId();

foreach ($load('items') as $item) {
    $id = $items->create([
        ...$item,
        'user_id' => $userId,
        'category_id' => $categoryIds[$item['category']],
        'building_id' => $buildingIds[$item['building']],
    ]);

    if ($item['photo'] !== null) {
        $items->setPhoto($id, $item['photo']);
    }
}

echo sprintf("Seeded %d categories, %d buildings, %d items.\n", count($categoryIds), count($buildingIds), count($items->all()));
echo "Make an admin after registering: UPDATE users SET role = 'admin' WHERE email = '...';\n";
