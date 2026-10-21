<?php

namespace App\Domain\Media\Actions;

use App\Domain\Media\ImageType;
use App\Domain\Media\Services\MediaPathGenerator;
use App\Models\Store\StoreSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use RuntimeException;

class ProcessStagedImage
{
    public function __construct(
        private readonly MediaPathGenerator $pathGenerator,
        private readonly string $disk = 'public',
    ) {
    }

    /**
     * Moves a staged upload to permanent storage and creates its conversions.
     * Folder bucketing (100 entities per folder) comes from $entityId, see MediaPathGenerator.
     *
     * @return array{src: string, conversions: array<string, string>}
     */
    public function handle(
        string $stagedPath,
        int $storeId,
        ImageType $type,
        int $entityId,
        string $baseSlug,
    ): array {
        // Store's own sizes where saved, defaults from the enum for the rest.
        // first()?->column, not value('column'): value() skips the model's array cast and returns raw JSON.
        $saved = StoreSettings::where('store_id', $storeId)->first()?->image_dimensions ?? [];
        $dimensions = $type->dimensions($saved);

        if (!Storage::disk($this->disk)->exists($stagedPath)) {
            throw new RuntimeException("Staging file not found: [{$stagedPath}].");
        }

        $extension = pathinfo($stagedPath, PATHINFO_EXTENSION) ?: 'jpg';

        // A short random suffix instead of the image's position in the list:
        //  - a new image can't take the name of an existing one and overwrite its file
        //  - an edited image gets a new URL, so browsers and CDNs don't serve the old cached file
        $baseName = $baseSlug . '-' . Str::lower(Str::random(6));

        // 1. Move source image from "staging" to permanent storage folder without changes
        $sourcePath = $this->pathGenerator->sourcePath($storeId, $type->value, $entityId, $baseName, $extension);
        Storage::disk($this->disk)->makeDirectory(dirname($sourcePath));
        Storage::disk($this->disk)->move($stagedPath, $sourcePath);

        // 2. Create conversions
        $manager = ImageManager::usingDriver(Driver::class);
        $absoluteSourcePath = Storage::disk($this->disk)->path($sourcePath);

        $conversions = [];

        foreach ($type->conversions() as $conversion) {
            $size = $dimensions[$conversion->value];

            // Also ->orient() closure can be used to orient by EXIF data
            $image = $manager->decode($absoluteSourcePath);

            // The fitting rule belongs to the conversion (see ImageConversion::fit())
            $image = $conversion->fit() === 'cover'
                ? $image->cover($size['width'], $size['height'])
                : $image->contain($size['width'], $size['height'], background: '#ffffff');

            $conversionPath = $this->pathGenerator->conversionPath(
                $storeId,
                $type->value,
                $entityId,
                $baseName,
                $conversion->value,
                $size['width'],
                $size['height']
            );

            Storage::disk($this->disk)->makeDirectory(dirname($conversionPath));
            // TODO Make format configurable
            Storage::disk($this->disk)->put($conversionPath, (string) $image->encodeUsingFormat(Format::WEBP, quality: 82));

            $conversions[$conversion->value] = $conversionPath;
        }

        return [
            'src' => $sourcePath,
            'conversions' => $conversions,
        ];
    }
}