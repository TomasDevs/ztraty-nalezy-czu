<?php

declare(strict_types=1);

namespace App\Seo;

use App\Repositories\ItemQuery;

/**
 * Pages filtered by exactly one type, category or building are indexable landing pages
 * (e.g. "Nálezy", "PEF"). Combinations and pagination point canonical to /inzeraty,
 * search results are not indexed.
 */
final class ListingMeta
{
    public const DEFAULT_HEADING = 'Ztracené a nalezené věci';

    private function __construct(
        public readonly string $heading,
        public readonly string $canonical,
        public readonly bool $noindex,
    ) {
    }

    public static function for(ItemQuery $query, array $categories, array $buildings): self
    {
        $filters = array_filter($query->toArray());
        $noindex = isset($filters['q']);

        if (count($filters) !== 1 || $noindex || $query->page > 1) {
            return new self(self::DEFAULT_HEADING, '/inzeraty', $noindex);
        }

        $key = array_key_first($filters);
        $value = $filters[$key];
        $lookup = fn (array $rows) => array_column($rows, null, 'slug')[$value]['name'] ?? null;

        $heading = match ($key) {
            'type' => isset(ItemQuery::TYPE_LISTS[$value]) ? ItemQuery::TYPE_LISTS[$value] . ' na kampusu ČZU' : null,
            'category' => ($name = $lookup($categories)) ? "{$name}: ztráty a nálezy na ČZU" : null,
            'building' => ($name = $lookup($buildings)) ? "Ztráty a nálezy: {$name}" : null,
        };

        return $heading
            ? new self($heading, '/inzeraty?' . http_build_query($filters), false)
            : new self(self::DEFAULT_HEADING, '/inzeraty', false);
    }
}
