<?php

declare(strict_types=1);

use App\Controllers\Api\ItemController as ApiItemController;
use App\Controllers\HomeController;
use App\Controllers\ItemController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/inzeraty', [ItemController::class, 'index']);
$router->get('/inzerat/{id:\d+}/{slug}', [ItemController::class, 'show']);
$router->post('/inzerat/{id:\d+}/{slug}', [ItemController::class, 'contact']);

$router->get('/api/items', [ApiItemController::class, 'index']);
