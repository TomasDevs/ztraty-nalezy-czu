<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\Admin\ItemController as AdminItemController;
use App\Controllers\Api\ItemController as ApiItemController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ItemController;
use App\Controllers\SeoController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/inzeraty', [ItemController::class, 'index']);
$router->get('/nahlasit', [ItemController::class, 'create']);
$router->get('/inzerat/{id:\d+}/{slug}', [ItemController::class, 'show']);
$router->post('/nahlasit', [ItemController::class, 'store']);
$router->get('/ucet', [AccountController::class, 'index']);
$router->get('/ucet/inzeraty/{id:\d+}/upravit', [ItemController::class, 'edit']);
$router->post('/ucet/inzeraty/{id:\d+}/upravit', [ItemController::class, 'update']);
$router->post('/ucet/inzeraty/{id:\d+}/vraceno', [ItemController::class, 'markReturned']);
$router->post('/ucet/inzeraty/{id:\d+}/smazat', [ItemController::class, 'destroy']);
$router->post('/inzerat/{id:\d+}/{slug}', [ItemController::class, 'contact']);

$router->get('/registrace', [AuthController::class, 'registerForm']);
$router->post('/registrace', [AuthController::class, 'register']);
$router->get('/prihlaseni', [AuthController::class, 'loginForm']);
$router->post('/prihlaseni', [AuthController::class, 'login']);
$router->post('/odhlaseni', [AuthController::class, 'logout']);

$router->get('/admin', [AdminItemController::class, 'index']);
$router->get('/admin/inzeraty', [AdminItemController::class, 'index']);
$router->post('/admin/inzeraty/{id:\d+}/stav', [AdminItemController::class, 'setStatus']);
$router->post('/admin/inzeraty/{id:\d+}/smazat', [AdminItemController::class, 'destroy']);

$router->get('/api/items', [ApiItemController::class, 'index']);

$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
