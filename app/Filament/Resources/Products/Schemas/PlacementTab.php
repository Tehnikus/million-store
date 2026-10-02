<?php

namespace App\Filament\Resources\Products\Schemas;

use Closure;
use App\Domain\Catalog\FacetType;
use App\Models\Catalog\{Category, FacetIndex, Manufacturer, Tag};
use Filament\Forms\Components\{Hidden, Repeater, Repeater\TableColumn, Select, TextInput, Toggle};
use Filament\Schemas\Components\{Callout, Section, Tabs\Tab, Utilities\Get, Utilities\Set};
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
                    ])
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
}