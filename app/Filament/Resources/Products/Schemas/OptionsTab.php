<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;


class OptionsTab
{
    public static function schema(): array    
    {
        
        $storeId    = Filament::getTenant()->id;
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        
        return [
            Repeater::make('productOptions')
                ->relationship('productOptions')
                // ->table([
                //     TableColumn::make('Option')->width('200px'),
                //     TableColumn::make('Value'),
                // ])
                // ->compact()
                ->schema([
                    // Required
                    Hidden::make('store_id')->default($storeId),

                    Select::make('option_id')
                        ->options(fn() => Option::where('store_id', Filament::getTenant()->id)->pluck('name', 'id'))
                        ->afterStateUpdated(fn (Set $set) => $set('productOptionValues', [])) // Also an array can be passed to create empty option value form TODO
                        // ->searchable()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required()
                        ->live()
                        ->label('Option group'),

                    Repeater::make('productOptionValues')
                        ->relationship('productOptionValues')
                        // ->table([
                        //     TableColumn::make('Product settings')->width('300px'),
                        //     TableColumn::make('price')->width('300px'),
                        //     TableColumn::make('Description'),
                        // ])
                        // ->compact()
                        ->schema([

                                // Product related data
                                Group::make([
                                     // Required
                                    Hidden::make('store_id')->default($storeId),
        
                                    // The form itself
                                    Select::make('option_value_id')
                                        ->options(fn(Get $get) => OptionValue::where('option_id', $get('../../option_id'))->pluck('name', 'id'))
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
    
                                    Toggle::make('is_default')
                                        ->distinct()
                                        ->fixIndistinctState(),

                                    TextInput::make('sku')
                                        ->nullable(),
    
                                    Toggle::make('stock_subtract')
                                        ->label('Subtract from stock'),
                                    
                                ])->columnSpan(1),
    
                                Group::make([
                                   self::priceTable()
                                ])->columnSpan(1),
    
                                // Option value related data
                                Group::make([
                                    TextInput::make('name')
                                ])->columnSpan(3),
                        ])
                        ->minItems(1)
                        ->default([])
                        ->maxItems(function (Get $get): ?int {
                            $optionId = $get('option_id');

                            // Restrict option value creation if option group is not selected
                            if (!$optionId) {
                                return 0;
                            }

                            return OptionValue::where('option_id', $optionId)->count();
                        })
                        ->collapsible()
                        ->collapsed(fn($operation) => $operation !== 'create')
                        ->columns(5)
                        ->addActionAlignment(Alignment::End)
                        ->addActionLabel(__('admin.catalog.products.buttons.add_option_value'))
                ])
                ->maxItems(fn (): int => Option::where('store_id', $storeId)->count())
                ->collapsible()
                ->collapsed(fn($operation) => $operation !== 'create')
                ->addActionAlignment(Alignment::Start)
                ->addActionLabel(__('admin.catalog.products.buttons.add_option'))
        ];
    }

    protected static function priceTable(): Repeater
    {
        $currencies = Filament::getTenant()->currencies()->wherePivot('is_active', true)->get();

        return Repeater::make('prices')
            ->relationship('prices')
            // ->table([
            //     TableColumn::make(__('admin.catalog.products.fields.prices')),
            // ])
            ->schema([
                Hidden::make('currency_id'),
                TextInput::make('price')
                    ->numeric()
                    ->step(0.01)
                    ->default(0)
                    ->prefix(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->sign)
                    ->placeholder(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->name)
                    ->live()
                    ->hiddenLabel(),
            ])
            // Display all available currencies
            ->default(
                collect($currencies)->map(fn($currency) => [
                    'currency_id' => $currency->id,
                    'price' => null,
                ])->all()
            )
            ->columnSpanFull()
            ->addable(false)
            ->deletable(false)
            // ->compact()
            ->disableLabel()
            ->label(__('admin.catalog.products.fields.prices'));
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.options');
    }
}