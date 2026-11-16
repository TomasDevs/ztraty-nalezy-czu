<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\BuildingRepository;

final class JsonBuildingRepository extends JsonCatalogRepository implements BuildingRepository
{
    protected function table(): string
    {
        return 'buildings';
    }

    protected function itemColumn(): string
    {
        return 'building_id';
    }

    protected function hydrate(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'name' => $row['name'],
            'short' => $row['short'],
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
        ];
    }
}
