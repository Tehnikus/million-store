<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

/**
 * FileUpload with per-file text fields (e.g. alt per language), rendered inside each FilePond item.
 *
 *   FileUploadWithFields::make('images')
 *       ->multiple()
 *       ->fields(fn () => ['alt.uk' => 'Alt (UA)', 'alt.en' => 'Alt (EN)'])
 *
 * Filament's own state stays untouched: [uuid => path | TemporaryUploadedFile].
 * Field values live in a sibling Livewire key, keyed by the same uuid:
 *   {statePath}_fields = [uuid => ['alt' => ['uk' => '...', 'en' => '...']]]
 *
 * Stored value:
 *   multiple: [{src: 'path', fields: {alt: {uk: '...', en: '...'}}}, ...]
 *   single:   {src: 'path', fields: {...}}
 * Plain path strings (old data) are still accepted when loading.
 */
class FileUploadWithFields extends FileUpload
{
    /**
     * Field path (dot notation) => label, e.g. ['alt.uk' => 'Alt (UA)'].
     * Not named $fields to avoid any clash with Filament internals.
     *
     * @var array<string, string> | Closure
     */
    protected array|Closure $fileFields = [];

    public function fields(array|Closure $fields): static
    {
        $this->fileFields = $fields;

        return $this;
    }

    /**
     * Always flat: ['alt.uk' => 'Alt (UA)', ...].
     * Arr::dot also accepts the nested form ['alt' => ['uk' => 'Alt (UA)']] — both describe the same fields.
     *
     * @return array<string, string>
     */
    public function getFileFields(): array
    {
        return Arr::dot($this->evaluate($this->fileFields) ?? []);
    }

    /**
     * Sibling key next to our own state path: data.images → data.images_fields.
     * getStatePath() is absolute, so this also works inside a Repeater
     * (data.items.{uuid}.images → data.items.{uuid}.images_fields).
     */
    public function getFileFieldsStatePath(): string
    {
        return $this->getStatePath() . '_fields';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Filament: afterStateHydrated holds ONE closure (last write wins).
        // The parent's closure only calls hydrateFiles(), so we replace it and call it ourselves.
        //
        // DB → form. By this point FileUploadStateCast::set() has already run:
        //   multiple: [{src, fields}, ...]  →  [uuid => {src, fields}, ...]   (numeric keys replaced by uuids)
        //   single:   {src, fields}         →  ['src' => ..., 'fields' => ...] (string keys are kept as is)
        $this->afterStateHydrated(function (self $component): void {
            $items = $component->getRawState() ?? [];

            // Single mode: the cast took our object for a list of files. Wrap it back into one item.
            if (array_key_exists('src', $items)) {
                $items = [(string) Str::uuid() => $items];
            }

            $files = [];
            $values = [];

            foreach ($items as $uuid => $item) {
                // Plain path string: data saved before fields existed, or a default value
                if (is_string($item)) {
                    $files[$uuid] = $item;

                    continue;
                }

                if (filled($item['src'] ?? null)) {
                    $files[$uuid] = $item['src'];
                    $values[$uuid] = $item['fields'] ?? [];
                }
            }

            // Filament's own state gets plain paths again, exactly what the rest of FileUpload expects
            $component->rawState($files);
            $component->hydrateFiles();

            // data_set works on the Livewire component object:
            // 'data.images_fields' resolves to $livewire->data['images_fields'].
            // It takes its target BY REFERENCE (&$target), so it needs a variable, not a call result.
            // The variable holds a handle to the same object, so the write reaches the component.
            $livewire = $component->getLivewire();
            data_set($livewire, $component->getFileFieldsStatePath(), $values);
        });

        // FilePond positions items absolutely and gives each one a fixed inline height,
        // but it does count the item's margin-bottom when stacking items.
        // We reserve room for the inputs under each image through that margin (see CSS).
        // The number of rows goes into a CSS variable on the outer .fi-fo-file-upload element.
        $this->extraAttributes(fn(self $component): array => [
            'class' => 'upload-fields',
            'style' => '--upload-fields-count: ' . count($component->getFileFields()),
        ], merge: true);
    }

    /**
     * Form → DB.
     *
     * Not dehydrateStateUsing(): its $state has already passed FileUploadStateCast::get(),
     * which does array_values() — the uuids are gone and we couldn't match the fields.
     * getRawState() still has them, and by now beforeStateDehydrated → saveUploadedFiles()
     * has replaced temporary uploads with stored paths.
     */
    public function getStateToDehydrate(mixed $state): array
    {
        // Parent keeps its own extras (e.g. storeFileNamesIn); we only replace our own key
        $result = parent::getStateToDehydrate($state);

        // The sibling key is client-writable (it comes from the browser via $wire.$set),
        // so only declared field paths are taken, and every value is cast to a string or null
        $values = data_get($this->getLivewire(), $this->getFileFieldsStatePath()) ?? [];
        $declaredPaths = array_keys($this->getFileFields());

        $items = [];

        foreach ($this->getRawState() ?? [] as $uuid => $path) {
            $fields = [];

            foreach ($declaredPaths as $fieldPath) {
                $value = data_get($values, "{$uuid}.{$fieldPath}");

                data_set($fields, $fieldPath, is_scalar($value) && filled($value) ? (string) $value : null);
            }

            $items[] = ['src' => $path, 'fields' => $fields];
        }

        $result[$this->getStatePath()] = $this->isMultiple() ? $items : ($items[0] ?? null);

        return $result;
    }

    /**
     * Filament 5.10 renders FileUpload in PHP (no Blade view to override).
     * We keep the native markup as is and append an invisible Alpine "glue" element
     * that injects our inputs into FilePond items.
     */
    public function toEmbeddedHtml(): string
    {
        $config = Js::from([
            'id' => $this->getId(),
            'fieldsPath' => $this->getFileFieldsStatePath(),
            'fields' => $this->getFileFields(),
            'isDisabled' => $this->isDisabled(),
        ]);

        // wire:ignore: Livewire must not morph this element, otherwise Alpine re-initializes it.
        return parent::toEmbeddedHtml() . <<<HTML
            <div wire:ignore hidden x-data="fileUploadFields({$config})"></div>
            HTML;
    }
}