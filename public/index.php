<?php

declare(strict_types=1);

// Let the PHP built-in server serve static files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/src/bootstrap.php';

App\Core\Session::start();
App\Core\Kernel::handle($_SERVER['REQUEST_METHOD'], current_path())->send();
