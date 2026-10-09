<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Search\SearchRefType;
use App\Models\Catalog\{Attribute, AttributeValue, Option, OptionValue};
use Closure;
use App\Domain\Catalog\FacetType;
use App\Models\Catalog\{Category, FacetIndex, Manufacturer, Tag};
use Filament\Forms\Components\{Hidden, Repeater, Repeater\TableColumn, Select, TextInput, Toggle};
use Filament\Schemas\Components\{Callout, FusedGroup, Section, Tabs\Tab};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;

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
                        // Options come from the CURRENT form state (not from the DB), so they follow the repeaters
                        // on this tab and on the Attributes / Options tabs without any re-render triggers:
                        // options() is a Closure, so Filament marks the select as having dynamic options and fetches them
                        // from the server (getOptionsForJs) every time the dropdown is opened.
                        Select::make('search_excluded_refs')
                            ->statePath('description.search_excluded_refs')
                            ->multiple()
                            ->searchable()
                            ->options(fn (Get $get, ?array $state) => static::excludedOptions($get, $store->id, (array) $state))
                            ->placeholder(__('admin.catalog.products.tabs.placement.placeholders.search_excluded_refs'))
                            ->hiddenLabel()
                            // DB -> form happens in EditProduct::mutateFormDataBeforeFill() via flattenRefs(), NOT in formatStateUsing():
                            // Select multiple applies OptionsArrayStateCast before formatStateUsing runs, and that cast drops
                            // every non-scalar item, so the grouped {"category": [5]} shape would arrive here as [].
                            // Form -> DB: ["category:5"] -> {"category": [5]}
                            // Exclusions of entities that are no longer linked to the product are dropped here,
                            // so re-linking a category later does not silently bring back an old exclusion.
                            ->dehydrateStateUsing(fn ($state, Get $get) => static::groupRefs(array_values(array_intersect(
                                (array) $state,
                                array_keys(static::linkedRefs($get, $store->id)),
                            )))),
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
     * Options of the "excluded from search" select, grouped by entity type:
     *   ['Categories' => ['category:5' => 'Blood pressure monitors'], 'Attributes' => [...], ...]
     *
     * Values that are selected but no longer linked to the product (a category was removed in the repeater)
     * are still returned as options, with their raw key as label: Select validates that every selected value
     * is among its options, so without them saving would fail. Selected options are hidden in the dropdown anyway,
     * and dehydrateStateUsing() drops them on save.
     *
     * @param string[] $selected  current state of the select, ["category:5", ...]
     * @return array<string, array<string, string>>
     */
    protected static function excludedOptions(Get $get, int $storeId, array $selected): array
    {
        $linked = static::linkedRefs($get, $storeId);
        $groups = [];

        foreach ($linked as $key => $item) {
            $groups[$item['group']][$key] = $item['label'];
        }

        foreach (array_diff(array_map('strval', $selected), array_keys($linked)) as $staleKey) {
            $groups['—'][$staleKey] = $staleKey;
        }

        return $groups;
    }

    /**
     * Every entity linked to the product in the current form state, in the same shape the indexer uses
     * (attribute and option GROUPS are separate entries from their VALUES).
     * Names come from the form state where it has them (per-product overrides of attributes/options),
     * otherwise from the cached *Choices() lists. No extra queries beyond those caches.
     *
     * @return array<string, array{group: string, label: string}>  keyed "type:id", in display order
     */
    protected static function linkedRefs(Get $get, int $storeId): array
    {
        $items = [];

        $simple = [
            [SearchRefType::Category,     'facet_categories',    Category::categoryChoices($storeId)],
            [SearchRefType::Manufacturer, 'facet_manufacturers', Manufacturer::manufacturerChoices($storeId)],
            [SearchRefType::Tag,          'facet_tags',          Tag::tagChoices($storeId)],
        ];

        foreach ($simple as [$type, $path, $choices]) {
            $group = static::typeGroupLabel($type);

            foreach ((array) $get($path) as $row) {
                $id = (int) ($row['facet_value_id'] ?? 0);

                if ($id > 0) {
                    $items["{$type->value}:{$id}"] = ['group' => $group, 'label' => (string) ($choices[$id] ?? "#{$id}")];
                }
            }
        }

        // Attributes: description.attributes_description = [{attribute_id, name: {locale}, description: [{attribute_value_id, name}]}]
        $attributeGroup = static::typeGroupLabel(SearchRefType::Attribute);
        $attributeNames = Attribute::attributeChoices($storeId);

        foreach ((array) $get('description.attributes_description') as $row) {
            $groupId = (int) ($row['attribute_id'] ?? 0);

            if ($groupId <= 0) {
                continue;
            }

            $groupName = static::stateName($row['name'] ?? null) ?? (string) ($attributeNames[$groupId] ?? "#{$groupId}");
            $items[SearchRefType::Attribute->value . ":{$groupId}"] = [
                'group' => $attributeGroup,
                'label' => $groupName . ' ' . __('admin.catalog.products.tabs.placement.labels.search_whole_group'),
            ];

            $valueNames = null; // loaded only if some value has no name in the form state

            foreach ((array) ($row['description'] ?? []) as $value) {
                $valueId = (int) ($value['attribute_value_id'] ?? 0);

                if ($valueId <= 0) {
                    continue;
                }

                $valueName = static::stateName($value['name'] ?? null)
                    ?? (string) (($valueNames ??= AttributeValue::attributeValueChoices($groupId))[$valueId] ?? "#{$valueId}");

                $items[SearchRefType::AttributeValue->value . ":{$valueId}"] = ['group' => $attributeGroup, 'label' => "{$groupName} → {$valueName}"];
            }
        }

        // Options: description.options_description = [{option_id, name: {locale}, description: [{option_value_id, name}]}]
        $optionGroup = static::typeGroupLabel(SearchRefType::Option);
        $optionNames = Option::optionChoices($storeId);

        foreach ((array) $get('description.options_description') as $row) {
            $groupId = (int) ($row['option_id'] ?? 0);

            if ($groupId <= 0) {
                continue;
            }

            $groupName = static::stateName($row['name'] ?? null) ?? (string) ($optionNames[$groupId] ?? "#{$groupId}");
            $items[SearchRefType::Option->value . ":{$groupId}"] = [
                'group' => $optionGroup,
                'label' => $groupName . ' ' . __('admin.catalog.products.tabs.placement.labels.search_whole_group'),
            ];

            $valueNames = null;

            foreach ((array) ($row['description'] ?? []) as $value) {
                $valueId = (int) ($value['option_value_id'] ?? 0);

                if ($valueId <= 0) {
                    continue;
                }

                $valueName = static::stateName($value['name'] ?? null)
                    ?? (string) (($valueNames ??= OptionValue::optionValueChoices($groupId, $storeId))[$valueId] ?? "#{$valueId}");

                $items[SearchRefType::OptionValue->value . ":{$valueId}"] = ['group' => $optionGroup, 'label' => "{$groupName} → {$valueName}"];
            }
        }

        return $items;
    }

    /**
     * Name from a translatable form-state array: current admin locale first, then the first filled one.
     */
    protected static function stateName(mixed $names): ?string
    {
        if (! is_array($names)) {
            return null;
        }

        $name = trim((string) ($names[app()->getLocale()] ?? ''));

        if ($name !== '') {
            return $name;
        }

        foreach ($names as $candidate) {
            if (($candidate = trim((string) $candidate)) !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Option group header in the select
     */
    protected static function typeGroupLabel(SearchRefType $type): string
    {
        return match ($type) {
            SearchRefType::Category                                 => __('admin.catalog.products.tabs.placement.labels.categories'),
            SearchRefType::Manufacturer                             => __('admin.catalog.products.tabs.placement.labels.manufacturers'),
            SearchRefType::Tag                                      => __('admin.catalog.products.tabs.placement.labels.product_tags'),
            SearchRefType::Attribute, SearchRefType::AttributeValue => __('admin.catalog.products.tabs.attributes.label'),
            SearchRefType::Option, SearchRefType::OptionValue       => __('admin.catalog.products.tabs.options.label'),
        };
    }

    /**
     * {"category": [5], "option_value": [31]} -> ["category:5", "option_value:31"]
     * Public: EditProduct uses it to fill the select (see the comment at the select).
     */
    public static function flattenRefs(array $refs): array
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
    public static function groupRefs(array $flat): array
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