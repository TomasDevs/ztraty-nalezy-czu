<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\FeatureTestCase;

final class SeoTest extends FeatureTestCase
{
    public function testRobotsDisallowsPrivateAreas(): void
    {
        $this->get('/robots.txt')
            ->assertSee('Disallow: /api/')
            ->assertSee('Disallow: /admin/')
            ->assertSee('Disallow: /ucet')
            ->assertSee('Sitemap:');
    }

    public function testSitemapListsVisibleItemsOnly(): void
    {
        $visible = $this->createItem(['title' => 'Viditelný inzerát']);
        $hidden = $this->createItem(['title' => 'Skrytý inzerát', 'status' => 'hidden']);

        $this->get('/sitemap.xml')
            ->assertSee("/inzerat/{$visible['id']}/viditelny-inzerat")
            ->assertDontSee("/inzerat/{$hidden['id']}/")
            ->assertDontSee('/ucet')
            ->assertDontSee('/admin');
    }
}
