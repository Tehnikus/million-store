<?php

namespace App\Domain\Media\Actions;

use App\Domain\Media\Services\MediaPathGenerator;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use RuntimeException;

class ProcessStagedImage
{
    public function __construct(
        private readonly MediaPathGenerator $pathGenerator,
        private readonly string $disk = 'public',
    ) {}

    /**
     * @return array{id: string, conversions: array<string, string>}
     */
    public function handle(
        string $stagedPath,
        int $storeId,
        string $type,
        int $entityId,
        string $baseSlug,
        ?string $existingId = null,
    ): array {
        $dimensions = $this->resolveDimensions($storeId, $type);

        if (! Storage::disk($this->disk)->exists($stagedPath)) {
            throw new RuntimeException("Staging file not found: [{$stagedPath}].");
        }

        $extension = pathinfo($stagedPath, PATHINFO_EXTENSION) ?: 'jpg';

        // 1. Move source image from "staging" to permanent storage folder without changes
        $sourcePath = $this->pathGenerator->sourcePath($storeId, $type, $entityId, $baseSlug, $extension);
        Storage::disk($this->disk)->makeDirectory(dirname($sourcePath));
        Storage::disk($this->disk)->move($stagedPath, $sourcePath);

        // 2. Convert images every time
        // TODO Add timestamp to image to skip unchanged images
        $manager = ImageManager::usingDriver(Driver::class);
        $absoluteSourcePath = Storage::disk($this->disk)->path($sourcePath);

        $conversions = ['original' => $sourcePath];

        foreach ($dimensions as $key => $size) {
            // Also ->orient() closure can be used to orient by EXIF data
            $image = $manager->decode($absoluteSourcePath);

            // Different resize types for different image dimensions types
            $image = $key === 'miniature'
                ? $image->cover($size['width'], $size['height'])
                : $image->contain($size['width'], $size['height'], background: '#ffffff');

            $conversionPath = $this->pathGenerator->conversionPath(
                $storeId, $type, $entityId, $baseSlug, $key, $size['width'], $size['height']
            );

            Storage::disk($this->disk)->makeDirectory(dirname($conversionPath));
            // TODO Make format configurable
            Storage::disk($this->disk)->put($conversionPath, (string) $image->encodeUsingFormat(Format::WEBP, quality: 82));

            $conversions[$key] = $conversionPath;
        }

        return [
            'id' => $existingId ?? (string) Str::ulid(),
            'conversions' => $conversions,
        ];
    }

    /**
     * Get image dimensions from StoreSetting by type
     * @param int $storeId
     * @param string $type: 'product', 'category', 'blog', etc. Must be set in store_settings.image_dimensions table
     * @throws InvalidArgumentException
     * @return array
     */
    private function resolveDimensions(int $storeId, string $type): array
    {
        $dimensions = StoreSetting::where('store_id', $storeId)->first()->image_dimensions[$type] ?? null;

        if ($dimensions === null) {
            throw new InvalidArgumentException(
                "Image dimensions for type [{$type}] are not set in store_id = [{$storeId}]."
            );
        }

        return $dimensions;
    }
}