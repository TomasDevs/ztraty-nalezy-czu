<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\ItemQuery;
use PHPUnit\Framework\TestCase;

final class ItemQueryTest extends TestCase
{
    public function testInvalidTypeBecomesNull(): void
    {
        $this->assertNull(ItemQuery::fromArray(['type' => 'stolen'])->type);
        $this->assertSame('lost', ItemQuery::fromArray(['type' => 'lost'])->type);
    }

    public function testBlankAndNonStringValuesBecomeNull(): void
    {
        $query = ItemQuery::fromArray(['q' => '  ', 'category' => ['a']]);

        $this->assertNull($query->q);
        $this->assertNull($query->category);
    }

    public function testPageIsAtLeastOne(): void
    {
        $this->assertSame(1, ItemQuery::fromArray(['page' => '-4'])->page);
    }

    public function testOffset(): void
    {
        $this->assertSame(2 * ItemQuery::PER_PAGE, ItemQuery::fromArray(['page' => '3'])->offset());
    }
}
