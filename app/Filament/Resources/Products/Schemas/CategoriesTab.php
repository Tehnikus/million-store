<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Category;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;


class CategoriesTab
{
    public static function schema(): array
    {
        return [
            Group::make([
                Select::make('parent_id')
                    ->label(__('admin.catalog.products.fields.parent_id'))
                    ->options(
                        fn() => Category::query()
                            ->where('store_id', Filament::getTenant()->id)
                            ->get()
                            ->mapWithKeys(fn(Category $category) => [$category->id => $category->name])
                    )
                    ->searchable()
                    ->preload()
                    ->helperText(__('admin.catalog.products.helpers.parent_id'))
            ])
            ->statePath('description')
            ->columnSpanFull(),

            Repeater::make('facet_categories')
                ->label(__('admin.catalog.products.fields.product_categories'))
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.category')),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.category'))
                        ->options(
                            fn() => Category::query()
                                ->where('store_id', Filament::getTenant()->id)
                                ->get()
                                ->mapWithKeys(fn(Category $category) => [$category->id => $category->name])
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        // Restricts same category selection  
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        // Update parent category input
                        ->afterStateUpdated(
                            fn(Set $set, ?string $state) => $set('facet_group_id', Category::find($state)?->parent_id ?? 0)
                        )
                        ->live(),
                    Hidden::make('facet_group_id')
                        ->default(0)
                ])
                ->reorderable(true) // repeater elements order = sort_order
                ->addActionLabel(__('admin.catalog.products.buttons.add_category'))
                ->columnSpanFull()
                ->defaultItems(0)
                ->helperText(__('admin.catalog.products.helpers.facet_categories')),
        ];
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.categories');
    }
}