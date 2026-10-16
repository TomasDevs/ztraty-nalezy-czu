<?php

declare(strict_types=1);

namespace App\Forms;

abstract class CatalogForm extends Form
{
    /**
     * @param string[] $takenSlugs Slugs of the other rows
     */
    abstract public function validate(array $takenSlugs): bool;

    abstract public function toData(): array;
}
