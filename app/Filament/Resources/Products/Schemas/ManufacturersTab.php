<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Manufacturer;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;

class ManufacturersTab
{
    public static function schema($storeId): array
    {
        return [
            Group::make([
                Select::make('manufacturer_id')
                    ->label(__('admin.catalog.products.fields.manufacturer_id'))
                    ->options(fn () => static::manufacturerChoices($storeId))
                    ->searchable()
                    ->preload()
                    ->helperText(__('admin.catalog.products.helpers.manufacturer_id'))
            ])
            ->statePath('description')
            ->columnSpanFull(),

            Repeater::make('facet_manufacturers')
                ->label(__('admin.catalog.products.fields.manufacturers'))
                ->table([
                    TableColumn::make(__('admin.catalog.products.fields.manufacturers')),
                ])
                ->schema([
                    Select::make('facet_value_id')
                        ->label(__('admin.catalog.products.fields.manufacturers'))
                        ->options(fn () => static::manufacturerChoices($storeId))
                        ->searchable()
                        ->preload()
                        ->required()
                        // Restricts same manufacturers selection  
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        // Update parent manufacturers input
                        ->afterStateUpdated(
                            fn(Set $set, ?string $state) => $set('facet_group_id', Manufacturer::find($state)?->parent_id ?? 0)
                        )
                        ->live(),
                    Hidden::make('facet_group_id')
                        ->default(0)
                ])
                ->reorderable(true) // repeater elements order = sort_order
                ->addActionLabel(__('admin.catalog.products.buttons.add_manufacturer'))
                ->columnSpanFull()
                ->defaultItems(0)
                ->helperText(__('admin.catalog.products.helpers.facet_manufacturers')),
        ];
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

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.manufacturers');
    }
}