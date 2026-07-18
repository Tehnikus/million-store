<?php

namespace App\Filament\Schemas\Tabs;

use App\Models\StoreSetting;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ImagesTab
{
    public static function schema($config = []): array
    {
        $storeId    = Filament::getTenant()->id;
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $type       = $config['type'] ?? 'misc';
        $dimensions = StoreSetting::where('store_id', $storeId)->first()->image_dimensions;

        // Error message if image dimensions for this type are not set
        if (! isset($dimensions[$type])) {
            return [
                Callout::make(__('admin.common.helpers.image_type_not_set_title'))
                    ->description(new HtmlString(__('admin.common.helpers.image_type_not_set_info', ['type' => __("admin.design.image_types.{$type}")])))
                    ->danger()
                    ->columnSpanFull(),
            ];
        }

        // Calculate file upload aspect ratio
        if (isset($dimensions[$type]['miniature'])) {
            $width = $dimensions[$type]['miniature']['width'];
            $height = $dimensions[$type]['miniature']['height'];
        } elseif (isset($dimensions[$type]['main'])) {
            $width = $dimensions[$type]['main']['width'];
            $height = $dimensions[$type]['main']['height'];
        } else {
            $firstKey = array_key_first($dimensions[$type]);
            $width = $dimensions[$type][$firstKey]['width'];
            $height = $dimensions[$type][$firstKey]['height'];
        }
        $aspectRatio = ($width / $width) . ':' . ($height / $width);

        return [
            Repeater::make('images')
                ->hiddenLabel()
                ->table([
                    TableColumn::make(__('admin.common.fields.image'))->width('300px'),
                    TableColumn::make(__('admin.common.fields.image_description')),
                ])
                ->schema([
                    // Service inputs for saving()/saved() interaction
                    Hidden::make('id')->default(fn () => (string) Str::ulid()),
                    Hidden::make('conversions')->default([]),

                    FileUpload::make('image')
                        ->image()
                        ->disk('public')
                        ->directory("{$storeId}/images/staging")
                        ->imageEditor()
                        ->imageEditorAspectRatioOptions([$aspectRatio, '16:9', '4:3', '1:1', null]) // Set actual aspect ration first
                        ->formatStateUsing(fn (Get $get) => static::resolvePreviewPath($get('conversions') ?? [])) // Display preview on edit page
                        ->required(fn (Get $get) => blank($get('conversions'))) // Show error if image is still uploading
                        // ->preserveFilenames()
                        ,

                    Group::make(
                        collect($languages)->map(
                            fn ($language) => TextInput::make("alt.{$language->locale}")
                                ->columnSpanFull()
                                ->prefix($language->locale)
                                ->placeholder('Alt')
                        )->all()
                    ),
                ])
                ->addActionLabel(__('admin.common.buttons.add_image_row'))
                ->reorderable(true)
                ->columnSpanFull()
                ->helperText(__('admin.common.helpers.images_tab')),
        ];
    }

    /**
     * Resolve preview
     * Needed because after the image was uploaded and the form was saved saved data structure does not match format expected by the Repeater component
     * So it passes original or larges conversion to diplay in FileUpload component
     * Try to load orgilan first, then main image, then first array element
     * @param array $conversions
     */
    private static function resolvePreviewPath(array $conversions): ?string
    {
        return $conversions['original'] ?? $conversions['main'] ?? Arr::first($conversions);
    }

    /**
     * Display tab label
     * @return array|string
     */
    public static function label(): string
    {
        return __('admin.common.tabs.images');
    }
}