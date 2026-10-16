<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\CategoryRepository;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Fixtures;

final class CategoryRepositoryTest extends DatabaseTestCase
{
    use Fixtures;

    private CategoryRepository $categories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categories = new CategoryRepository(self::$db);
    }

    public function testAllIsSortedByName(): void
    {
        $this->createCategory(['name' => 'Zzz']);
        $this->createCategory(['name' => 'Aaa']);

        $names = array_column($this->categories->all(), 'name');

        $this->assertSame(['Aaa', 'Zzz'], $names);
    }

    public function testFindByIdAndSlug(): void
    {
        $category = $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);

        $this->assertSame('Klíče', $this->categories->find($category['id'])['name']);
        $this->assertSame($category['id'], $this->categories->findBySlug('klice')['id']);
        $this->assertNull($this->categories->find(999999));
        $this->assertNull($this->categories->findBySlug('missing'));
    }

    public function testCreateUpdateDelete(): void
    {
        $id = $this->categories->create(['name' => 'Doplňky', 'slug' => 'doplnky']);
        $this->assertSame('doplnky', $this->categories->find($id)['slug']);

        $this->categories->update($id, ['name' => 'Šperky', 'slug' => 'sperky']);
        $this->assertSame(['Šperky', 'sperky'], [$this->categories->find($id)['name'], $this->categories->find($id)['slug']]);

        $this->categories->delete($id);
        $this->assertNull($this->categories->find($id));
    }

    public function testItemCountCountsReferencingItems(): void
    {
        $category = $this->createCategory();
        $this->assertSame(0, $this->categories->itemCount($category['id']));

        $this->createItem(['category_id' => $category['id']]);
        $this->createItem(['category_id' => $category['id'], 'status' => 'hidden']);

        $this->assertSame(2, $this->categories->itemCount($category['id']));
    }
}
