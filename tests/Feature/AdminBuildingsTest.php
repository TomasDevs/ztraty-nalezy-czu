<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class AdminBuildingsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser(['role' => 'admin']));
    }

    private function data(array $overrides = []): array
    {
        return ['name' => 'Knihovna ČZU', 'short' => 'KN', 'slug' => 'knihovna', 'lat' => '50.1300', 'lng' => '14.3730', ...$overrides];
    }

    public function testListsBuildingsWithAddForm(): void
    {
        $this->createBuilding(['name' => 'Aula', 'slug' => 'aula', 'short' => 'AULA']);

        $this->get('/admin/budovy')
            ->assertOk()
            ->assertSee('value="Aula"')
            ->assertSee('action="/admin/budovy"')
            ->assertSee('<meta name="robots" content="noindex">');
    }

    public function testCreateAddsBuilding(): void
    {
        $this->post('/admin/budovy', $this->data())->assertRedirect('/admin/budovy');

        $building = App::buildings()->findBySlug('knihovna');
        $this->assertSame(['KN', 50.13, 14.373], [$building['short'], $building['lat'], $building['lng']]);
        $this->get('/admin/budovy')->assertSee('Budova je přidaná.');
    }

    public function testCoordinatesOutsideCampusAreRejected(): void
    {
        $before = count(App::buildings()->all());

        $this->post('/admin/budovy', $this->data(['lat' => '48.2', 'lng' => '16.37']))->assertRedirect('/admin/budovy');

        $this->assertCount($before, App::buildings()->all());
        $this->get('/admin/budovy')->assertSee('Budova musí být na kampusu ČZU.');
    }

    public function testDuplicateSlugIsRejected(): void
    {
        $this->createBuilding(['slug' => 'knihovna']);
        $before = count(App::buildings()->all());

        $this->post('/admin/budovy', $this->data());

        $this->assertCount($before, App::buildings()->all());
    }

    public function testUpdateChangesBuilding(): void
    {
        $building = $this->createBuilding(['slug' => 'knihovna']);

        $this->post("/admin/budovy/{$building['id']}", $this->data(['name' => 'Nová knihovna', 'lat' => '50.1305']))
            ->assertRedirect('/admin/budovy');

        $updated = App::buildings()->find($building['id']);
        $this->assertSame(['Nová knihovna', 50.1305], [$updated['name'], $updated['lat']]);
    }

    public function testUnusedBuildingCanBeDeleted(): void
    {
        $building = $this->createBuilding();

        $this->post("/admin/budovy/{$building['id']}/smazat")->assertRedirect('/admin/budovy');

        $this->assertNull(App::buildings()->find($building['id']));
    }

    public function testUsedBuildingCannotBeDeleted(): void
    {
        $building = $this->createBuilding();
        $this->createItem(['building_id' => $building['id']]);

        $this->post("/admin/budovy/{$building['id']}/smazat")->assertRedirect('/admin/budovy');

        $this->assertNotNull(App::buildings()->find($building['id']));
        $this->get('/admin/budovy')->assertSee('Budovu nejde smazat, obsahuje inzeráty: 1.');
    }

    public function testUnknownBuildingIsNotFound(): void
    {
        $this->post('/admin/budovy/999999', $this->data())->assertStatus(404);
        $this->post('/admin/budovy/999999/smazat')->assertStatus(404);
    }
}
