<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\App;
use App\Core\Kernel;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class KernelRoutingTest extends DatabaseTestCase
{
    use Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        App::reset();
        App::set('db', self::$db);
        $_GET = $_POST = [];
    }

    private function get(string $path): \App\Core\Response
    {
        $_SERVER['REQUEST_URI'] = $path;

        return Kernel::handle('GET', $path);
    }

    public function testWrongSlugRedirectsPermanently(): void
    {
        $item = $this->createItem(['title' => 'Ztracené klíče']);

        $response = $this->get("/inzerat/{$item['id']}/spatny-slug");

        $this->assertSame(301, $response->status);
        $this->assertSame("/inzerat/{$item['id']}/ztracene-klice", $response->location);
    }

    public function testDetailRendersItem(): void
    {
        $item = $this->createItem(['title' => 'Ztracené klíče']);

        $response = $this->get("/inzerat/{$item['id']}/ztracene-klice");

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Ztracené klíče', $response->body);
    }
}
