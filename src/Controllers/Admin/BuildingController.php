<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Forms\BuildingForm;
use App\Forms\CatalogForm;
use App\Repositories\CatalogRepository;

final class BuildingController extends CatalogController
{
    protected function repository(): CatalogRepository
    {
        return App::buildings();
    }

    protected function formFor(array $input): CatalogForm
    {
        return BuildingForm::fromArray($input);
    }

    protected function path(): string
    {
        return '/admin/budovy';
    }

    protected function template(): string
    {
        return 'admin/buildings';
    }

    protected function title(): string
    {
        return 'Administrace: budovy';
    }

    protected function messages(): array
    {
        return [
            'created' => 'Budova je přidaná.',
            'updated' => 'Budova je upravená.',
            'deleted' => 'Budova je smazaná.',
            'inUse' => 'Budovu nejde smazat, obsahuje inzeráty: %d.',
        ];
    }
}
