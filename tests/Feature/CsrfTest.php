<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\FeatureTestCase;

final class CsrfTest extends FeatureTestCase
{
    public function testPostWithoutTokenIsRejected(): void
    {
        $item = $this->createItem(['title' => 'Klíče']);

        $this->post("/inzerat/{$item['id']}/klice", [], withToken: false)->assertStatus(419);
    }

    public function testPostWithForgedTokenIsRejected(): void
    {
        $item = $this->createItem(['title' => 'Klíče']);

        $this->post("/inzerat/{$item['id']}/klice", ['_token' => 'forged'], withToken: false)->assertStatus(419);
    }

    public function testPostWithTokenIsProcessed(): void
    {
        $item = $this->createItem(['title' => 'Klíče']);

        $response = $this->post("/inzerat/{$item['id']}/klice");

        $this->assertNotSame(419, $response->status());
    }

    public function testFormsContainTheToken(): void
    {
        $item = $this->createItem(['title' => 'Klíče']);
        $this->actingAs($this->createUser());

        $this->get("/inzerat/{$item['id']}/klice")->assertSee('name="_token"');
    }
}
