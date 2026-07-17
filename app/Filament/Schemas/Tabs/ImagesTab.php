<?php

namespace App\Filament\Schemas\Tabs;

use App\Models\StoreSetting;
use App\Models\StoreLanguage;
use App\Models\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Group;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\HtmlString;


class ImagesTab
{
    public static function schema($config = []): array
    {
        $storeId        = Filament::getTenant()->id;
        $languages      = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $type           = $config['type'] ?? 'misc';
        $dimensions     = StoreSetting::where('store_id', $storeId)->first()->image_dimensions;

        
        if (!isset($dimensions[$type])) {
            return [
                Callout::make(__('admin.common.helpers.image_type_not_set_title'))
                    ->description(new HtmlString(__('admin.common.helpers.image_type_not_set_info',  ['type' => __("admin.design.image_types.{$type}")])))
                    ->danger()
                    ->columnSpanFull()
            ];
        }

        // $maxWidth       = max(array_column($dimensions, 'width'));
        // $maxHeight      = max(array_column($dimensions, 'height'));
        // $defaultWidth   = $dimensions[array_key_first($dimensions)]['width'];
        // $defaultHeight  = $dimensions[array_key_first($dimensions)]['height'];
        $defaultWidth      = 1000;
        $defaultHeight     = 1000;
        
        return [
            Repeater::make("images")
                ->hiddenLabel()
                ->table([
                    TableColumn::make(__('admin.common.fields.image'))->width('300px'),
                    TableColumn::make(__('admin.common.fields.image_description')),
                ])
                ->schema([
                    FileUpload::make('image')
                        ->image()
                        ->panelAspectRatio($defaultWidth / $defaultHeight . ":1") // Put image settings width:height here so panel fits desired image aspect ratio
                        ->imageEditor()
                        ->disk('public')
                        ->imageEditorAspectRatioOptions([null, '16:9', '4:3', '1:1'])
                        ->directory("images/{$storeId}/{$type}")
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                ->prepend('custom-prefix-'),
                        )
                        // ->getUploadedFileNameForStorageUsing(
                        //     function (TemporaryUploadedFile $file, Get $get): string {
                        //         $storeId = Filament::getTenant()->id;
                        //         $storeDefaultLanguage = StoreLanguage::where('is_default', true)->where('store_id', $storeId)->pluck('language_id');
                        //         $storeDefaultLocale = Language::where('id', $storeDefaultLanguage)->pluck('locale');
                        //         // 1. Fetch the value from your TextInput field
                        //         $title = $get("name.{$storeDefaultLocale[0]}") ?? 'default-name';
                                
                        //         // 2. Clean the input string to make it safe for file systems
                               
                        //         $slug = Str::slug($title). '-' . Str::random(2);
                                
                        //         // 3. Extract the original file extension
                        //         $extension = $file->getClientOriginalExtension();
                                
                        //         // 4. Return the full custom file name
                        //         return "{$slug}.{$extension}";
                        //     }
                        // )
                        ,
                    Group::make(
                            collect($languages)->map(
                                fn($language) =>
                                TextInput::make("alt.{$language->locale}")
                                    ->columnSpanFull()
                                    ->prefix($language->locale)
                                    ->placeholder("Alt")
                            )->all()
                        )
                ])
                ->addActionLabel(__('admin.common.buttons.add_image_row'))
                ->reorderable(true)
                // ->compact()
                ->columnSpanFull()
                ->helperText(__('admin.common.helpers.images_tab')),
        ];
    }

    public static function label(): string
    {
        return __('admin.common.tabs.images');
    }
}