<?php

namespace App\Domain\Media\Concerns;
use illuminate\Support\Str;
use App\Domain\Media\Actions\DeleteProcessedImage;
use App\Domain\Media\Actions\ProcessStagedImage;
use App\Models\Language;
use App\Models\StoreLanguage;

/**
 * Converts images automatically to WEBP
 * The model must declare:
 *  public function imageColumns(): array
 *  {
 *      return [
 *          'images' => [
 *              'type'        => 'blog', // Image type. Sets which dimensions to choose from StoreSetting and what directory to store images in
 *              'slug_source' => 'name', // Translatable field to take converted image names from. Will be slugged
 *          ],
 *      ];
 *  }
 * Also store_id column must be present in DB table to resolve default language and make a slug name of image from translatable field in defaul language 
 */
trait HasProcessedImages
{
    protected array $pendingImageDeletions = [];

    public static function bootHasProcessedImages(): void
    {
        static::saving(function (self $model) {
            // Catch livewire upload race bug
            // TODO Make workaround for livewire bug
            \Log::debug('images raw state at saving()', [
                'model'     => static::class,
                'id'        => $model->getKey(),
                'images'    => $model->images ?? null,
            ]);
            $model->captureImageDeletions();
        });

        static::saved(function (self $model) {
            $model->deletePendingImages();
            $model->processRawImages();
        });

        static::deleted(function (self $model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            // Delete all images when entity is deleted
            $model->deleteAllImages();
        });
    }

    abstract public function imageColumns(): array;

    protected function captureImageDeletions(): void
    {
        foreach ($this->imageColumns() as $column => $config) {
            $original       = $this->getOriginal($column) ?? [];
            $current        = $this->getAttribute($column) ?? [];
            $originalById   = collect($original)->keyBy('id');
            $currentIds     = collect($current)->pluck('id')->filter()->all();
            $removed        = $originalById->except($currentIds)->values()->all();

            if ($removed !== []) {
                $this->pendingImageDeletions = [...$this->pendingImageDeletions, ...$removed];
            }
        }
    }

    protected function deletePendingImages(): void
    {
        if ($this->pendingImageDeletions === []) {
            return;
        }

        $action = app(DeleteProcessedImage::class);

        foreach ($this->pendingImageDeletions as $entry) {
            $action->handle($entry);
        }

        $this->pendingImageDeletions = [];
    }

    protected function processRawImages(): void
    {
        $touched        = false;
        $slugBase       = null;
        $stagingPrefix  = "{$this->store_id}/images/staging/";

        foreach ($this->imageColumns() as $column => $config) {
            $images = $this->getAttribute($column) ?? [];

            foreach ($images as $index => &$entry) {
                $imagePath = $entry['image'] ?? null;

                // If image is empty or already processed, skip it
                if (blank($imagePath) || ! str_starts_with($imagePath, $stagingPrefix)) {
                    continue;
                }

                // Livewire race condition bug log
                if (str_starts_with($imagePath, 'livewire-file:')) {
                    report(new \RuntimeException(
                        "Uploaded image skipped (Livewire race) for {$column}[{$index}], model ".static::class." #{$this->getKey()}"
                    ));

                    continue;
                }

                // Delete previous images before creating new
                if (filled($entry['conversions'] ?? null)) {
                    app(DeleteProcessedImage::class)->handle($entry);
                }

                $slugBase ??= $this->resolveSlugBase($config['slug_source'] ?? null);

                $processed = app(ProcessStagedImage::class)->handle(
                    stagedPath: $imagePath,
                    storeId:    $this->store_id,
                    type:       $config['type'],
                    entityId:   $this->getKey(),
                    baseSlug:   "{$slugBase}-{$index}",
                    existingId: $entry['id'] ?? null,
                );

                $entry = [...$entry, ...$processed];
                unset($entry['image']);

                $touched = true;
            }
            unset($entry);

            if ($touched) {
                $this->setAttribute($column, array_values($images));
            }
        }

        if ($touched) {
            $this->saveQuietly();
        }
    }

    // protected function processRawImages(): void
    // {
    //     $touched = false;
    //     $slugBase = null;

    //     foreach ($this->imageColumns() as $column => $config) {
    //         $images = $this->getAttribute($column) ?? [];

    //         foreach ($images as $index => &$entry) {
    //             if (filled($entry['conversions'] ?? null)) {
    //                 continue;
    //             }

    //             if (blank($entry['image'] ?? null)) {
    //                 continue;
    //             }

    //             $slugBase ??= $this->resolveSlugBase($config['slug_source'] ?? null);

    //             // стало:
    //             $processed = app(ProcessStagedImage::class)->handle(
    //                 stagedPath: $entry['image'],
    //                 storeId: $this->store_id,
    //                 type: $config['type'],
    //                 entityId: $this->getKey(),
    //                 baseSlug: "{$slugBase}-{$index}",
    //                 existingId: $entry['id'] ?? null,
    //             );

    //             $entry = [...$entry, ...$processed];
    //             unset($entry['image']);

    //             $touched = true;
    //         }
    //         unset($entry);

    //         if ($touched) {
    //             $this->setAttribute($column, array_values($images));
    //         }
    //     }

    //     if ($touched) {
    //         $this->saveQuietly();
    //     }
    // }

    protected function resolveSlugBase(?string $slugSource): string
    {
        $fallback = Str::slug(class_basename($this)) . '-' . $this->getKey();

        if ($slugSource === null || !in_array($slugSource, $this->translatable ?? [], true)) {
            return $fallback;
        }

        $defaultLanguageId = StoreLanguage::where('store_id', $this->store_id)
            ->where('is_default', true)
            ->value('language_id');

        $locale = Language::find($defaultLanguageId)?->locale;
        $value = $locale ? $this->getTranslation($slugSource, $locale) : null;

        return Str::slug($value ?: $fallback);
    }

    /**
     * Delete all images when entity is deleted
     * @return void
     */
    protected function deleteAllImages(): void
    {
        $action = app(DeleteProcessedImage::class);

        foreach ($this->imageColumns() as $column => $config) {
            foreach ($this->getAttribute($column) ?? [] as $entry) {
                $action->handle($entry);
            }
        }
    }
}