<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Forms\CatalogForm;
use App\Forms\CategoryForm;
use App\Repositories\CatalogRepository;

final class CategoryController extends CatalogController
{
    protected function repository(): CatalogRepository
    {
        return App::categories();
    }

    protected function formFor(array $input): CatalogForm
    {
        return CategoryForm::fromArray($input);
    }

    protected function path(): string
    {
        return '/admin/kategorie';
    }

    protected function template(): string
    {
        return 'admin/categories';
    }

    protected function title(): string
    {
        return 'Administrace: kategorie';
    }

    protected function messages(): array
    {
        return [
            'created' => 'Kategorie je přidaná.',
            'updated' => 'Kategorie je upravená.',
            'deleted' => 'Kategorie je smazaná.',
            'inUse' => 'Kategorii nejde smazat, obsahuje inzeráty: %d.',
        ];
    }
}
