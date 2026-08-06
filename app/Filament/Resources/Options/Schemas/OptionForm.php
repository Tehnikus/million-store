<?php

namespace App\Filament\Resources\Options\Schemas;

use App\Domain\Seo\ChecksSlugUniqueness;
use App\Models\Seo\Slug;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
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
use Illuminate\Validation\Rules\Unique;

class OptionForm
{
    use ChecksSlugUniqueness;
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
                                                        $slugPath = $component->getContainer()->getStatePath() . ".slugs.{$language->id}";
                                                        self::validateSlugLive($livewire, $slugPath, $groupSlug . $valueSlug, $language->id, $excludeSelf($record));
                                                    })
                                                    ->hiddenLabel(),
                                                TextInput::make("slugs.{$language->id}")
                                                    ->required()
                                                    ->label(__('admin.catalog.options.fields.slug'))
                                                    ->placeholder(__('admin.catalog.options.fields.slug'))
                                                    ->hiddenLabel()

                                                    // Check slug uniqueness live
                                                    ->live(onBlur: false, debounce: 500)
                                                    ->afterStateUpdated(function (Set $set, ?string $state, $component, $livewire, ?Model $record) use ($language, $excludeSelf) {
                                                        // If user made any input into slug input mark it as touched, so autofill does not change it
                                                        $set("slugs_touched.{$language->id}", true);

                                                        self::validateSlugLive($livewire, $component->getStatePath(), $state, $language->id, $excludeSelf($record));
                                                    })
                                                    ->afterStateHydrated(function (Set $set, ?string $state) use ($language) {
                                                        // If any slug state present after form values are filled mark it slug input touched, so autofill does not change it
                                                        if (filled($state)) {
                                                            $set("slugs_touched.{$language->id}", true);
                                                        }
                                                    })
                                                    
                                                    ->unique(
                                                        table: 'slugs',
                                                        column: 'slug',
                                                        ignorable: fn() => null,
                                                        modifyRuleUsing: function (Unique $rule, ?Model $record) use ($language) {
                                                            $rule
                                                                ->where('store_id', Filament::getTenant()->id)
                                                                ->where('language_id', $language->id);

                                                            if ($record) {
                                                                $type = $record::class;

                                                                $rule->where(function ($query) use ($type, $record) {
                                                                    $query->where('sluggable_type', '!=', $type)
                                                                        ->orWhere('sluggable_id', '!=', $record->getKey());
                                                                });
                                                            }

                                                            return $rule;
                                                        },
                                                    )

                                                    ->maxLength(255)
                                                    ->rules(['alpha_dash:ascii'])
                                                    ->validationMessages(['unique' => __('admin.seo.slugs.errors.slug_taken'), 'alpha_dash' => __('admin.seo.slugs.errors.alpha_dash')])

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
                                    ->helperText(__('admin.catalog.options.helpers.is_default')),

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
                    ])
                    ->columnSpanFull()
            ]);
    }
}
