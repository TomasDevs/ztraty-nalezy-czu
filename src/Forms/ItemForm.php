<?php

declare(strict_types=1);

namespace App\Forms;

use App\Repositories\ItemQuery;

final class ItemForm extends Form
{
    public const MAX_PHOTO_BYTES = 5 * 1024 * 1024;
    public const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const PRIVATE_CATEGORY = 'doklady';

    private ?array $categoryRow = null;
    private ?array $buildingRow = null;

    public function __construct(
        public readonly string $type = '',
        public readonly string $title = '',
        public readonly string $category = '',
        public readonly string $building = '',
        public readonly string $location = '',
        public readonly string $date = '',
        public readonly string $description = '',
        public readonly string $lat = '',
        public readonly string $lng = '',
        public readonly ?array $photo = null,
    ) {
    }

    public static function fromArray(array $input, ?array $photo = null): self
    {
        return new self(
            self::string($input, 'type'),
            self::string($input, 'title'),
            self::string($input, 'category'),
            self::string($input, 'building'),
            self::string($input, 'location'),
            self::string($input, 'date'),
            self::string($input, 'description'),
            self::string($input, 'lat'),
            self::string($input, 'lng'),
            $photo,
        );
    }

    public static function fromItem(array $item): self
    {
        return new self(
            $item['type'],
            $item['title'],
            $item['category']['slug'],
            $item['building']['slug'],
            $item['location'],
            $item['date'],
            $item['description'],
            (string) $item['lat'],
            (string) $item['lng'],
        );
    }

    public function validate(array $categories, array $buildings): bool
    {
        $this->categoryRow = array_column($categories, null, 'slug')[$this->category] ?? null;
        $this->buildingRow = array_column($buildings, null, 'slug')[$this->building] ?? null;

        return $this->check([
            'type' => isset(ItemQuery::TYPES[$this->type]) ? null : 'Vyberte, jestli jde o ztrátu, nebo nález.',
            'title' => $this->length($this->title, 3, 150, 'Název'),
            'category' => $this->categoryRow ? null : 'Vyberte kategorii.',
            'building' => $this->buildingRow ? null : 'Vyberte budovu.',
            'location' => $this->length($this->location, 1, 150, 'Místo'),
            'date' => $this->dateError(),
            'description' => $this->length($this->description, 20, 5000, 'Popis'),
            'lat' => $this->coordinatesError(),
            'photo' => $this->photoError(),
        ]);
    }

    public function toData(): array
    {
        $hasCoordinates = $this->lat !== '';

        return [
            'type' => $this->type,
            'title' => $this->title,
            'category_id' => $this->categoryRow['id'],
            'building_id' => $this->buildingRow['id'],
            'location' => $this->location,
            'date' => $this->date,
            'description' => $this->description,
            'lat' => $hasCoordinates ? (float) $this->lat : $this->buildingRow['lat'],
            'lng' => $hasCoordinates ? (float) $this->lng : $this->buildingRow['lng'],
        ];
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null
            && $this->photo['error'] === UPLOAD_ERR_OK
            && $this->category !== self::PRIVATE_CATEGORY;
    }

    private function length(string $value, int $min, int $max, string $label): ?string
    {
        $length = mb_strlen($value);

        return match (true) {
            $length < $min => $min === 1 ? "{$label} je povinné." : sprintf('%s musí mít aspoň %d znaků.', $label, $min),
            $length > $max => sprintf('%s může mít nejvýš %d znaků.', $label, $max),
            default => null,
        };
    }

    private function dateError(): ?string
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $this->date);

        return match (true) {
            $date === false || $date->format('Y-m-d') !== $this->date => 'Zadejte datum ve tvaru rrrr-mm-dd.',
            $this->date > date('Y-m-d') => 'Datum nemůže být v budoucnosti.',
            default => null,
        };
    }

    private function coordinatesError(): ?string
    {
        if ($this->lat === '' && $this->lng === '') {
            return null;
        }

        if (!is_numeric($this->lat) || !is_numeric($this->lng)) {
            return 'Vyberte místo na mapě, nebo nechte souřadnice prázdné.';
        }

        return self::inCampus((float) $this->lat, (float) $this->lng) ? null : 'Místo musí být na kampusu ČZU.';
    }

    private function photoError(): ?string
    {
        if ($this->photo === null || $this->photo['error'] === UPLOAD_ERR_NO_FILE || $this->category === self::PRIVATE_CATEGORY) {
            return null;
        }

        return match (true) {
            $this->photo['error'] !== UPLOAD_ERR_OK => 'Fotku se nepodařilo nahrát.',
            $this->photo['size'] > self::MAX_PHOTO_BYTES => 'Fotka může mít nejvýš 5 MB.',
            !in_array((new \finfo(FILEINFO_MIME_TYPE))->file($this->photo['tmp_name']), self::PHOTO_TYPES, true) => 'Nahrajte fotku ve formátu JPEG, PNG nebo WebP.',
            default => null,
        };
    }
}
