<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Row rules shared by the item repositories.
 */
final class ItemRows
{
    public const WRITABLE = ['category_id', 'building_id', 'type', 'title', 'description', 'location', 'lat', 'lng', 'date'];

    /**
     * Columns computed from the item text: URL slug and folded search text.
     */
    public static function derived(array $data): array
    {
        $slug = trim(substr(slugify($data['title']), 0, 160), '-');

        return [
            'slug' => $slug !== '' ? $slug : 'inzerat',
            'search_text' => fold("{$data['title']} {$data['description']} {$data['location']}"),
        ];
    }

    public static function marker(array $row): array
    {
        return [
            'lat' => (float) $row['lat'],
            'lng' => (float) $row['lng'],
            'title' => $row['title'],
            'type' => $row['type'],
            'url' => item_url($row),
        ];
    }
}
