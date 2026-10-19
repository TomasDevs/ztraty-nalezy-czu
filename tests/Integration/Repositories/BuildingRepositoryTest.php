<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\BuildingRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class BuildingRepositoryTest extends DatabaseTestCase
{
    use Fixtures;

    private BuildingRepository $buildings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildings = new BuildingRepository(self::$db);
    }

    public function testAllIsSortedByNameAndCoordinatesAreFloats(): void
    {
        $this->createBuilding(['name' => 'Zzz']);
        $this->createBuilding(['name' => 'Aaa', 'lat' => 50.131160, 'lng' => 14.373220]);

        $all = $this->buildings->all();

        $this->assertSame(['Aaa', 'Zzz'], array_column($all, 'name'));
        $this->assertSame(50.13116, $all[0]['lat']);
        $this->assertSame(14.37322, $all[0]['lng']);
    }

    public function testFindByIdAndSlug(): void
    {
        $building = $this->createBuilding(['slug' => 'pef', 'short' => 'PEF']);

        $this->assertSame('PEF', $this->buildings->find($building['id'])['short']);
        $this->assertSame($building['id'], $this->buildings->findBySlug('pef')['id']);
        $this->assertNull($this->buildings->find(999999));
        $this->assertNull($this->buildings->findBySlug('missing'));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->buildings->create(['name' => 'Knihovna', 'short' => 'KN', 'slug' => 'knihovna', 'lat' => 50.13, 'lng' => 14.37]);
        $this->assertSame('KN', $this->buildings->find($id)['short']);

        $this->buildings->update($id, ['name' => 'Velká knihovna', 'short' => 'VK', 'slug' => 'velka-knihovna', 'lat' => 50.131, 'lng' => 14.371]);
        $updated = $this->buildings->find($id);
        $this->assertSame(['Velká knihovna', 'VK', 'velka-knihovna', 50.131], [$updated['name'], $updated['short'], $updated['slug'], $updated['lat']]);

        $this->buildings->delete($id);
        $this->assertNull($this->buildings->find($id));
    }

    public function testItemCountCountsReferencingItems(): void
    {
        $building = $this->createBuilding();
        $this->assertSame(0, $this->buildings->itemCount($building['id']));

        $this->createItem(['building_id' => $building['id']]);
        $this->createItem(['building_id' => $building['id'], 'status' => 'hidden']);

        $this->assertSame(2, $this->buildings->itemCount($building['id']));
    }
}
