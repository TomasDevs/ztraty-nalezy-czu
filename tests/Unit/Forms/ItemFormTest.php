<?php

declare(strict_types=1);

namespace Tests\Unit\Forms;

use App\Forms\ItemForm;
use PHPUnit\Framework\TestCase;

final class ItemFormTest extends TestCase
{
    private const CATEGORIES = [['id' => 1, 'slug' => 'klice', 'name' => 'Klíče'], ['id' => 2, 'slug' => 'doklady', 'name' => 'Doklady a karty']];
    private const BUILDINGS = [['id' => 7, 'slug' => 'pef', 'name' => 'PEF', 'short' => 'PEF', 'lat' => 50.13116, 'lng' => 14.37322]];

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->tempFiles);
    }

    private function input(array $overrides = []): array
    {
        return [
            'type' => 'found',
            'title' => 'Nalezené klíče',
            'category' => 'klice',
            'building' => 'pef',
            'location' => 'Učebna E127',
            'date' => '2026-10-10',
            'description' => 'Svazek tří klíčů na kroužku s přívěskem.',
            'lat' => '',
            'lng' => '',
            ...$overrides,
        ];
    }

    private function errors(array $overrides = [], ?array $photo = null): array
    {
        $form = ItemForm::fromArray($this->input($overrides), $photo);
        $this->assertFalse($form->validate(self::CATEGORIES, self::BUILDINGS));

        return $form->errors;
    }

    private function upload(string $contents, string $suffix = '.png'): array
    {
        $file = tempnam(sys_get_temp_dir(), 'upl') . $suffix;
        file_put_contents($file, $contents);
        $this->tempFiles[] = $file;

        return ['name' => 'photo' . $suffix, 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => filesize($file)];
    }

    private function png(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(10, 10));

        return (string) ob_get_clean();
    }

    public function testValidInputPasses(): void
    {
        $form = ItemForm::fromArray($this->input());

        $this->assertTrue($form->validate(self::CATEGORIES, self::BUILDINGS));
        $this->assertSame([], $form->errors);
    }

    public function testTypeMustBeKnown(): void
    {
        $this->assertArrayHasKey('type', $this->errors(['type' => 'stolen']));
    }

    public function testTitleLength(): void
    {
        $this->assertArrayHasKey('title', $this->errors(['title' => 'ab']));
        $this->assertArrayHasKey('title', $this->errors(['title' => str_repeat('a', 151)]));
    }

    public function testCategoryAndBuildingMustExist(): void
    {
        $errors = $this->errors(['category' => 'nope', 'building' => 'nope']);

        $this->assertArrayHasKey('category', $errors);
        $this->assertArrayHasKey('building', $errors);
    }

    public function testLocationIsRequiredAndLimited(): void
    {
        $this->assertArrayHasKey('location', $this->errors(['location' => '']));
        $this->assertArrayHasKey('location', $this->errors(['location' => str_repeat('a', 151)]));
    }

    public function testDateMustBeRealAndNotInTheFuture(): void
    {
        $this->assertArrayHasKey('date', $this->errors(['date' => '']));
        $this->assertArrayHasKey('date', $this->errors(['date' => '10. 10. 2026']));
        $this->assertArrayHasKey('date', $this->errors(['date' => '2026-02-30']));
        $this->assertArrayHasKey('date', $this->errors(['date' => date('Y-m-d', strtotime('+1 day'))]));
    }

    public function testTodayIsAllowed(): void
    {
        $form = ItemForm::fromArray($this->input(['date' => date('Y-m-d')]));

        $this->assertTrue($form->validate(self::CATEGORIES, self::BUILDINGS));
    }

    public function testDescriptionLength(): void
    {
        $this->assertArrayHasKey('description', $this->errors(['description' => 'Příliš krátké']));
        $this->assertArrayHasKey('description', $this->errors(['description' => str_repeat('a', 5001)]));
    }

    public function testCoordinatesAreBothOrNone(): void
    {
        $this->assertArrayHasKey('lat', $this->errors(['lat' => '50.131']));
        $this->assertArrayHasKey('lat', $this->errors(['lng' => '14.373']));
        $this->assertArrayHasKey('lat', $this->errors(['lat' => 'abc', 'lng' => 'def']));
    }

    public function testCoordinatesMustBeOnCampus(): void
    {
        $this->assertArrayHasKey('lat', $this->errors(['lat' => '48.2', 'lng' => '16.37']));
    }

    public function testToDataUsesGivenCoordinates(): void
    {
        $form = ItemForm::fromArray($this->input(['lat' => '50.1312', 'lng' => '14.3731']));
        $form->validate(self::CATEGORIES, self::BUILDINGS);

        $data = $form->toData();

        $this->assertSame(50.1312, $data['lat']);
        $this->assertSame(14.3731, $data['lng']);
        $this->assertSame(1, $data['category_id']);
        $this->assertSame(7, $data['building_id']);
        $this->assertSame(['type', 'title', 'category_id', 'building_id', 'location', 'date', 'description', 'lat', 'lng'], array_keys($data));
    }

    public function testToDataFallsBackToBuildingCoordinates(): void
    {
        $form = ItemForm::fromArray($this->input());
        $form->validate(self::CATEGORIES, self::BUILDINGS);

        $data = $form->toData();

        $this->assertSame(50.13116, $data['lat']);
        $this->assertSame(14.37322, $data['lng']);
    }

    public function testFromItemPrefillsForm(): void
    {
        $form = ItemForm::fromItem([
            'type' => 'lost', 'title' => 'Klíče', 'category' => ['slug' => 'klice'], 'building' => ['slug' => 'pef'],
            'location' => 'Aula', 'date' => '2026-10-01', 'description' => 'Popis', 'lat' => 50.13, 'lng' => 14.37,
        ]);

        $this->assertSame('klice', $form->category);
        $this->assertSame('50.13', $form->lat);
        $this->assertSame('Klíče', $form->value('title'));
    }

    public function testMissingPhotoIsFine(): void
    {
        $form = ItemForm::fromArray($this->input(), ['error' => UPLOAD_ERR_NO_FILE, 'tmp_name' => '', 'size' => 0]);

        $this->assertTrue($form->validate(self::CATEGORIES, self::BUILDINGS));
        $this->assertFalse($form->hasPhoto());
    }

    public function testValidPhotoIsAccepted(): void
    {
        $form = ItemForm::fromArray($this->input(), $this->upload($this->png()));

        $this->assertTrue($form->validate(self::CATEGORIES, self::BUILDINGS));
        $this->assertTrue($form->hasPhoto());
    }

    public function testPhotoWithWrongTypeIsRejected(): void
    {
        $this->assertArrayHasKey('photo', $this->errors([], $this->upload('just text', '.png')));
    }

    public function testOversizedPhotoIsRejected(): void
    {
        $photo = $this->upload($this->png());
        $photo['size'] = ItemForm::MAX_PHOTO_BYTES + 1;

        $this->assertArrayHasKey('photo', $this->errors([], $photo));
    }

    public function testUploadErrorIsReported(): void
    {
        $this->assertArrayHasKey('photo', $this->errors([], ['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '', 'size' => 0]));
    }

    public function testDocumentsDoNotAllowPhotos(): void
    {
        $this->assertTrue(ItemForm::fromArray($this->input())->allowsPhoto());
        $this->assertFalse(ItemForm::fromArray($this->input(['category' => 'doklady']))->allowsPhoto());
    }

    public function testDocumentsNeverHavePhotos(): void
    {
        $form = ItemForm::fromArray($this->input(['category' => 'doklady']), $this->upload($this->png()));

        $this->assertTrue($form->validate(self::CATEGORIES, self::BUILDINGS));
        $this->assertFalse($form->hasPhoto());
    }
}
