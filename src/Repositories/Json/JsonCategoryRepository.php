<?php

declare(strict_types=1);

namespace App\Repositories\Json;

use App\Repositories\CategoryRepository;

final class JsonCategoryRepository extends JsonCatalogRepository implements CategoryRepository
{
    protected function table(): string
    {
        return 'categories';
    }

    protected function itemColumn(): string
    {
        return 'category_id';
    }

    protected function hydrate(array $row): array
    {
        return ['id' => (int) $row['id'], 'slug' => $row['slug'], 'name' => $row['name']];
    }
}
