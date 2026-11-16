<?php

declare(strict_types=1);

namespace App\Repositories;

interface CategoryRepository extends CatalogRepository
{
    public function findBySlug(string $slug): ?array;
}
