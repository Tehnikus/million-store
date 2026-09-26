<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Models\Customer\CustomerGroup;
use App\Models\Catalog\OptionValue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;

class PricesTab
{
    public static function make($store, $currencies, $languages): Tab
    {
        return Tab::make('prices')
            ->badge(fn($record) => ($count = $record?->priceTiers()->where('store_id', $store->id)->count()) ? $count : null)
            ->schema([

                Repeater::make('priceTiers')
                    ->schema([

                        Group::make([

                            // Price badge
                            FusedGroup::make([
                                ...collect($languages)->map(fn($language) => 
                                    TextInput::make("name.$language->locale")
                                        ->prefix($language->locale)
                                        ->label(__('admin.catalog.products.fields.price_name'))
                                        ->placeholder(__('admin.catalog.products.fields.price_name'))
                                        ->hiddenLabel(),
                                )
    
                            ])
                            ->label(__('admin.catalog.products.fields.price_name'))
                            ->helperText(__('admin.catalog.products.helpers.price_name')),
    
    
                            Select::make('customer_group_id')
                                ->options(fn() => static::customerGroupChoices($store->id))
                                ->placeholder(__('admin.catalog.products.fields.everyone'))
                                ->label(__('admin.catalog.products.fields.prices_customer_group')),
                            Toggle::make('is_base')
                                ->distinct()
                                ->fixIndistinctState()
                                ->live()
                                ->label(__('admin.catalog.products.fields.is_base')),
                            Toggle::make('is_discount')
                                ->visible(fn(Get $get) => !$get('is_base'))
                                ->label(__('admin.catalog.products.fields.is_discount')),
                            TextInput::make('priority')->numeric()->default(1),
                            FusedGroup::make([
                                DateTimePicker::make('date_valid_from')
                                    ->native(false)
                                    ->placeholder(__('admin.catalog.products.fields.valid_from'))
                                    ->columnSpan(1),
                                DateTimePicker::make('date_valid_until')
                                    ->native(false)
                                    ->placeholder(__('admin.catalog.products.fields.valid_until'))
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                            TextInput::make('valid_quantity')->numeric()->nullable(),
                        ])
                        ->columns(1)
                        ->columnSpan(1),


                        Fieldset::make('price')
                            ->label(__('admin.catalog.products.fields.prices'))
                            ->schema(fn(Get $get) => [
                                // Text::make('debug')->content(fn() => 'DEBUG = ' . json_encode($get('../../optionSignatures'))),
                                ...static::priceFields($get, $currencies),
                            ])
                            ->columnSpan(1),
                    ])
                    ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->defaultItems(1)
                    ->minItems(1)
                    ->live()
                    ->deletable(fn (Get $get): bool => count($get('priceTiers')) > 1 )
                   
            ]);
    }

    protected static function priceFields(Get $get, $currencies): array
    {
        return collect(static::liveCombinations($get))
            ->map(fn ($combo, $comboKey) =>
                FusedGroup::make(
                    collect($currencies)->map(fn ($currency) =>
                        TextInput::make("price.{$comboKey}.{$currency->id}")
                            ->numeric()
                            ->prefix($currency->sign)
                            ->hiddenLabel()
                            ->placeholder($currency->name)
                    )->toArray()
                )->label($combo['label'])
            )->toArray();
    }

    protected static function liveCombinations(Get $get): Collection
    {
        $optionSignatures = collect($get('../../optionSignatures'));
        $optionsDescription = collect($get('../../description.options_description'));

        $rows = $optionSignatures
            ->map(fn ($signature) => collect($signature['selectedOptions'] ?? [])
                ->filter(fn ($row) => filled($row['option_select'] ?? null) && filled($row['option_value_select'] ?? null))
                ->mapWithKeys(fn ($row) => [(int) $row['option_select'] => (int) $row['option_value_select']])
                ->all())
            ->filter()
            ->unique(fn ($signature) => UpsertProduct::signatureKey($signature))
            ->values();

        if ($rows->isEmpty()) {
            return collect(['base' => ['label' => __('admin.catalog.products.fields.base_price'), 'signature' => null]]);
        }

        return $rows->mapWithKeys(fn ($signature) => [
            UpsertProduct::signatureKey($signature) => [
                'label'     => static::signatureLabel($signature, $optionsDescription),
                'signature' => $signature,
            ],
        ]);
    }

    protected static function signatureLabel(array $signature, Collection $optionsDescription): string
    {
        return collect($signature)->map(function ($valueId, $groupId) use ($optionsDescription) {
            $group = $optionsDescription->first(fn ($g) => (int) ($g['option_id'] ?? null) === (int) $groupId);
            $value = collect($group['description'] ?? [])
                ->first(fn ($v) => (int) ($v['option_value_id'] ?? null) === (int) $valueId);

            return $value['name'][app()->getLocale()]
                ?? OptionValue::find($valueId)?->name
                ?? "#{$valueId}";
        })->implode(', ');
    }

    protected static function customerGroupChoices(int $storeId): Collection
    {
        $key = "customer_group_choices.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = CustomerGroup::where('store_id', $storeId)->pluck('name', 'id');
        Context::add($key, $choices->all());

        return $choices;
    }
}