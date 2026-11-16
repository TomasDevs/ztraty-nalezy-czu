<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/bootstrap.php';

App\Core\Env::load(ROOT_PATH . '/.env.testing');

$_ENV['APP_URL'] = 'http://localhost';
$_ENV['BASE_PATH'] = '';
$_ENV['MAIL_DRIVER'] = 'log';
// mysql (default) or json, e.g. DATA_DRIVER=json vendor/bin/phpunit
$_ENV['DATA_DRIVER'] = getenv('DATA_DRIVER') ?: 'mysql';
