<?php

declare(strict_types=1);

namespace App\Uploads;

final class PhotoProcessor
{
    private const SIZES = ['' => 1000, '-thumb' => 240];
    private const QUALITY = 80;

    public function __construct(private readonly string $publicDir = PUBLIC_PATH)
    {
    }

    public static function newPath(int $itemId): string
    {
        return sprintf('uploads/items/%d-%s', $itemId, bin2hex(random_bytes(4)));
    }

    /**
     * Saves {basePath}.webp and {basePath}-thumb.webp as center-cropped squares.
     */
    public function store(string $sourceFile, string $basePath): void
    {
        $source = @imagecreatefromstring((string) file_get_contents($sourceFile));

        if ($source === false) {
            throw new \RuntimeException('Unreadable image');
        }

        $source = $this->applyOrientation($source, $sourceFile);
        $side = min(imagesx($source), imagesy($source));
        $x = intdiv(imagesx($source) - $side, 2);
        $y = intdiv(imagesy($source) - $side, 2);

        if (!is_dir(dirname("{$this->publicDir}/{$basePath}"))) {
            mkdir(dirname("{$this->publicDir}/{$basePath}"), 0775, true);
        }

        foreach (self::SIZES as $suffix => $size) {
            $target = imagecreatetruecolor($size, $size);
            imagecopyresampled($target, $source, 0, 0, $x, $y, $size, $size, $side, $side);
            imagewebp($target, "{$this->publicDir}/{$basePath}{$suffix}.webp", self::QUALITY);
        }
    }

    /**
     * Only uploaded photos are deleted, seed photos in images/ stay.
     */
    public function delete(?string $basePath): void
    {
        if ($basePath === null || !str_starts_with($basePath, 'uploads/') || str_contains($basePath, '..')) {
            return;
        }

        foreach (array_keys(self::SIZES) as $suffix) {
            @unlink("{$this->publicDir}/{$basePath}{$suffix}.webp");
        }
    }

    private function applyOrientation(\GdImage $image, string $file): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($file)['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle !== 0 ? imagerotate($image, $angle, 0) : $image;
    }
}
