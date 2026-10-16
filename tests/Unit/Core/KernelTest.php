<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Kernel;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $_POST = [];
    }

    public function testUnknownPathRendersNotFoundPage(): void
    {
        $_SERVER['REQUEST_URI'] = '/neexistuje';

        $response = Kernel::handle('GET', '/neexistuje');

        $this->assertStringContainsString('Stránka nenalezena', $response->body);
    }

    public function testApiErrorIsJson(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/neexistuje';

        $response = Kernel::handle('GET', '/api/neexistuje');

        $this->assertSame('{"error":404}', $response->body);
    }

    public function testRegularPageIsReturned(): void
    {
        $_SERVER['REQUEST_URI'] = '/robots.txt';

        $response = Kernel::handle('GET', '/robots.txt');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Sitemap:', $response->body);
    }

    public function testRedirectCarriesLocationAndStatus(): void
    {
        $_SERVER['REQUEST_URI'] = '/inzerat/1/spatny-slug';

        $response = Kernel::handle('GET', '/inzerat/1/spatny-slug');

        $this->assertSame(301, $response->status);
        $this->assertStringStartsWith('/inzerat/1/', $response->location);
    }
}
