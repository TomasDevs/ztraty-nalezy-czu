<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        $_ENV['BASE_PATH'] = '';
    }

    public function testFoldRemovesDiacriticsAndLowercases(): void
    {
        $this->assertSame('klice zlutoucky', fold('Klíče ŽLUŤOUČKÝ'));
    }

    public function testSlugify(): void
    {
        $this->assertSame('ztracene-klice-cerna-klicenka', slugify('Ztracené klíče, černá klíčenka!'));
    }

    public function testPluralForm(): void
    {
        $forms = array_map(fn (int $n) => plural_form($n, 'one', 'few', 'many'), [0, 1, 2, 4, 5]);

        $this->assertSame(['many', 'one', 'few', 'few', 'many'], $forms);
    }

    public function testUrlSkipsEmptyQueryValues(): void
    {
        $this->assertSame('/inzeraty?type=lost', url('/inzeraty', ['type' => 'lost', 'q' => '']));
    }

    public function testUrlUsesBasePath(): void
    {
        $_ENV['BASE_PATH'] = '/~login/ztraty';

        $this->assertSame('/~login/ztraty/inzeraty', url('/inzeraty'));
    }

    public function testPrivateItemsHaveNoPhoto(): void
    {
        $item = ['photo' => 'images/items/2', 'category' => ['slug' => 'doklady']];

        $this->assertNull(item_photo_path($item));
    }

    public function testPhotoPathHasThumbVariant(): void
    {
        $item = ['photo' => 'images/items/2', 'category' => ['slug' => 'klice']];

        $this->assertSame('/images/items/2.webp', item_photo_path($item));
        $this->assertSame('/images/items/2-thumb.webp', item_photo_path($item, thumb: true));
    }

    public function testItemCodeIsPadded(): void
    {
        $this->assertSame('#007', item_code(['id' => 7]));
    }
}
