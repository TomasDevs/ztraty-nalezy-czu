<?php

declare(strict_types=1);

namespace App\Forms;

final class BuildingForm extends CatalogForm
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $short = '',
        public readonly string $slug = '',
        public readonly string $lat = '',
        public readonly string $lng = '',
    ) {
    }

    public static function fromArray(array $input): self
    {
        return new self(
            self::string($input, 'name'),
            self::string($input, 'short'),
            mb_strtolower(self::string($input, 'slug')),
            self::string($input, 'lat'),
            self::string($input, 'lng'),
        );
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
                $nameLength > 150 => 'Název může mít nejvýš 150 znaků.',
                default => null,
            },
            'short' => match (true) {
                $this->short === '' => 'Vyplňte zkratku.',
                mb_strlen($this->short) > 20 => 'Zkratka může mít nejvýš 20 znaků.',
                default => null,
            },
            'slug' => self::slugError(self::resolveSlug($this->slug, $this->name), $takenSlugs),
            'lat' => $this->coordinatesError(),
        ]);
    }

    public function toData(): array
    {
        return [
            'name' => $this->name,
            'short' => $this->short,
            'slug' => self::resolveSlug($this->slug, $this->name),
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
        ];
    }

    private function coordinatesError(): ?string
    {
        if (!is_numeric($this->lat) || !is_numeric($this->lng)) {
            return 'Zadejte zeměpisnou šířku a délku jako čísla.';
        }

        return self::inCampus((float) $this->lat, (float) $this->lng) ? null : 'Budova musí být na kampusu ČZU.';
    }
}
