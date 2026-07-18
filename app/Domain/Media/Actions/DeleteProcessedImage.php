<?php

namespace App\Domain\Media\Actions;

use Illuminate\Support\Facades\Storage;

class DeleteProcessedImage
{
    public function __construct(
        private readonly string $disk = 'public',
    ) {}

    /**
     * Delete unused images
     * @param array{conversions: array<string, string>} $imageEntry
     */
    public function handle(array $imageEntry): void
    {
        $paths = array_values($imageEntry['conversions'] ?? []);

        if ($paths !== []) {
            Storage::disk($this->disk)->delete($paths);
        }
    }
}