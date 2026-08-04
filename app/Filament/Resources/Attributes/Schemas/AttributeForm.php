<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        $storeId    = Filament::getTenant()->id;
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $injectStoreId = fn (array $data): array => [...$data, 'store_id' => $storeId];

        $excludeSelf = function (?Model $owner) {
            if (!$owner) {
                return null;
            }

            $type = $owner::class;

            return fn($query) => $query->where(function ($q) use ($type, $owner) {
                $q->where('sluggable_type', '!=', $type)
                    ->orWhere('sluggable_id', '!=', $owner->getKey());
            });
        };

        return $schema
            ->components([
                Section::make('Attribute')
                    ->schema([
                        Fieldset::make(__('admin.catalog.attributes.fields.group_name'))
                            ->schema(
                                collect($languages)->map(
                                    fn($language) =>
                                    TextInput::make("name.{$language->locale}")
                                        ->required()
                                        ->prefix($language->locale)
                                        ->placeholder(__('admin.catalog.attributes.fields.group_name'))
                                        ->label(__('admin.catalog.attributes.fields.group_name'))
                                        ->helperText(__('admin.catalog.attributes.helpers.group_name'))
                                        ->live(),
                                )->all(),
                            )
                            ->columns(count($languages)),
                        Repeater::make('values')
                            ->table([
                                TableColumn::make(__('admin.catalog.attributes.fields.image'))->width('200px'),
                                TableColumn::make(__('admin.catalog.attributes.fields.description')),
                            ])
                            ->schema([
                                FileUpload::make('image'),
                                Group::make(
                                    collect($languages)->map(
                                        fn($language) =>
                                        Fieldset::make($language->name)
                                            ->schema([
                                                TextInput::make("name.{$language->locale}")
                                                    ->required()
                                                    ->prefix($language->locale)
                                                    ->label(__('admin.catalog.attributes.fields.attribute_name'))
                                                    ->placeholder(__('admin.catalog.attributes.fields.attribute_name'))
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, $component, $livewire, ?string $state, ?Model $record) use ($language, $excludeSelf) {
                                                        // Skip autofill if record already exists
                                                        $slugTouchedPath = "slugs_touched.{$language->id}";
                                                        if ($get($slugTouchedPath)) return;

                                                        // Create slur from name
                                                        $newSlug = Str::slug($state ?? '', '-', $language->locale);
                                                        // Fill slug input
                                                        $set("slugs.{$language->id}", $newSlug);
                                                        // Validate slug uniqueness and alpha-numeric stuff
                                                        // $slugPath = $component->getContainer()->getStatePath() . ".slugs.{$languageId}";
                                                        // self::validateSlugLive($livewire, $slugPath, $newSlug, $languageId, $excludeSelf($record));
                                                    }),
                                                TextInput::make("slugs.{$language->id}")
                                                    ->required()
                                                    ->prefix($language->locale)
                                                    ->label(__('admin.catalog.attributes.fields.slug'))
                                                    ->placeholder(__('admin.catalog.attributes.fields.slug')),
                                                RichEditor::make("description.{$language->locale}")
                                                    ->columnSpanFull()
                                                    ->placeholder(__('admin.catalog.attributes.fields.description'))
                                                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'textColor'])

                                                    ->extraInputAttributes([
                                                        'style' => 'min-height: 7rem; max-height: 15vh; overflow-y: auto;'
                                                    ])
                                            ])
                                    )->all()
                                )

                            ])
                            ->relationship('values')
                            // Add required store id column
                            ->mutateRelationshipDataBeforeCreateUsing($injectStoreId)
                            ->mutateRelationshipDataBeforeSaveUsing($injectStoreId)
                            ->label(__('admin.catalog.attributes.fields.values'))
                    ])
                    ->columnSpanFull()
            ]);
    }
}
