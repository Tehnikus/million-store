<?php

namespace App\Domain\Media\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaPathGenerator
{
    private const int BUCKET_SIZE = 100;

    public function __construct(
        private readonly string $disk = 'public',
    ) {}

    public function resolveBasePath(int $storeId, string $type, string $originalFilename): string
    {
        $bucketDir = $this->resolveBucketDir($storeId, $type);
        $slug = $this->resolveUniqueSlug($bucketDir, $originalFilename);

        return "{$bucketDir}/{$slug}";
    }

    private function resolveBucketDir(int $storeId, string $type): string
    {
        $baseDir = "{$storeId}/{$type}";
        $directories = Storage::disk($this->disk)->directories($baseDir);

        if (empty($directories)) {
            return "{$baseDir}/0";
        }

        $lastBucket = collect($directories)
            ->map(fn (string $dir) => (int) basename($dir))
            ->sort()
            ->last();

        $filesInLastBucket = Storage::disk($this->disk)->files("{$baseDir}/{$lastBucket}");

        $bucket = count($filesInLastBucket) >= self::BUCKET_SIZE
            ? $lastBucket + 1
            : $lastBucket;

        return "{$baseDir}/{$bucket}";
    }

    private function resolveUniqueSlug(string $bucketDir, string $originalFilename): string
    {
        $slug = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME));

        if ($slug === '') {
            $slug = 'image';
        }

        $candidate = $slug;
        $counter = 1;

        while ($this->slugIsTaken($bucketDir, $candidate)) {
            $candidate = "{$slug}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    private function slugIsTaken(string $bucketDir, string $slug): bool
    {
        $existingFiles = Storage::disk($this->disk)->files($bucketDir);

        foreach ($existingFiles as $file) {
            $basename = pathinfo($file, PATHINFO_FILENAME);

            if ($basename === $slug || str_starts_with($basename, "{$slug}_")) {
                return true;
            }
        }

        return false;
    }
}