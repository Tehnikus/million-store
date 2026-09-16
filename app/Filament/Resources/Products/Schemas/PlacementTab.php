<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Tag;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;

class PlacementTab
{
    public static function make($store, $languages): Tab
    {
        return Tab::make('placement')
            ->schema([

            // Category part 
            Repeater::make('facet_categories')
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.category'))->markAsRequired(),
                    TableColumn::make(__('admin.catalog.products.fields.is_primary_category'))->width('240px')->markAsRequired(),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.parent_category'))
                        ->options(fn () => static::categoryChoices($store->id))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                            $set('facet_group_id', Category::find($state)?->parent_id ?? 0)
                        )
                        ->live(),
                    Hidden::make('facet_group_id')
                        ->default(0),
                    Toggle::make('is_primary')
                        ->distinct()
                        ->required()
                        ->fixIndistinctState()
                        ->label(__('admin.catalog.products.fields.is_primary_category')),
                ])
                ->rule(fn () => function (string $attribute, $value, Closure $fail) {
                    $hasPrimary = collect($value)->contains(fn ($item) => !empty($item['is_primary']));
                    if (!$hasPrimary) {
                        $fail(__('admin.catalog.products.helpers.no_primary_category'));
                    }
                })
                ->reorderable(true)
                ->addActionLabel(__('admin.catalog.products.buttons.add_category'))
                ->columnSpanFull()
                ->minItems(1)
                ->maxItems(static::categoryChoices($store->id)->count())
                ->label(__('admin.catalog.products.fields.categories'))
                ->belowLabel(__('admin.catalog.products.helpers.facet_categories')),
            Callout::make()
                ->description(__('admin.catalog.products.helpers.no_categories'))
                ->danger()
                ->visible(function() use ($store) {
                    return static::categoryChoices($store->id)->count() == 0;
                })
                ->columnSpanFull(),
            
            // Manufacturer part
            Repeater::make('facet_manufacturers')
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.manufacturer')),
                    TableColumn::make(__('admin.catalog.products.fields.is_primary_manufacturer'))->width('240px'),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.manufacturers'))
                        ->options(fn () => static::manufacturerChoices($store->id))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                            $set('facet_group_id', Manufacturer::find($state)?->parent_id ?? 0)
                        )
                        ->live(),
                    Hidden::make('facet_group_id')
                        ->default(0),
                    Toggle::make('is_primary')
                        ->distinct()
                        ->fixIndistinctState()
                        ->label(__('admin.catalog.products.fields.primary_manufacturer')),
                ])
                ->reorderable(true)
                ->maxItems(static::manufacturerChoices($store->id)->count())
                ->columnSpanFull()
                ->addActionLabel(__('admin.catalog.products.buttons.add_manufacturer'))
                ->label(__('admin.catalog.products.fields.manufacturers'))
                ->belowLabel(__('admin.catalog.products.helpers.facet_manufacturers'))
                ->visible(function() use ($store) {
                    return static::manufacturerChoices($store->id)->count() > 0;
                }),

            Repeater::make('facet_tags')
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.product_tags')),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.tag'))
                        ->options(fn () => static::tagChoices($store->id))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    Hidden::make('facet_group_id')->default(0),
                ])
                ->reorderable(true)
                ->columnSpanFull()
                ->addActionLabel(__('admin.catalog.products.buttons.add_tag'))
                ->maxItems(static::tagChoices($store->id)->count())
                ->label(__('admin.catalog.products.fields.product_tags'))
                ->belowLabel(__('admin.catalog.products.helpers.facet_tags'))
                ->visible(function() use ($store) {
                    return static::tagChoices($store->id)->count() > 0;
                }),
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