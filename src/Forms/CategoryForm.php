<?php

declare(strict_types=1);

namespace App\Forms;

final class CategoryForm extends Form
{
    public function __construct(public readonly string $name = '', public readonly string $slug = '')
    {
    }

    public static function fromArray(array $input): self
    {
        return new self(self::string($input, 'name'), mb_strtolower(self::string($input, 'slug')));
    }

    public function slug(): string
    {
        return self::resolveSlug($this->slug, $this->name);
    }

    public function toData(): array
    {
        return ['name' => $this->name, 'slug' => $this->slug()];
    }

    /**
     * @param string[] $takenSlugs Slugs of the other rows
     */
    public function validate(array $takenSlugs): bool
    {
        $nameLength = mb_strlen($this->name);

        return $this->check([
            'name' => match (true) {
                $nameLength < 2 => 'Název musí mít aspoň 2 znaky.',
                $nameLength > 100 => 'Název může mít nejvýš 100 znaků.',
                default => null,
            },
            'slug' => self::slugError($this->slug(), $takenSlugs),
        ]);
    }
}
