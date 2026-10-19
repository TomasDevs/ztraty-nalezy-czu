<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Listing filters shared by the page, the API and the repository.
 */
final class ItemQuery
{
    public const PER_PAGE = 9;
    public const TYPES = ['lost' => 'Ztráta', 'found' => 'Nález'];
    public const TYPE_LISTS = ['lost' => 'Ztráty', 'found' => 'Nálezy'];

    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $category = null,
        public readonly ?string $building = null,
        public readonly ?string $q = null,
        public readonly int $page = 1,
    ) {
    }

    public static function fromArray(array $input): self
    {
        $string = fn (string $key) => is_string($input[$key] ?? null) && trim($input[$key]) !== ''
            ? trim($input[$key])
            : null;

        $type = $string('type');

        return new self(
            type: $type !== null && isset(self::TYPES[$type]) ? $type : null,
            category: $string('category'),
            building: $string('building'),
            q: $string('q'),
            page: max(1, (int) ($input['page'] ?? 1)),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'category' => $this->category,
            'building' => $this->building,
            'q' => $this->q,
        ];
    }

    public function offset(): int
    {
        return ($this->page - 1) * self::PER_PAGE;
    }
}
