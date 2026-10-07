<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Search\SearchIndexer;
use App\Domain\Catalog\Search\SearchRefType;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use Closure;
use App\Domain\Catalog\FacetType;
use App\Models\Catalog\{Category, FacetIndex, Manufacturer, Tag};
use Filament\Forms\Components\{Hidden, Repeater, Repeater\TableColumn, Select, TextInput, Toggle};
use Filament\Schemas\Components\{Callout, FusedGroup, Section, Tabs\Tab, Utilities\Get, Utilities\Set};
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlacementTab
{
    public static function make($store, $languages): Tab
    {

        // Count badge without server querying
        $placementBadgeJs = static::badgeUpdateJs(
            "Object.keys(\$get('facet_categories') ?? {}).length"
            . " + Object.keys(\$get('facet_manufacturers') ?? {}).length"
            . " + Object.keys(\$get('facet_tags') ?? {}).length"
        );

        return Tab::make('placement')
            ->badge(fn (Get $get) => \count($get('facet_categories') ?? []) + \count($get('facet_manufacturers') ?? []) + \count($get('facet_tags') ?? []) ?: null)
            ->schema([

                // Category part 
                Section::make(__('admin.catalog.products.tabs.placement.labels.categories'))
                    ->description(__('admin.catalog.products.tabs.placement.helpers.categories'))
                    ->schema([
                        Repeater::make('facet_categories')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.category'))->markAsRequired(),
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.is_primary_category'))->markAsRequired()->width('200px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.category'))
                                    ->options(fn() => Category::categoryChoices($store->id))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function(Set $set, ?string $state) use ($store) {
                                        $category = Category::where('store_id', $store->id)->where('id', $state)->first();
                                        $set('facet_group_id', $category?->parent_id ?? 0);
                                        $set('sort_order', FacetIndex::where('facet_value_id', $state)->where('facet_group_id', $category?->parent_id ?? 0)->where('facet_type_id', FacetType::Category)->where('store_id', $store->id)->count() + 1);
                                    })
                                    
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['sort_order', 'facet_group_id', 'is_primary']),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')
                                    ->default(0),
                                Toggle::make('is_primary')
                                    ->distinct()
                                    ->required()
                                    ->fixIndistinctState()
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center'])
                                    ->label(__('admin.catalog.products.tabs.placement.labels.is_primary_category')),
                            ])
                            ->rule(fn() => function (string $attribute, $value, Closure $fail) {
                                $hasPrimary = collect($value)->contains(fn($item) => !empty($item['is_primary']));
                                if (!$hasPrimary) {
                                    $fail(__('admin.catalog.products.tabs.placement.errors.no_primary_category'));
                                }
                            })
                            ->addActionLabel(__('admin.catalog.products.tabs.placement.buttons.add_category'))
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->maxItems(Category::categoryChoices($store->id)->count())
                            ->label(__('admin.catalog.products.tabs.placement.labels.categories'))
                            ->hiddenLabel()
                            ->compact()
                            ->live()
                            ->afterStateUpdatedJs($placementBadgeJs)
                            ->partiallyRenderComponentsAfterStateUpdated(['facet_categories']),
                        Callout::make()
                            ->visible(function () use ($store) {
                                return Category::categoryChoices($store->id)->count() == 0;
                            })
                            ->description(__('admin.catalog.products.tabs.placement.errors.no_categories'))
                            ->danger()
                            ->columnSpanFull(),
                    ]),

                // Manufacturer part
                Section::make(__('admin.catalog.products.tabs.placement.labels.manufacturers'))
                    ->description(__('admin.catalog.products.tabs.placement.helpers.manufacturers'))
                    ->schema([
                        Repeater::make('facet_manufacturers')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.manufacturer')),
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.is_primary_manufacturer'))->width('200px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.manufacturers'))
                                    ->options(fn() => Manufacturer::manufacturerChoices($store->id))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function(Set $set, ?string $state) use ($store) {
                                        $manufacturer = Manufacturer::where('store_id', $store->id)->where('id', $state)->first();
                                        $set('facet_group_id', $manufacturer?->parent_id ?? 0);
                                        $set('sort_order', FacetIndex::where('facet_value_id', $state)->where('facet_group_id', $manufacturer?->parent_id ?? 0)->where('facet_type_id', FacetType::Manufacturer)->where('store_id', $store->id)->count() + 1);
                                        // $set('facet_group_id', Manufacturer::where('store_id', $store->id)->where('id', $state)->first()?->parent_id ?? 0);
                                    })
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['sort_order', 'facet_group_id', 'is_primary']),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')
                                    ->default(0),
                                Toggle::make('is_primary')
                                    ->distinct()
                                    ->fixIndistinctState()
                                    ->extraFieldWrapperAttributes(['style' => 'justify-self: center'])
                                    ->label(__('admin.catalog.products.tabs.placement.labels.is_primary_manufacturer')),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->maxItems(Manufacturer::manufacturerChoices($store->id)->count())
                            ->addActionLabel(__('admin.catalog.products.tabs.placement.buttons.add_manufacturer'))
                            ->label(__('admin.catalog.products.tabs.placement.labels.manufacturers'))
                            ->hiddenLabel()
                            ->compact()
                            ->visible(function () use ($store) {
                                return Manufacturer::manufacturerChoices($store->id)->count() > 0;
                            })
                            ->live()
                            ->afterStateUpdatedJs($placementBadgeJs)
                            ->partiallyRenderComponentsAfterStateUpdated(['facet_manufacturers']),
                    ]),
                
                // Tags part
                Section::make(__('admin.catalog.products.tabs.placement.labels.product_tags'))
                    ->description(__('admin.catalog.products.tabs.placement.helpers.tags'))
                    ->schema([
                        Repeater::make('facet_tags')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.product_tags')),
                                TableColumn::make(__('admin.catalog.products.tabs.placement.labels.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.tag'))
                                    ->options(fn() => Tag::tagChoices($store->id))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function(Set $set, ?string $state) use ($store) {
                                        $tag = Tag::where('store_id', $store->id)->where('id', $state)->first();
                                        $set('facet_group_id', $tag?->parent_id ?? 0);
                                        $set('sort_order', FacetIndex::where('facet_value_id', $state)->where('facet_group_id', 0)->where('facet_type_id', FacetType::Tag)->where('store_id', $store->id)->count() + 1);
                                    })
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['sort_order', 'facet_group_id']),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.tabs.placement.labels.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')->default(0),
                            ])
                            ->reorderable(false)
                            ->addActionLabel(__('admin.catalog.products.tabs.placement.buttons.add_tag'))
                            ->defaultItems(0)
                            ->maxItems(Tag::tagChoices($store->id)->count())
                            ->label(__('admin.catalog.products.tabs.placement.labels.product_tags'))
                            ->hiddenLabel()
                            ->compact()
                            ->visible(function () use ($store) {
                                return Tag::tagChoices($store->id)->count() > 0;
                            }),
                    ]),

                // Search part
                Section::make(__('admin.catalog.products.tabs.placement.labels.search_custom_terms'))
                    ->description(__('admin.catalog.products.tabs.placement.helpers.search_custom_terms'))
                    ->schema([
                        FusedGroup::make(
                            collect($languages)->map(
                                fn ($language) =>
                                TextInput::make("description.search_custom_terms.{$language->locale}")
                                    ->prefix($language->locale)
                                    ->maxLength(500)
                                    ->hiddenLabel()
                                    ->placeholder(__('admin.catalog.products.tabs.placement.placeholders.search_custom_terms'))
                            )->all()
                        )
                            ->columnSpanFull()
                            ->label(__('admin.catalog.products.tabs.placement.labels.search_custom_terms')),
                    ]),

                Section::make(__('admin.catalog.products.tabs.placement.labels.search_excluded_refs'))
                    ->description(__('admin.catalog.products.tabs.placement.helpers.search_excluded_refs'))
                    ->schema([
                        Select::make('search_excluded_refs')
                            ->statePath('description.search_excluded_refs')
                            ->multiple()
                            ->searchable()
                            ->options(fn (?Product $record) => static::candidates($record, $store))
                            ->placeholder(__('admin.catalog.products.tabs.placement.placeholders.search_excluded_refs'))
                            ->hiddenLabel()
                            // DB -> form: {"category": [5]} -> ["category:5"]
                            ->formatStateUsing(fn ($state) => static::flattenRefs((array) $state))
                            // Form -> DB: ["category:5"] -> {"category": [5]}
                            ->dehydrateStateUsing(fn ($state) => static::groupRefs((array) $state)),
                    ]),
            ]);
    }

    // Render badge by JS
    protected static function badgeUpdateJs(string $countExpression, string $color = 'primary'): string
    {
        return <<<JS
            const tabRoot = \$el.closest('.fi-sc-tabs-tab');
            const dataKey = tabRoot.id.replace('form.', '');
            const button  = document.querySelector(`[data-tab-key="\${dataKey}"]`);
            const count   = {$countExpression};

            let badge = button?.querySelector('.fi-badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'fi-color fi-color-{$color} fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm';
                    badge.innerHTML = '<span class="fi-badge-label-ctn"><span class="fi-badge-label"></span></span>';
                    button?.querySelector('.fi-tabs-item-label')?.after(badge);
                }
                badge.querySelector('.fi-badge-label').textContent = count;
            } else {
                badge?.remove();
            }
            JS;
    }

    
    /**
     * Options of the "excluded" select: every entity the index currently contains plus every current exclusion
     * (so an excluded entity can be brought back, even if it is no longer linked to the product).
     *
     * @return array<string, string>  "type:id" => "Type: name"
     */
    protected static function candidates(?Product $record, $store): array
    {
        if ($record === null) {
            return [];
        }

        $description = ProductDescription::query()
            ->where('product_id', $record->id)
            ->where('store_id', $store->id)
            ->first();

        if ($description === null) {
            return [];
        }

        // Entities the index was built from at the last save
        $refs = static::indexedRefs($record->id, $store->id);

        // ...plus current exclusions
        foreach ($description->search_excluded_refs ?? [] as $type => $ids) {
            $refs[$type] = array_values(array_unique([...($refs[$type] ?? []), ...array_map('intval', (array) $ids)]));
        }

        $locale    = app()->getLocale();
        $overrides = app(SearchIndexer::class)->nameOverrides($description);
        $items     = [];

        foreach (SearchRefType::cases() as $type) {
            $ids = $refs[$type->value] ?? [];

            if ($ids === []) {
                continue;
            }

            // Global reference-book names are the fallback when the product has no override
            $globalNames = static::modelClass($type)::query()
                ->whereIn('id', $ids)
                ->get()
                ->mapWithKeys(fn ($model) => [$model->id => $model->name]);

            $typeItems = [];

            foreach ($ids as $id) {
                $override = trim((string) ($overrides[$type->value][$id][$locale] ?? ''));
                $name     = $override !== '' ? $override : ($globalNames[$id] ?? "#{$id}");

                $typeItems["{$type->value}:{$id}"] = static::typeLabel($type) . ': ' . $name;
            }

            asort($typeItems);
            $items += $typeItems;
        }

        return $items;
    }

    /**
     * Union of search_included_refs over all language rows of this product in this store.
     *
     * @return array<string, int[]>
     */
    protected static function indexedRefs(int $productId, int $storeId): array
    {
        $merged = [];

        DB::table('product_search_index')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->pluck('search_included_refs')
            ->each(function ($json) use (&$merged) {
                foreach (json_decode((string) $json, true) ?? [] as $type => $ids) {
                    $merged[$type] = array_values(array_unique([...($merged[$type] ?? []), ...array_map('intval', $ids)]));
                }
            });

        return $merged;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected static function modelClass(SearchRefType $type): string
    {
        return match ($type) {
            SearchRefType::Category       => Category::class,
            SearchRefType::Manufacturer   => Manufacturer::class,
            SearchRefType::Tag            => Tag::class,
            SearchRefType::Attribute      => Attribute::class,
            SearchRefType::AttributeValue => AttributeValue::class,
            SearchRefType::Option         => Option::class,
            SearchRefType::OptionValue    => OptionValue::class,
        };
    }

    protected static function typeLabel(SearchRefType $type): string
    {
        return match ($type) {
            SearchRefType::Category       => FacetType::Category->getLabel(),
            SearchRefType::Manufacturer   => FacetType::Manufacturer->getLabel(),
            SearchRefType::Tag            => FacetType::Tag->getLabel(),
            SearchRefType::Attribute      => __('admin.catalog.products.tabs.placement.labels.attribute_group'),
            SearchRefType::AttributeValue => FacetType::AttributeValue->getLabel(),
            SearchRefType::Option         => __('admin.catalog.products.tabs.placement.labels.option_group'),
            SearchRefType::OptionValue    => FacetType::OptionValue->getLabel(),
        };
    }

    /**
     * {"category": [5], "option_value": [31]} -> ["category:5", "option_value:31"]
     */
    protected static function flattenRefs(array $refs): array
    {
        if (array_is_list($refs)) {
            return $refs; // already flat
        }

        $flat = [];

        foreach ($refs as $type => $ids) {
            foreach ((array) $ids as $id) {
                $flat[] = "{$type}:{$id}";
            }
        }

        return $flat;
    }

    /**
     * ["category:5", "option_value:31"] -> {"category": [5], "option_value": [31]}
     * Unknown types and non-numeric ids are dropped. An empty result is saved as an empty array.
     */
    protected static function groupRefs(array $flat): array
    {
        $refs = [];

        foreach ($flat as $value) {
            [$type, $id] = array_pad(explode(':', (string) $value, 2), 2, null);

            if (SearchRefType::tryFrom((string) $type) === null || ! ctype_digit((string) $id)) {
                continue;
            }

            $refs[$type][] = (int) $id;
        }

        return array_map(function (array $ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);

            return $ids;
        }, $refs);
    }
}