<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Models\Seo\Slug;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
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
        $prepareValueData = function (array $data) use ($storeId): array {
            unset($data['slugs'], $data['slugs_touched']);

            return [...$data, 'store_id' => $storeId];
        };

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
                Section::make(__('admin.catalog.attributes.fields.group_name'))
                    ->description(__('admin.catalog.attributes.fields.group_name'))
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
                            // ->table([
                            //     TableColumn::make(__('admin.catalog.attributes.fields.image'))->width('200px'),
                            //     TableColumn::make(__('admin.catalog.attributes.fields.description')),
                            // ])
                            ->schema([
                                FileUpload::make('images'),
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

                                                        // Create slur from group and name
                                                        $groupName = $get("../../name.{$language->locale}");
                                                        $groupSlug = !empty($groupName) ? Str::slug($get("../../name.{$language->locale}"), '-', $language->locale) . '-' : ''; 
                                                        $valueSlug = Str::slug($state ?? '', '-', $language->locale);
                                                        // Fill slug input
                                                        $set("slugs.{$language->id}", $groupSlug . $valueSlug);
                                                        // Validate slug uniqueness and alpha-numeric stuff
                                                        // $slugPath = $component->getContainer()->getStatePath() . ".slugs.{$languageId}";
                                                        // self::validateSlugLive($livewire, $slugPath, $newSlug, $languageId, $excludeSelf($record));
                                                    })
                                                    ->hiddenLabel(),
                                                TextInput::make("slugs.{$language->id}")
                                                    ->required()
                                                    ->prefix($language->locale)
                                                    ->label(__('admin.catalog.attributes.fields.slug'))
                                                    ->placeholder(__('admin.catalog.attributes.fields.slug'))
                                                    ->hiddenLabel()
                                                    ->suffixAction(
                                                        Action::make(__('admin.common.buttons.create_slug'))
                                                            ->icon('heroicon-o-link')
                                                            ->action(function (Get $get, Set $set) use ($language) {
                                                                $groupName = $get("../../name.{$language->locale}");
                                                                $groupSlug = !empty($groupName) ? Str::slug($get("../../name.{$language->locale}"), '-', $language->locale) . '-' : ''; 
                                                                $valueSlug = Str::slug($get("name.{$language->locale}") ?? '', '-', $language->locale);
                                                                // Fill slug input
                                                                $set("slugs.{$language->id}", $groupSlug . $valueSlug);
                                                                // Set slug touched flag to true so slug autocomlete does not touch it
                                                                $slugTouchedPath = "slugs_touched.{$language->id}";
                                                                $set($slugTouchedPath, true);
                                                            })
                                                            ->tooltip(__('admin.common.buttons.create_slug'))
                                                    )
                                                    ->loadStateFromRelationshipsUsing(static function (?Model $record, Component $component) use ($language) {
                                                        if (!$record || !method_exists($record, 'currentStoreSlugs')) return;

                                                        $slugs = $record->currentStoreSlugs->keyBy('language_id');
                                                        $state = $slugs->get($language->id)?->slug;
                                                        $component->state($state);
                                                    })
                                                    // Save slugs right from the form
                                                    ->saveRelationshipsUsing(static function (Model $record, $state) use ($language) {
                                                        $storeId = Filament::getTenant()->id;

                                                        if (filled($state)) {
                                                            $slugValue = Str::slug($state ?? '', '-', $language->locale);

                                                            Slug::updateOrCreate(
                                                                [
                                                                    'sluggable_type' => $record->getMorphClass(),
                                                                    'sluggable_id'   => $record->id,
                                                                    'store_id'       => $storeId,
                                                                    'language_id'    => $language->id,
                                                                ],
                                                                [
                                                                    'slug'           => $slugValue,
                                                                    'is_active'      => true,
                                                                ]
                                                            );
                                                        } else {
                                                            Slug::where([
                                                                'sluggable_type' => $record->getMorphClass(),
                                                                'sluggable_id'   => $record->id,
                                                                'store_id'       => $storeId,
                                                                'language_id'    => $language->id,
                                                            ])->delete();
                                                        }
                                                    }),
                                                RichEditor::make("description.{$language->locale}")
                                                    ->columnSpanFull()
                                                    ->placeholder(__('admin.catalog.attributes.fields.description'))
                                                    ->toolbarButtons([])
                                                    ->floatingToolbars([
                                                        'paragraph' => ['bold', 'italic', 'underline', 'link', 'textColor', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify', 'clearFormatting', 'undo', 'redo'],
                                                    ])
                                                    ->extraInputAttributes([
                                                        'style' => 'min-height: 7rem; max-height: 15vh; overflow-y: auto;'
                                                    ])
                                                    ->hiddenLabel()
                                                    
                                            ])
                                            ->dense()
                                    )->all()
                                )

                            ])
                            ->relationship('values')
                            ->mutateRelationshipDataBeforeCreateUsing($prepareValueData) // Add required store id column
                            ->mutateRelationshipDataBeforeSaveUsing($prepareValueData) // Remove slugs from data 
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed(fn($operation) => $operation !== 'create')
                            ->itemLabel(fn (array $state): ?string => \Illuminate\Support\Arr::first($state['name'] ?? []) ?? '')
                            ->label(__('admin.catalog.attributes.fields.values'))
                    ])
                    ->columnSpanFull()
            ]);
    }
}
