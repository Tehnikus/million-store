<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductOptionValue;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;


class OptionsTab
{
    public static function schema($storeId, $languages, $currencies): array    
    {
        
        return [
            Repeater::make('productOptions')
                ->relationship('productOptions', modifyQueryUsing: fn ($query) => $query
                    ->with(['productOptionValues.prices', 'productOptionValues.optionValue'])
                    ->where('store_id', $storeId)
                )
                ->schema([
                    // Hidden::make('store_id')->default($storeId),

                    Select::make('option_id')
                        ->options(fn () => static::optionChoices($storeId))
                        ->afterStateUpdated(fn (Set $set) => $set('productOptionValues', [])) // Also an array can be passed to create empty option value form TODO
                        ->searchable()
                        ->preload()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required()
                        ->live()
                        ->label(__('admin.catalog.options.fields.group')),

                    Repeater::make('productOptionValues')
                        ->relationship('productOptionValues')
                        ->schema([

                                // Product related data
                                Group::make([
                                     // Required
                                    Hidden::make('store_id')->default($storeId),
        
                                    // The form itself
                                    Select::make('option_value_id')
                                        ->options(fn (Get $get) => static::optionValueChoices($get('../../option_id')))
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->afterStateUpdated(function ($state, Set $set, $livewire) {
                                            // Safely return if select is empty
                                            if (blank($state)) return;

                                            $defaultOptionData = OptionValue::find($state)?->toArray();
                                            if (!$defaultOptionData) return;

                                            $productId = $livewire->getRecord()?->id;

                                            $overrideOptionData = $productId
                                                ? ProductOptionValue::query()
                                                    ->where('product_id', $productId)
                                                    ->where('option_value_id', $state)
                                                    ->first()
                                                    ?->toArray()
                                                : null;

                                            foreach ($defaultOptionData['name'] as $locale => $name) {
                                                $set("name.{$locale}", $overrideOptionData['name'][$locale] ?? $name);
                                            }
                                            foreach ($defaultOptionData['description'] as $locale => $description) {
                                                $set("description.{$locale}", $overrideOptionData['description'][$locale] ?? $description);
                                            }
                                        })
                                        ->label(__('admin.catalog.options.fields.option_name')),
    
                                    Toggle::make('is_default')
                                        ->distinct()
                                        ->fixIndistinctState()
                                        ->label(__('admin.catalog.options.fields.is_default')),

                                    TextInput::make('sku')
                                        ->nullable()
                                        ->label(__('admin.catalog.products.fields.sku')),
    
                                    Toggle::make('stock_subtract')
                                        ->label(__('admin.catalog.products.fields.stock_subtract')),
                                    
                                ])->columnSpan(1),
    
                                Group::make([
                                   self::priceTable($currencies)
                                ])->columnSpan(1),
    
                                // Option value related data
                                Group::make([
                                    ...self::optionValueDescriptionsForm($languages)
                                ])->columnSpan(3),
                        ])
                        ->minItems(1)
                        ->default([])
                        ->maxItems(fn (Get $get) => static::optionValueChoices($get('option_id'))->count())
                        ->collapsible()
                        ->collapsed(fn($operation) => $operation !== 'create')
                        ->itemLabel(function (array $state, Get $get): ?string {
                            $optionId = $get('option_id');
                            return static::optionValueChoices($optionId)->get($state['option_value_id'] ?? null);
                        })
                        ->reorderable()
                        ->orderColumn('sort_order')
                        ->columns(5)
                        ->addActionAlignment('end')
                        ->addActionLabel(__('admin.catalog.products.buttons.add_option_value'))
                        ->label(__('admin.catalog.options.fields.values'))
                ])
                ->maxItems(fn () => static::optionChoices($storeId)->count())
                ->collapsible()
                ->collapsed(fn($operation) => $operation !== 'create')
                ->itemLabel(function (array $state) use ($storeId): ?string {
                    $optionName = static::optionChoices($storeId)->get($state['option_id'] ?? null);

                    if (blank($optionName)) {
                        return null;
                    }

                    $valueChoices = static::optionValueChoices($state['option_id'] ?? null);

                    $valueIds = filled($state['id'] ?? null)
                        ? ProductOptionValue::where('product_option_id', $state['id'])->pluck('option_value_id')
                        : collect($state['productOptionValues'] ?? [])->pluck('option_value_id');

                    $valueNames = $valueIds
                        ->filter()
                        ->map(fn ($id) => $valueChoices->get($id))
                        ->filter()
                        ->implode(', ');

                    return $valueNames !== '' ? "{$optionName}: {$valueNames}" : $optionName;
                })
                ->reorderable()
                ->orderColumn('sort_order')
                ->addActionLabel(__('admin.catalog.products.buttons.add_option'))
                ->label(__('admin.catalog.options.navigation_label'))
                ->hiddenLabel()
        ];
    }

    protected static function priceTable($currencies): Repeater
    {

        return Repeater::make('prices')
            ->relationship('prices')
            // ->table([
            //     TableColumn::make(__('admin.catalog.products.fields.prices')),
            // ])
            ->schema([
                Hidden::make('currency_id'),
                TextInput::make('price_modifier')
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
            ->compact()
            // ->disableLabel()
            ->label(__('admin.catalog.products.fields.prices'));
    }

    protected static function optionValueDescriptionsForm($languages)
    {
        return [
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
                                ->hiddenLabel()
                                ->columnSpanFull(),

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
            )
        ];
    }


    protected static function optionValueChoices(?int $optionId): Collection
    {
        if (blank($optionId)) {
            return collect();
        }

        $key = "option_value_choices.{$optionId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = OptionValue::query()
            ->where('option_id', $optionId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }

    protected static function optionChoices(int $storeId): Collection
    {
        $key = "option_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Option::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->pluck('name', 'id');

        Context::add($key, $choices->all());

        return $choices;
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.options');
    }
}