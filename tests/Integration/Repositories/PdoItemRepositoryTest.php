<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\ItemQuery;
use App\Repositories\PdoItemRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class PdoItemRepositoryTest extends DatabaseTestCase
{
    use Fixtures;

    private PdoItemRepository $items;

    protected function setUp(): void
    {
        parent::setUp();
        $this->items = new PdoItemRepository(self::$db);
        self::$db->exec('DELETE FROM items');
    }

    public function testSearchWithoutFiltersHidesHiddenAndSortsNewestFirst(): void
    {
        $old = $this->createItem(['date' => '2026-10-01']);
        $new = $this->createItem(['date' => '2026-10-05', 'status' => 'returned']);
        $this->createItem(['date' => '2026-10-09', 'status' => 'hidden']);

        $result = $this->items->search(new ItemQuery());

        $this->assertSame([$new['id'], $old['id']], array_column($result['items'], 'id'));
        $this->assertSame(2, $result['total']);
    }

    public function testEqualDatesAreSortedByNewestId(): void
    {
        $first = $this->createItem(['date' => '2026-10-01']);
        $second = $this->createItem(['date' => '2026-10-01']);

        $ids = array_column($this->items->all(), 'id');

        $this->assertSame([$second['id'], $first['id']], $ids);
    }

    public function testFiltersByTypeCategoryAndBuilding(): void
    {
        $category = $this->createCategory();
        $building = $this->createBuilding();
        $match = $this->createItem(['type' => 'found', 'category_id' => $category['id'], 'building_id' => $building['id']]);
        $this->createItem(['type' => 'lost', 'category_id' => $category['id'], 'building_id' => $building['id']]);
        $this->createItem(['type' => 'found', 'building_id' => $building['id']]);
        $this->createItem(['type' => 'found', 'category_id' => $category['id']]);

        $query = new ItemQuery(type: 'found', category: $category['slug'], building: $building['slug']);

        $this->assertSame([$match['id']], array_column($this->items->search($query)['items'], 'id'));
        $this->assertSame(3, $this->items->search(new ItemQuery(building: $building['slug']))['total']);
    }

    public function testSearchIgnoresDiacriticsInTitleDescriptionAndLocation(): void
    {
        $byTitle = $this->createItem(['title' => 'Ztracené Klíče']);
        $byDescription = $this->createItem(['description' => 'Svazek klíčů s přívěskem na kroužku.']);
        $byLocation = $this->createItem(['location' => 'Učebna u klíčové místnosti']);
        $this->createItem(['title' => 'Batoh']);

        $this->assertSame([$byTitle['id']], $this->sortedIds($this->items->search(new ItemQuery(q: 'klice'))['items']));

        $expected = [$byTitle['id'], $byDescription['id'], $byLocation['id']];
        sort($expected);
        $this->assertSame($expected, $this->sortedIds($this->items->search(new ItemQuery(q: 'KLÍČ'))['items']));
    }

    public function testSearchTreatsLikeWildcardsLiterally(): void
    {
        $match = $this->createItem(['description' => 'Sleva 50% na všechno, dlouhý popis.']);
        $this->createItem(['description' => 'Obyčejný popis bez procent, dlouhý.']);

        $ids = array_column($this->items->search(new ItemQuery(q: '50%'))['items'], 'id');

        $this->assertSame([$match['id']], $ids);
        $this->assertSame(0, $this->items->search(new ItemQuery(q: '_'))['total']);
    }

    public function testPaginationAndMarkers(): void
    {
        for ($i = 0; $i < ItemQuery::PER_PAGE + 1; $i++) {
            $this->createItem(['title' => "Item {$i}"]);
        }

        $result = $this->items->search(new ItemQuery(page: 2));

        $this->assertCount(1, $result['items']);
        $this->assertSame(ItemQuery::PER_PAGE + 1, $result['total']);
        $this->assertCount(ItemQuery::PER_PAGE + 1, $result['markers']);
        $this->assertSame(['lat', 'lng', 'title', 'type', 'url'], array_keys($result['markers'][0]));
    }

    public function testFindReturnsHydratedItemInAnyStatus(): void
    {
        $category = $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);
        $building = $this->createBuilding(['slug' => 'pef', 'short' => 'PEF']);
        $author = $this->createUser();
        $created = $this->createItem([
            'user_id' => $author['id'],
            'category_id' => $category['id'],
            'building_id' => $building['id'],
            'status' => 'hidden',
            'title' => 'Ztracené klíče',
            'photo' => 'images/items/2',
        ]);

        $item = $this->items->find($created['id']);

        $this->assertSame($created['id'], $item['id']);
        $this->assertSame($author['id'], $item['user_id']);
        $this->assertSame('hidden', $item['status']);
        $this->assertSame('ztracene-klice', $item['slug']);
        $this->assertSame('2026-10-01', $item['date']);
        $this->assertSame(50.13, $item['lat']);
        $this->assertSame('images/items/2', $item['photo']);
        $this->assertSame(['id' => $category['id'], 'slug' => 'klice', 'name' => 'Klíče'], $item['category']);
        $this->assertSame('PEF', $item['building']['short']);
        $this->assertSame(14.373, $item['building']['lng']);
        $this->assertNull($this->items->find(999999));
        $this->assertNull($this->createItem()['photo']);
    }

    public function testLatestReturnsNewestVisibleItems(): void
    {
        $this->createItem(['date' => '2026-10-01']);
        $b = $this->createItem(['date' => '2026-10-03']);
        $c = $this->createItem(['date' => '2026-10-02']);
        $this->createItem(['date' => '2026-10-09', 'status' => 'hidden']);

        $this->assertSame([$b['id'], $c['id']], array_column($this->items->latest(2), 'id'));
    }

    public function testRelatedPrefersSameBuildingThenSameCategory(): void
    {
        $building = $this->createBuilding();
        $category = $this->createCategory();
        $base = $this->createItem(['building_id' => $building['id'], 'category_id' => $category['id']]);
        $sameCategory = $this->createItem(['category_id' => $category['id'], 'date' => '2026-10-09']);
        $sameBuilding = $this->createItem(['building_id' => $building['id'], 'date' => '2026-10-02']);
        $this->createItem(['building_id' => $building['id'], 'status' => 'returned']);
        $this->createItem(['building_id' => $building['id'], 'status' => 'hidden']);
        $this->createItem();

        $related = $this->items->related($base, 5);

        $this->assertSame([$sameBuilding['id'], $sameCategory['id']], array_column($related, 'id'));
        $this->assertCount(1, $this->items->related($base, 1));
    }

    public function testStatsCountOnlyActiveLostAndFoundPlusReturned(): void
    {
        $this->createItem(['type' => 'found']);
        $this->createItem(['type' => 'found']);
        $this->createItem(['type' => 'lost']);
        $this->createItem(['type' => 'lost', 'status' => 'returned']);
        $this->createItem(['type' => 'found', 'status' => 'hidden']);

        $this->assertSame(['found' => 2, 'lost' => 1, 'returned' => 1], $this->items->stats());
    }

    public function testCreateGeneratesSlugAndSearchText(): void
    {
        $item = $this->createItem(['title' => 'Černá Peněženka!']);

        $this->assertSame('cerna-penezenka', $item['slug']);
        $this->assertSame(
            'cerna penezenka',
            substr((string) self::$db->query("SELECT search_text FROM items WHERE id = {$item['id']}")->fetchColumn(), 0, 15)
        );
    }

    public function testCreateAcceptsExplicitId(): void
    {
        $item = $this->createItem(['id' => 4242]);

        $this->assertSame(4242, $item['id']);
    }

    public function testUpdateRegeneratesSlugAndSearchText(): void
    {
        $item = $this->createItem(['title' => 'Old title']);
        $category = $this->createCategory();

        $this->items->update($item['id'], [...$item, 'title' => 'Nový název', 'category_id' => $category['id']]);

        $updated = $this->items->find($item['id']);
        $this->assertSame('novy-nazev', $updated['slug']);
        $this->assertSame($category['slug'], $updated['category']['slug']);
        $this->assertCount(1, $this->items->search(new ItemQuery(q: 'novy nazev'))['items']);
    }

    public function testSetPhotoReplacesAndRemoves(): void
    {
        $item = $this->createItem();

        $this->items->setPhoto($item['id'], 'uploads/items/1-aaaaaaaa');
        $this->items->setPhoto($item['id'], 'uploads/items/1-bbbbbbbb');
        $this->assertSame('uploads/items/1-bbbbbbbb', $this->items->find($item['id'])['photo']);

        $this->items->setPhoto($item['id'], null);
        $this->assertNull($this->items->find($item['id'])['photo']);
    }

    public function testSetStatusAndDelete(): void
    {
        $item = $this->createItem();

        $this->items->setStatus($item['id'], 'returned');
        $this->assertSame('returned', $this->items->find($item['id'])['status']);

        $this->items->delete($item['id']);
        $this->assertNull($this->items->find($item['id']));
    }

    public function testForUserReturnsOnlyOwnItemsInEveryStatus(): void
    {
        $user = $this->createUser();
        $active = $this->createItem(['user_id' => $user['id'], 'date' => '2026-10-01']);
        $hidden = $this->createItem(['user_id' => $user['id'], 'date' => '2026-10-02', 'status' => 'hidden']);
        $this->createItem();

        $this->assertSame([$hidden['id'], $active['id']], array_column($this->items->forUser($user['id']), 'id'));
    }

    public function testForAdminFiltersByStatusAndIncludesAuthorEmail(): void
    {
        $author = $this->createUser(['email' => 'author@example.com']);
        $hidden = $this->createItem(['user_id' => $author['id'], 'status' => 'hidden']);
        $this->createItem();

        $hiddenOnly = $this->items->forAdmin('hidden');

        $this->assertSame([$hidden['id']], array_column($hiddenOnly, 'id'));
        $this->assertSame('author@example.com', $hiddenOnly[0]['author_email']);
        $this->assertCount(2, $this->items->forAdmin());
    }

    private function sortedIds(array $items): array
    {
        $ids = array_column($items, 'id');
        sort($ids);

        return $ids;
    }
}
