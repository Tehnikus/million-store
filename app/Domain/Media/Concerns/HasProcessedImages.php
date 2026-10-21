<?php

namespace App\Domain\Media\Concerns;

use App\Domain\Media\Actions\DeleteProcessedImage;
use App\Domain\Media\Actions\ProcessStagedImage;
use App\Models\Global\Language;
use App\Models\Global\StoreLanguage;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

/**
 * Converts uploaded images to WEBP sizes and keeps the files on disk in sync with the column.
 *
 * The model must declare:
 *  public function imageColumns(): array
 *  {
 *      return [
 *          'images' => [
 *              'type'        => ImageType::Category, // sizes (store settings / defaults) and directory name
 *              'slug_source' => 'name',              // translatable field the file names are made from
 *          ],
 *      ];
 *  }
 * A store_id column is required: it resolves the store's default language and the storage folder.
 *
 * Column format — a list, filled by FileUploadWithFields::make(...)->multiple():
 *  [
 *      {
 *          "src": "1/images/category/sources/0/5_laptops-k3f9a2.jpg",  // source file; a staging path until processed
 *          "fields": {"alt": {"uk": "...", "en": "..."}},              // whatever the form sends, kept as is
 *          "conversions": {"miniature": "....webp", "main": "....webp"} // owned by this trait, never by the form
 *      },
 *  ]
 *
 * An image is identified by its src (file paths are unique). The form only knows src + fields;
 * conversions are taken from the DB copy of the record (getOriginal), so the browser can't forge them.
 */
trait HasProcessedImages
{
    /** Entries removed from the column during this save; their files are deleted after the save succeeds */
    protected array $pendingImageDeletions = [];

    public static function bootHasProcessedImages(): void
    {
        static::saving(function (self $model) {
            $model->mergeStoredConversions();
        });

        static::saved(function (self $model) {
            $model->deletePendingImages();
            $model->processStagedImages();
        });

        static::deleted(function (self $model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->deleteAllImages();
        });
    }

    abstract public function imageColumns(): array;

    /**
     * Before save: give every kept image its conversions back from the DB and note removed images.
     */
    protected function mergeStoredConversions(): void
    {
        $stagingPrefix = "{$this->store_id}/images/staging/";

        foreach ($this->imageColumns() as $column => $config) {
            // isDirty compares the JSON: the form never sends conversions, so any form save makes it dirty
            if (!$this->isDirty($column)) {
                continue;
            }

            $current = $this->getAttribute($column) ?? [];

            if (array_key_exists('src', $current)) {
                throw new LogicException(
                    "Column [{$column}] of " . static::class . ' holds a single image; use FileUploadWithFields with ->multiple().'
                );
            }

            // Previous DB state, src => entry
            $stored = collect($this->getOriginal($column) ?? [])->keyBy('src');

            $images = [];
            $keptSources = [];

            foreach ($current as $entry) {
                $src = $entry['src'] ?? null;

                if (blank($src)) {
                    continue;
                }

                // Already processed and still there: restore its conversions from the DB
                if ($stored->has($src)) {
                    $images[] = [...$entry, 'conversions' => $stored[$src]['conversions'] ?? []];
                    $keptSources[] = $src;

                    continue;
                }

                // New upload: processed after save. Anything conversions-like from the input is dropped.
                if (str_starts_with($src, $stagingPrefix)) {
                    unset($entry['conversions']);
                    $images[] = $entry;

                    continue;
                }

                // Neither stored nor staged: a stale form (refreshFormData missing) or a forged path.
                // Livewire's 'livewire-file:' placeholders (upload race) also end up here.
                report(new RuntimeException(
                    "Unknown image src [{$src}] dropped from {$column}, model " . static::class . " #{$this->getKey()}"
                ));
            }

            $removed = $stored->except($keptSources)->values()->all();
            $this->pendingImageDeletions = [...$this->pendingImageDeletions, ...$removed];

            $this->setAttribute($column, $images);
        }
    }

    /**
     * After save: files of removed images go away only once the record is really saved
     */
    protected function deletePendingImages(): void
    {
        $action = app(DeleteProcessedImage::class);

        foreach ($this->pendingImageDeletions as $entry) {
            $action->handle($entry);
        }

        $this->pendingImageDeletions = [];
    }

    /**
     * After save: process images whose src is still in staging. Needs the record id (folder bucket),
     * that's why it runs in saved() and not in saving().
     */
    protected function processStagedImages(): void
    {
        $isTouched = false;
        $slugBase = null;
        $stagingPrefix = "{$this->store_id}/images/staging/";

        foreach ($this->imageColumns() as $column => $config) {
            $images = $this->getAttribute($column) ?? [];
            $isColumnTouched = false;

            foreach ($images as $index => $entry) {
                if (!str_starts_with($entry['src'] ?? '', $stagingPrefix)) {
                    continue;
                }

                // Resolved once per save, only when there is something to process
                $slugBase ??= $this->resolveSlugBase($config['slug_source'] ?? null);

                // Returns the new src (permanent source path) and conversions; fields stay untouched
                $processed = app(ProcessStagedImage::class)->handle(
                    stagedPath: $entry['src'],
                    storeId: $this->store_id,
                    type: $config['type'],
                    entityId: $this->getKey(),
                    baseSlug: $slugBase,
                );

                $images[$index] = [...$entry, ...$processed];
                $isColumnTouched = true;
            }

            if ($isColumnTouched) {
                $this->setAttribute($column, $images);
                $isTouched = true;
            }
        }

        // Quietly: no saving/saved events, so no recursion into this trait
        if ($isTouched) {
            $this->saveQuietly();
        }
    }

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
     * Entity deleted: remove all its image files
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