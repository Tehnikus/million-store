<?php

namespace App\Filament\Resources\Options\Schemas;

use App\Filament\Schemas\Fields\SlugInput;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OptionForm
{
    public static function configure(Schema $schema): Schema
    {
        $storeId    = Filament::getTenant()->id;
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $prepareValueData = function (array $data) use ($storeId): array {
            // unset($data['slugs'], $data['slugs_touched']);

            return [...$data, 'store_id' => $storeId];
        };

        return $schema
            ->components([
                Section::make(__('admin.catalog.options.fields.group_title'))
                    ->description(__('admin.catalog.options.helpers.group_title'))
                    ->schema([

                        Fieldset::make(__('admin.catalog.options.fields.group_name'))
                            ->schema([
                                ...collect($languages)->map(
                                    fn($language) =>
                                    TextInput::make("name.{$language->locale}")
                                        ->required()
                                        ->maxLength(255)
                                        ->prefix($language->locale)
                                        ->placeholder(__('admin.catalog.options.fields.group_name'))
                                        ->label(__('admin.catalog.options.fields.group_name'))
                                        ->hiddenLabel(),
                                )->all(),

                                Text::make(__('admin.catalog.options.helpers.group_name'))
                                    ->columnSpanFull(),

                                Select::make('type')
                                    ->options([
                                        'radio'     => __('admin.catalog.options.fields.radio'),
                                        'checkbox'  => __('admin.catalog.options.fields.checkbox'),
                                    ])
                                    ->required()
                                    ->columnSpanFull()
                                    ->label(__('admin.catalog.options.fields.type'))
                                    ->helperText(__('admin.catalog.options.helpers.type')),

                                Group::make([

                                    Toggle::make('is_active')
                                        ->label(__('admin.catalog.options.fields.is_active'))
                                        ->helperText(__('admin.catalog.options.helpers.is_active'))
                                        ->default(true),

                                    Toggle::make('show_in_facets')
                                        ->label(__('admin.catalog.options.fields.show_in_facets'))
                                        ->helperText(__('admin.catalog.options.helpers.show_in_facets'))
                                        ->default(true),
                                ])
                                ->columnSpanFull(),
                            ])
                            ->columns(\count($languages)),

                        Repeater::make('values')
                            ->schema([
                                FileUpload::make('images'),
                                Group::make(
                                    collect($languages)->map(
                                        fn($language) =>
                                        Fieldset::make($language->name)
                                            ->schema([
                                                TextInput::make("name.{$language->locale}")
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefix($language->locale)
                                                    ->label(__('admin.catalog.options.fields.option_name'))
                                                    ->placeholder(__('admin.catalog.options.fields.option_name'))
                                                    ->live(onBlur: false, debounce: 500, condition: true)
                                                    ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state, ?Model $record) use ($language) {
                                                        $slugTouchedPath = "slugs_touched_{$language->id}";
                                                        if ($get($slugTouchedPath)) return;

                                                        $groupName = $get("../../name.{$language->locale}");
                                                        $groupSlug = filled($groupName) ? Str::slug($groupName, '-', $language->locale) . '-' : '';
                                                        $valueSlug = Str::slug($state ?? '', '-', $language->locale);

                                                        $set("slugs_{$language->id}", $groupSlug . $valueSlug);

                                                        $slugPath = $component->getContainer()->getStatePath() . ".slugs_{$language->id}";
                                                        SlugInput::validateSlugLive($livewire, $slugPath, $groupSlug . $valueSlug, $language->id, SlugInput::excludeSelfQuery($record));
                                                    })
                                                    ->hiddenLabel(),

                                                // Slugs
                                                ...SlugInput::makeSlug($language, [
                                                    'generateSlugUsing' => function (Get $get, string $locale) {
                                                        $groupName  = $get("../../name.{$locale}");
                                                        $groupSlug  = filled($groupName) ? Str::slug($groupName, '-', $locale) . '-' : '';
                                                        $valueSlug  = Str::slug($get("name.{$locale}") ?? '', '-', $locale);
                                                        return $groupSlug . $valueSlug;
                                                    },
                                                    'siblingsPath' => '../../values',
                                                ]),

                                                RichEditor::make("description.{$language->locale}")
                                                    ->columnSpanFull()
                                                    ->placeholder(__('admin.catalog.options.fields.description'))
                                                    ->toolbarButtons([])
                                                    ->floatingToolbars([
                                                        'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify', 'clearFormatting', 'undo', 'redo'],
                                                    ])
                                                    ->extraInputAttributes([
                                                        'style' => 'min-height: 7rem; max-height: 15vh; overflow-y: auto;'
                                                    ])
                                                    ->hiddenLabel(),
                                            ])
                                            ->dense()
                                    )->all()
                                ),

                                Toggle::make('is_default')
                                    ->label(__('admin.catalog.options.fields.is_default'))
                                    ->helperText(__('admin.catalog.options.helpers.is_default'))
                                    ->live()
                                    ->distinct(fn(Get $get) => $get('../../type') == 'radio')
                                    ->fixIndistinctState(fn(Get $get) => $get('../../type') == 'radio'),

                                Group::make([
                                    Toggle::make('is_active')
                                        ->label(__('admin.catalog.options.fields.is_active'))
                                        ->helperText(__('admin.catalog.options.helpers.value_is_active'))
                                        ->default(true),

                                    Toggle::make('show_in_facets')
                                        ->label(__('admin.catalog.options.fields.show_in_facets'))
                                        ->helperText(__('admin.catalog.options.helpers.value_show_in_facets'))
                                        ->default(true),
                                ])
                                ->columns(2)
                                ->columnSpanFull()
                            ])
                            ->relationship('values')
                            ->mutateRelationshipDataBeforeCreateUsing($prepareValueData) // Add required store id column
                            ->mutateRelationshipDataBeforeSaveUsing($prepareValueData) // Remove slugs from data 
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed(fn($operation) => $operation !== 'create')
                            ->itemLabel(fn (array $state): ?string => $state['name'][app()->getLocale()] ?? Arr::first($state['name'] ?? []) ?? null)
                            ->label(__('admin.catalog.options.fields.values'))
                            ->addActionLabel(__('admin.catalog.options.buttons.add_option_value'))
                            ->addActionAlignment('right')
                            ->addAction(fn (Action $action) => $action->color('success')->icon('heroicon-o-plus'))
                    ])
                    ->columnSpanFull()
            ]);
    }
}
