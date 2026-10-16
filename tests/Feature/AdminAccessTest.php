<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FeatureTestCase;

final class AdminAccessTest extends FeatureTestCase
{
    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['GET', '/admin'],
            'items' => ['GET', '/admin/inzeraty'],
            'item status' => ['POST', '/admin/inzeraty/1/stav'],
            'item delete' => ['POST', '/admin/inzeraty/1/smazat'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function testGuestIsSentToLogin(string $method, string $path): void
    {
        $response = $method === 'GET' ? $this->get($path) : $this->post($path);

        $response->assertRedirect('/prihlaseni?zpet=' . rawurlencode($path));
    }

    #[DataProvider('adminRoutes')]
    public function testRegularUserIsForbidden(string $method, string $path): void
    {
        $this->actingAs($this->createUser());

        $response = $method === 'GET' ? $this->get($path) : $this->post($path);

        $response->assertStatus(403);
    }
}
