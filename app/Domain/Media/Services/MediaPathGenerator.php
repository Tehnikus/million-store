<?php

namespace App\Domain\Media\Services;

class MediaPathGenerator
{
    private const int BUCKET_SIZE = 100;

    /**
     * Where to store source images
     * @param int $storeId
     * @param string $type
     * @param int $entityId
     * @param string $baseName
     * @param string $extension
     * @return string
     */
    public function sourcePath(int $storeId, string $type, int $entityId, string $baseName, string $extension): string
    {
        // Create 100 child directories max, then make next bucket
        $bucket = intdiv($entityId, self::BUCKET_SIZE);

        return "{$storeId}/images/{$type}/sources/{$bucket}/{$entityId}_{$baseName}.{$extension}";
    }

    /**
     * Where to store conversions
     * @param int $storeId
     * @param string $type
     * @param int $entityId
     * @param string $baseName
     * @param string $conversionKey
     * @param int $width
     * @param int $height
     * @param string $extension
     * @return string
     */
    public function conversionPath(
        int $storeId,
        string $type,
        int $entityId,
        string $baseName,
        string $conversionKey,
        int $width,
        int $height,
        string $extension = 'webp' // TODO use when format is passed as param
    ): string {
        $bucket = intdiv($entityId, self::BUCKET_SIZE);

        return "{$storeId}/images/{$type}/conversions/{$bucket}/{$entityId}_{$baseName}_{$conversionKey}_{$width}x{$height}.webp";
    }
}