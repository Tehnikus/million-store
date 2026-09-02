<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;

class CategoriesTab
{
    public static function schema($storeId): array
    {
        return [
            Group::make([
                Select::make('parent_id')
                    ->label(__('admin.catalog.products.fields.parent_id'))
                    ->options(fn () => static::categoryChoices($storeId))
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
                        ->options(fn () => static::categoryChoices($storeId))
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

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.categories');
    }
}