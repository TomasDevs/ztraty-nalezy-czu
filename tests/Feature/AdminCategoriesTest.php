<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class AdminCategoriesTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser(['role' => 'admin']));
    }

    public function testListsCategoriesWithAddForm(): void
    {
        $this->createCategory(['name' => 'Elektronika', 'slug' => 'elektronika']);

        $this->get('/admin/kategorie')
            ->assertOk()
            ->assertSee('value="Elektronika"')
            ->assertSee('action="/admin/kategorie"')
            ->assertSee('<meta name="robots" content="noindex">');
    }

    public function testCreateAddsCategoryWithGeneratedSlug(): void
    {
        $this->post('/admin/kategorie', ['name' => 'Sportovní vybavení'])->assertRedirect('/admin/kategorie');

        $this->assertNotNull(App::categories()->findBySlug('sportovni-vybaveni'));
        $this->get('/admin/kategorie')->assertSee('Kategorie je přidaná.');
    }

    public function testInvalidCreateShowsFirstErrorAndStoresNothing(): void
    {
        $before = count(App::categories()->all());

        $this->post('/admin/kategorie', ['name' => 'a'])->assertRedirect('/admin/kategorie');

        $this->assertCount($before, App::categories()->all());
        $this->get('/admin/kategorie')->assertSee('Název musí mít aspoň 2 znaky.');
    }

    public function testDuplicateSlugIsRejected(): void
    {
        $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);
        $before = count(App::categories()->all());

        $this->post('/admin/kategorie', ['name' => 'Jiné klíče', 'slug' => 'klice']);

        $this->assertCount($before, App::categories()->all());
        $this->get('/admin/kategorie')->assertSee('Tato adresa už se používá.');
    }

    public function testUpdateChangesNameAndSlug(): void
    {
        $category = $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);

        $this->post("/admin/kategorie/{$category['id']}", ['name' => 'Klíče a karty', 'slug' => 'klice-a-karty'])
            ->assertRedirect('/admin/kategorie');

        $this->assertSame('Klíče a karty', App::categories()->find($category['id'])['name']);
        $this->assertSame('klice-a-karty', App::categories()->find($category['id'])['slug']);
    }

    public function testUpdateMayKeepOwnSlug(): void
    {
        $category = $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);

        $this->post("/admin/kategorie/{$category['id']}", ['name' => 'Nový název', 'slug' => 'klice']);

        $this->assertSame('Nový název', App::categories()->find($category['id'])['name']);
    }

    public function testUpdateCannotTakeAnotherSlug(): void
    {
        $this->createCategory(['slug' => 'klice']);
        $other = $this->createCategory(['slug' => 'elektronika', 'name' => 'Elektronika']);

        $this->post("/admin/kategorie/{$other['id']}", ['name' => 'Elektronika', 'slug' => 'klice']);

        $this->assertSame('elektronika', App::categories()->find($other['id'])['slug']);
    }

    public function testUnusedCategoryCanBeDeleted(): void
    {
        $category = $this->createCategory();

        $this->post("/admin/kategorie/{$category['id']}/smazat")->assertRedirect('/admin/kategorie');

        $this->assertNull(App::categories()->find($category['id']));
    }

    public function testUsedCategoryCannotBeDeleted(): void
    {
        $category = $this->createCategory();
        $this->createItem(['category_id' => $category['id']]);
        $this->createItem(['category_id' => $category['id']]);

        $this->post("/admin/kategorie/{$category['id']}/smazat")->assertRedirect('/admin/kategorie');

        $this->assertNotNull(App::categories()->find($category['id']));
        $this->get('/admin/kategorie')->assertSee('Kategorii nejde smazat, obsahuje inzeráty: 2.');
    }

    public function testUnknownCategoryIsNotFound(): void
    {
        $this->post('/admin/kategorie/999999', ['name' => 'Něco'])->assertStatus(404);
        $this->post('/admin/kategorie/999999/smazat')->assertStatus(404);
    }
}
