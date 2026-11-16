<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Database;

require dirname(__DIR__) . '/src/bootstrap.php';

$load = fn (string $name) => json_decode((string) file_get_contents(__DIR__ . "/mock/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);

// The seed user cannot log in: "!" is not a valid password hash.
$seedUser = ['name' => 'Tým Ztráty a nálezy', 'email' => 'tym@ztraty.example', 'password_hash' => '!'];

if (App::usesJson()) {
    echo "Recreating JSON files in storage/data\n";
    App::store()->truncate('users', 'categories', 'buildings', 'items', 'messages');
    $userId = App::store()->insert('users', [...$seedUser, 'role' => 'user', 'is_blocked' => false, 'created_at' => date('Y-m-d H:i:s')]);
} else {
    echo "Recreating tables in " . env('DB_NAME') . "\n";
    Database::runSqlFile(App::db(), __DIR__ . '/schema.sql');
    App::db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')->execute(array_values($seedUser));
    $userId = (int) App::db()->lastInsertId();
}

$categoryIds = [];
foreach ($load('categories') as $category) {
    $categoryIds[$category['slug']] = App::categories()->create($category);
}

$buildingIds = [];
foreach ($load('buildings') as $building) {
    $buildingIds[$building['slug']] = App::buildings()->create($building);
}

foreach ($load('items') as $item) {
    $id = App::items()->create([
        ...$item,
        'user_id' => $userId,
        'category_id' => $categoryIds[$item['category']],
        'building_id' => $buildingIds[$item['building']],
    ]);

    if ($item['photo'] !== null) {
        App::items()->setPhoto($id, $item['photo']);
    }
}

echo sprintf("Seeded %d categories, %d buildings, %d items.\n", count($categoryIds), count($buildingIds), count(App::items()->all()));
echo App::usesJson()
    ? "Make an admin after registering: set \"role\": \"admin\" for the user in storage/data/users.json\n"
    : "Make an admin after registering: UPDATE users SET role = 'admin' WHERE email = '...';\n";
