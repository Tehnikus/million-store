<?php

namespace App\Filament\Schemas\Tabs;

use App\Domain\Media\ImageType;
use App\Filament\Forms\Components\FileUploadWithFields;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Images tab for models with HasProcessedImages.
 * The Edit page must refresh the column after save (the pipeline moves files out of staging):
 *   protected function afterSave(): void { $this->refreshFormData(['images']); }
 */
class ImagesTab
{
    public static function make($store, $languages, ImageType $type): Tab
    {
        return Tab::make('images')
            ->schema(self::schema($store, $languages, $type))
            ->label(__('admin.common.tabs.images'))
            ->icon(Heroicon::OutlinedPhoto)
            ->badge(fn(Get $get) => count($get('images') ?? []) ?: null);
    }

    public static function schema($store, $languages, ImageType $type): array
    {
        $storeId = $store->id;

        // The largest conversion of this type (the last one: main, or miniature for small types)
        // gives the editor's first aspect ratio. Reduced by GCD: 2000×2000 → 1:1, 1600×900 → 16:9.
        $dimensions = $type->dimensions($store->storeSettings?->image_dimensions ?? []);
        ['width' => $width, 'height' => $height] = end($dimensions);

        for ($a = $width, $b = $height; $b !== 0; [$a, $b] = [$b, $a % $b])
            ;
        $aspectRatio = ($width / $a) . ':' . ($height / $a);

        return [
            FileUploadWithFields::make('images')
                ->multiple()
                ->fields(
                    fn() => collect($languages)
                        ->mapWithKeys(fn($language) => [
                            "alt.{$language->locale}" => "Alt ({$language->locale})",
                        ])
                        ->all()
                )
                ->panelLayout('grid')
                ->reorderable()
                ->image() // only images allowed
                ->disk('public') // Store on local public disc (not remote s3)
                ->directory("{$storeId}/images/staging") // temporary folder for uploaded files
                ->imageEditor() // Enaple image editor
                ->appendFiles() // Add new files to the end
                // The type's own ratio first; array_unique drops it from the presets if it's already there
                ->imageEditorAspectRatioOptions(array_values(array_unique([$aspectRatio, '16:9', '4:3', '1:1', null])))
                // ->imagePreviewHeight('250')
                ->loadingIndicatorPosition('right')
                // ->panelAspectRatio('2:1')
                // ->panelLayout('integrated')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('right')
                ->uploadProgressIndicatorPosition('right'),
        ];
    }
}