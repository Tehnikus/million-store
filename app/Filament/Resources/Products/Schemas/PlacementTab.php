<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\FacetType;
use App\Models\Catalog\Category;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Product;
use App\Models\Catalog\Tag;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;

class PlacementTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('placement')
            ->badge(fn(?Product $record) => $record ? FacetIndex::where('product_id', $record->id)->where('store_id', $store->id)->whereIn('facet_type_id', [FacetType::Category, FacetType::Manufacturer, FacetType::Tag])->count() : null)
            ->schema([

                // Category part 
                Section::make(__('admin.catalog.products.fields.categories'))
                    ->description(__('admin.catalog.products.helpers.facet_categories'))
                    ->schema([
                        Repeater::make('facet_categories')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.fields.category'))->markAsRequired(),
                                TableColumn::make(__('admin.catalog.products.fields.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                                TableColumn::make(__('admin.catalog.products.fields.is_primary_category'))->markAsRequired()->width('120px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.fields.category'))
                                    ->options(fn() => static::categoryChoices($store->id))
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
                                    ->live(),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.fields.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')
                                    ->default(0),
                                Toggle::make('is_primary')
                                    ->distinct()
                                    ->required()
                                    ->fixIndistinctState()
                                    ->label(__('admin.catalog.products.fields.is_primary_category')),
                            ])
                            ->rule(fn() => function (string $attribute, $value, Closure $fail) {
                                $hasPrimary = collect($value)->contains(fn($item) => !empty($item['is_primary']));
                                if (!$hasPrimary) {
                                    $fail(__('admin.catalog.products.errors.no_primary_category'));
                                }
                            })
                            ->addActionLabel(__('admin.catalog.products.buttons.add_category'))
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->maxItems(static::categoryChoices($store->id)->count())
                            ->label(__('admin.catalog.products.fields.categories'))
                            ->hiddenLabel(),
                        Callout::make()
                            ->visible(function () use ($store) {
                                return static::categoryChoices($store->id)->count() == 0;
                            })
                            ->description(__('admin.catalog.products.errors.no_categories'))
                            ->danger()
                            ->columnSpanFull(),
                    ]),

                // Manufacturer part
                Section::make(__('admin.catalog.products.fields.manufacturers'))
                    ->description(__('admin.catalog.products.helpers.facet_manufacturers'))
                    ->schema([
                        Repeater::make('facet_manufacturers')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.fields.manufacturer')),
                                TableColumn::make(__('admin.catalog.products.fields.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                                TableColumn::make(__('admin.catalog.products.fields.is_primary_manufacturer'))->width('120px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.fields.manufacturers'))
                                    ->options(fn() => static::manufacturerChoices($store->id))
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
                                    ->live(),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.fields.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')
                                    ->default(0),
                                Toggle::make('is_primary')
                                    ->distinct()
                                    ->fixIndistinctState()
                                    ->label(__('admin.catalog.products.fields.is_primary_manufacturer')),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->maxItems(static::manufacturerChoices($store->id)->count())
                            ->addActionLabel(__('admin.catalog.products.buttons.add_manufacturer'))
                            ->label(__('admin.catalog.products.fields.manufacturers'))
                            ->hiddenLabel()
                            ->visible(function () use ($store) {
                                return static::manufacturerChoices($store->id)->count() > 0;
                            }),
                    ]),

                Section::make(__('admin.catalog.products.fields.product_tags'))
                    ->description(__('admin.catalog.products.helpers.facet_tags'))
                    ->schema([
                        Repeater::make('facet_tags')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.fields.product_tags')),
                                TableColumn::make(__('admin.catalog.products.fields.sort_order'))->width('120px')->wrapHeader()->alignCenter(),
                            ])
                            ->schema([
                                Select::make('facet_value_id')
                                    ->label(__('admin.catalog.products.fields.tag'))
                                    ->options(fn() => static::tagChoices($store->id))
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
                                    ->live(),
                                TextInput::make('sort_order')
                                    ->label(__('admin.catalog.products.fields.sort_order'))
                                    ->numeric(),
                                Hidden::make('facet_group_id')->default(0),
                            ])
                            ->reorderable(false)
                            ->addActionLabel(__('admin.catalog.products.buttons.add_tag'))
                            ->defaultItems(0)
                            ->maxItems(static::tagChoices($store->id)->count())
                            ->label(__('admin.catalog.products.fields.product_tags'))
                            ->hiddenLabel()
                            ->visible(function () use ($store) {
                                return static::tagChoices($store->id)->count() > 0;
                            }),
                    ])
            ]);
    }

    // Cache option list for single request or multiple requests if Octane is used
    protected static function categoryChoices(int $storeId): Collection
    {
        $key = "category_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Category::query()
            ->where('store_id', $storeId)
            ->get()
            ->mapWithKeys(fn (Category $category) => [$category->id => $category->name]);

        Context::add($key, $choices->all());

        return $choices;
    }

    // Cache option list for single request or multiple requests if Octane is used
    protected static function manufacturerChoices(int $storeId): Collection
    {
        $key = "manufacturer_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Manufacturer::query()
            ->where('store_id', $storeId)
            ->get()
            ->mapWithKeys(fn (Manufacturer $manufacturer) => [$manufacturer->id => $manufacturer->name]);

        Context::add($key, $choices->all());

        return $choices;
    }

    // Cache option list for single request or multiple requests if Octane is used
    protected static function tagChoices(int $storeId): Collection
    {
        $key = "tag_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Tag::query()
            ->where('store_id', $storeId)
            ->get()
            ->mapWithKeys(fn (Tag $tag) => [$tag->id => $tag->name]);

        Context::add($key, $choices->all());

        return $choices;
    }
}