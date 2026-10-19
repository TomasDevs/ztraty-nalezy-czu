<?php

declare(strict_types=1);

namespace Tests\Unit\Uploads;

use App\Uploads\PhotoProcessor;
use PHPUnit\Framework\TestCase;

final class PhotoProcessorTest extends TestCase
{
    private string $dir;
    private PhotoProcessor $processor;

    protected function setUp(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support');
        }

        $this->dir = sys_get_temp_dir() . '/photo-test-' . uniqid();
        mkdir($this->dir . '/images/items', 0777, true);
        $this->processor = new PhotoProcessor($this->dir);
    }

    protected function tearDown(): void
    {
        if (isset($this->dir)) {
            exec('rm -rf ' . escapeshellarg($this->dir));
        }
    }

    private function sourceImage(int $width = 1200, int $height = 800): string
    {
        $file = "{$this->dir}/source.png";
        imagepng(imagecreatetruecolor($width, $height), $file);

        return $file;
    }

    public function testStoreCreatesFullAndThumbWebp(): void
    {
        $this->processor->store($this->sourceImage(), 'uploads/items/5-abcd1234');

        $full = getimagesize("{$this->dir}/uploads/items/5-abcd1234.webp");
        $thumb = getimagesize("{$this->dir}/uploads/items/5-abcd1234-thumb.webp");

        $this->assertSame([1000, 1000, IMAGETYPE_WEBP], [$full[0], $full[1], $full[2]]);
        $this->assertSame([240, 240, IMAGETYPE_WEBP], [$thumb[0], $thumb[1], $thumb[2]]);
    }

    public function testStoreRejectsUnreadableImage(): void
    {
        $file = "{$this->dir}/broken.png";
        file_put_contents($file, 'not an image');

        $this->expectException(\RuntimeException::class);

        $this->processor->store($file, 'uploads/items/1-aaaaaaaa');
    }

    public function testDeleteRemovesBothSizes(): void
    {
        $this->processor->store($this->sourceImage(), 'uploads/items/5-abcd1234');

        $this->processor->delete('uploads/items/5-abcd1234');

        $this->assertFileDoesNotExist("{$this->dir}/uploads/items/5-abcd1234.webp");
        $this->assertFileDoesNotExist("{$this->dir}/uploads/items/5-abcd1234-thumb.webp");
    }

    public function testDeleteLeavesSeedPhotosAndHandlesNull(): void
    {
        touch("{$this->dir}/images/items/2.webp");

        $this->processor->delete('images/items/2');
        $this->processor->delete('uploads/../images/items/2');
        $this->processor->delete(null);

        $this->assertFileExists("{$this->dir}/images/items/2.webp");
    }

    public function testNewPathHasItemIdAndRandomSuffix(): void
    {
        $this->assertMatchesRegularExpression('#^uploads/items/5-[0-9a-f]{8}$#', PhotoProcessor::newPath(5));
        $this->assertNotSame(PhotoProcessor::newPath(5), PhotoProcessor::newPath(5));
    }
}
