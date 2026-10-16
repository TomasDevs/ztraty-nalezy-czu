<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class ItemManagementTest extends FeatureTestCase
{
    private array $category;
    private array $building;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = $this->createCategory(['slug' => 'klice', 'name' => 'Klíče']);
        $this->building = $this->createBuilding(['slug' => 'pef', 'name' => 'PEF', 'short' => 'PEF', 'lat' => 50.13116, 'lng' => 14.37322]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', $this->tempFiles);
        parent::tearDown();
    }

    private function itemData(array $overrides = []): array
    {
        return [
            'type' => 'found',
            'title' => 'Nalezené klíče',
            'category' => $this->category['slug'],
            'building' => $this->building['slug'],
            'location' => 'Učebna E127',
            'date' => '2026-10-10',
            'description' => 'Svazek tří klíčů na kroužku s přívěskem.',
            ...$overrides,
        ];
    }

    private function photoUpload(): array
    {
        $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
        imagepng(imagecreatetruecolor(300, 200), $file);
        $this->tempFiles[] = $file;

        return ['photo' => ['name' => 'photo.png', 'type' => 'image/png', 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => filesize($file)]];
    }

    private function ownItem(array $overrides = []): array
    {
        $user = $this->createUser();
        $this->actingAs($user);

        return $this->createItem([
            'user_id' => $user['id'],
            'category_id' => $this->category['id'],
            'building_id' => $this->building['id'],
            'title' => 'Moje klíče',
            ...$overrides,
        ]);
    }

    private function lastItem(): array
    {
        return App::items()->all()[0];
    }

    public function testGuestSeesLoginPromptInsteadOfForm(): void
    {
        $this->get('/nahlasit', ['type' => 'found'])
            ->assertOk()
            ->assertSee('/prihlaseni?zpet=')
            ->assertDontSee('name="title"');
    }

    public function testGuestCannotPost(): void
    {
        $this->post('/nahlasit', $this->itemData())->assertRedirect('/prihlaseni?zpet=%2Fnahlasit');
    }

    public function testLoggedInUserSeesForm(): void
    {
        $this->actingAs($this->createUser());

        $this->get('/nahlasit', ['type' => 'found'])
            ->assertOk()
            ->assertSee('name="title"')
            ->assertSee('enctype="multipart/form-data"')
            ->assertSee('name="_token"');
    }

    public function testValidPostCreatesItemOwnedByUser(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->post('/nahlasit', $this->itemData(['lat' => '50.1312', 'lng' => '14.3731']));

        $item = $this->lastItem();
        $this->assertSame($user['id'], $item['user_id']);
        $this->assertSame('Nalezené klíče', $item['title']);
        $this->assertSame(50.1312, $item['lat']);
        $response->assertRedirect("/inzerat/{$item['id']}/nalezene-klice");
        $this->get("/inzerat/{$item['id']}/nalezene-klice")->assertSee('Inzerát je zveřejněný.');
    }

    public function testMissingCoordinatesFallBackToBuilding(): void
    {
        $this->actingAs($this->createUser());

        $this->post('/nahlasit', $this->itemData());

        $item = $this->lastItem();
        $this->assertSame([50.13116, 14.37322], [$item['lat'], $item['lng']]);
    }

    public function testInvalidPostShowsErrorsAndStoresNothing(): void
    {
        $this->actingAs($this->createUser());
        $before = count(App::items()->all());

        $this->post('/nahlasit', $this->itemData(['title' => '', 'description' => 'krátké']))
            ->assertStatus(422)
            ->assertSee('Vyplňte název.')
            ->assertSee('Popis musí mít aspoň 20 znaků.');

        $this->assertCount($before, App::items()->all());
    }

    public function testUploadedPhotoIsStoredAsWebp(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support');
        }

        $this->actingAs($this->createUser());

        $this->post('/nahlasit', $this->itemData(), files: $this->photoUpload());

        $item = $this->lastItem();
        $this->assertMatchesRegularExpression('#^uploads/items/\d+-[0-9a-f]{8}$#', $item['photo']);
        $this->assertFileExists("{$this->publicDir}/{$item['photo']}.webp");
        $this->assertFileExists("{$this->publicDir}/{$item['photo']}-thumb.webp");
    }

    public function testDocumentsDoNotStorePhotos(): void
    {
        $documents = $this->createCategory(['slug' => 'doklady', 'name' => 'Doklady']);
        $this->actingAs($this->createUser());

        $this->post('/nahlasit', $this->itemData(['category' => $documents['slug']]), files: $this->photoUpload());

        $this->assertNull($this->lastItem()['photo']);
    }

    public function testAuthorSeesEditFormAndCanUpdate(): void
    {
        $item = $this->ownItem();

        $this->get("/ucet/inzeraty/{$item['id']}/upravit")->assertOk()->assertSee('value="Moje klíče"');

        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData(['title' => 'Nový název']))
            ->assertRedirect("/inzerat/{$item['id']}/novy-nazev");
        $this->assertSame('Nový název', App::items()->find($item['id'])['title']);
    }

    public function testInvalidUpdateKeepsOldData(): void
    {
        $item = $this->ownItem();

        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData(['title' => 'x']))->assertStatus(422);

        $this->assertSame('Moje klíče', App::items()->find($item['id'])['title']);
    }

    public function testNewPhotoReplacesOldUploadedPhoto(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support');
        }

        $item = $this->ownItem();
        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData(), files: $this->photoUpload());
        $first = App::items()->find($item['id'])['photo'];

        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData(), files: $this->photoUpload());

        $second = App::items()->find($item['id'])['photo'];
        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist("{$this->publicDir}/{$first}.webp");
        $this->assertFileExists("{$this->publicDir}/{$second}.webp");
    }

    public function testStrangerCannotEditReturnOrDelete(): void
    {
        $item = $this->createItem(['category_id' => $this->category['id'], 'building_id' => $this->building['id']]);
        $this->actingAs($this->createUser());

        $this->get("/ucet/inzeraty/{$item['id']}/upravit")->assertStatus(403);
        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData())->assertStatus(403);
        $this->post("/ucet/inzeraty/{$item['id']}/vraceno")->assertStatus(403);
        $this->post("/ucet/inzeraty/{$item['id']}/smazat")->assertStatus(403);
        $this->assertNotNull(App::items()->find($item['id']));
    }

    public function testGuestIsSentToLoginFromManagementUrls(): void
    {
        $item = $this->createItem();

        $this->get("/ucet/inzeraty/{$item['id']}/upravit")
            ->assertRedirect('/prihlaseni?zpet=' . rawurlencode("/ucet/inzeraty/{$item['id']}/upravit"));
    }

    public function testAdminCanManageForeignItems(): void
    {
        $item = $this->createItem(['category_id' => $this->category['id'], 'building_id' => $this->building['id']]);
        $this->actingAs($this->createUser(['role' => 'admin']));

        $this->get("/ucet/inzeraty/{$item['id']}/upravit")->assertOk();
        $this->post("/ucet/inzeraty/{$item['id']}/vraceno")->assertRedirect(item_path($item));
        $this->assertSame('returned', App::items()->find($item['id'])['status']);
    }

    public function testMarkReturnedShowsReturnedNotice(): void
    {
        $item = $this->ownItem(['title' => 'Moje klíče']);

        $this->post("/ucet/inzeraty/{$item['id']}/vraceno")->assertRedirect("/inzerat/{$item['id']}/moje-klice");

        $this->get("/inzerat/{$item['id']}/moje-klice")->assertSee('Věc už je zpátky u majitele');
    }

    public function testDeleteRemovesItemAndPhoto(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support');
        }

        $item = $this->ownItem();
        $this->post("/ucet/inzeraty/{$item['id']}/upravit", $this->itemData(), files: $this->photoUpload());
        $photo = App::items()->find($item['id'])['photo'];

        $this->post("/ucet/inzeraty/{$item['id']}/smazat")->assertRedirect('/ucet');

        $this->assertNull(App::items()->find($item['id']));
        $this->assertFileDoesNotExist("{$this->publicDir}/{$photo}.webp");
    }

    public function testHiddenItemIsNotFoundForGuestsButVisibleToAuthor(): void
    {
        $item = $this->ownItem(['title' => 'Skryté klíče', 'status' => 'hidden']);
        $path = "/inzerat/{$item['id']}/skryte-klice";

        $this->get($path)->assertOk()->assertSee('Inzerát je skrytý administrátorem');

        $this->asGuest();
        $this->get($path)->assertStatus(404);
    }

    public function testManagePanelIsOnlyForManagers(): void
    {
        $item = $this->createItem(['title' => 'Cizí klíče', 'category_id' => $this->category['id'], 'building_id' => $this->building['id']]);
        $path = "/inzerat/{$item['id']}/cizi-klice";

        $this->get($path)->assertDontSee('Správa inzerátu');

        $this->actingAs($this->createUser());
        $this->get($path)->assertDontSee('Správa inzerátu');
    }

    public function testAuthorSeesManagePanel(): void
    {
        $item = $this->ownItem(['title' => 'Moje klíče']);

        $this->get("/inzerat/{$item['id']}/moje-klice")
            ->assertSee('Správa inzerátu')
            ->assertSee("/ucet/inzeraty/{$item['id']}/upravit")
            ->assertSee('data-confirm');
    }
}
