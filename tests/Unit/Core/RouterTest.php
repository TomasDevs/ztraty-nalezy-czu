<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\HttpError;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testPassesNamedParametersToHandler(): void
    {
        $router = new Router();
        $received = [];
        $router->get('/inzerat/{id:\d+}/{slug}', function (string $id, string $slug) use (&$received) {
            $received = [$id, $slug];
        });

        $router->dispatch('GET', '/inzerat/12/klice');

        $this->assertSame(['12', 'klice'], $received);
    }

    public function testUnknownPathIsNotFound(): void
    {
        $router = new Router();
        $router->get('/inzeraty', fn () => null);

        try {
            $router->dispatch('GET', '/neexistuje');
            $this->fail('Expected HttpError');
        } catch (HttpError $error) {
            $this->assertSame(404, $error->status);
        }
    }

    public function testHandlerIsBoundToMethod(): void
    {
        $router = new Router();
        $called = false;
        $router->post('/odeslat', function () use (&$called) {
            $called = true;
        });

        $this->expectException(HttpError::class);

        try {
            $router->dispatch('GET', '/odeslat');
        } finally {
            $this->assertFalse($called);
        }
    }
}
