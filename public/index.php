<?php

declare(strict_types=1);

// Let the PHP built-in server serve static files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new App\Core\Router();
require ROOT_PATH . '/src/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], current_path());
