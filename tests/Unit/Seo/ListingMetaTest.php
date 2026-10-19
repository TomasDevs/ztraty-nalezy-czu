<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Repositories\ItemQuery;
use App\Seo\ListingMeta;
use PHPUnit\Framework\TestCase;

final class ListingMetaTest extends TestCase
{
    private const CATEGORIES = [['slug' => 'klice', 'name' => 'Klíče']];
    private const BUILDINGS = [['slug' => 'pef', 'name' => 'Provozně ekonomická fakulta (PEF)']];

    private function meta(array $input): ListingMeta
    {
        return ListingMeta::for(ItemQuery::fromArray($input), self::CATEGORIES, self::BUILDINGS);
    }

    public function testSingleTypeFilterIsLandingPage(): void
    {
        $meta = $this->meta(['type' => 'found']);

        $this->assertSame('Nálezy na kampusu ČZU', $meta->heading);
        $this->assertSame('/inzeraty?type=found', $meta->canonical);
        $this->assertFalse($meta->noindex);
    }

    public function testSingleCategoryAndBuildingFilters(): void
    {
        $this->assertSame('Klíče: ztráty a nálezy na ČZU', $this->meta(['category' => 'klice'])->heading);
        $this->assertSame(
            'Ztráty a nálezy: Provozně ekonomická fakulta (PEF)',
            $this->meta(['building' => 'pef'])->heading
        );
    }

    public function testCombinationAndPaginationPointToMainListing(): void
    {
        $combined = $this->meta(['type' => 'found', 'building' => 'pef']);
        $paged = $this->meta(['type' => 'found', 'page' => '2']);

        $this->assertSame('/inzeraty', $combined->canonical);
        $this->assertSame('/inzeraty', $paged->canonical);
        $this->assertSame(ListingMeta::DEFAULT_HEADING, $paged->heading);
    }

    public function testSearchIsNotIndexed(): void
    {
        $meta = $this->meta(['q' => 'klice']);

        $this->assertTrue($meta->noindex);
        $this->assertSame('/inzeraty', $meta->canonical);
    }

    public function testUnknownSlugFallsBackToDefault(): void
    {
        $meta = $this->meta(['category' => 'neexistuje']);

        $this->assertSame(ListingMeta::DEFAULT_HEADING, $meta->heading);
        $this->assertSame('/inzeraty', $meta->canonical);
    }
}
