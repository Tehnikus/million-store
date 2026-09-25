<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Customer\CustomerGroup;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;

class PricesTab
{
    public static function make($store, $currencies): Tab
    {
        return Tab::make('prices')
            ->schema([

                Repeater::make('priceTiers')
                    ->schema([
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
                            DateTimePicker::make('valid_from'),
                            DateTimePicker::make('valid_until'),
                        ]),
                        TextInput::make('valid_quantity')->numeric()->nullable(),

                        Fieldset::make('price')
                            ->label(__('admin.catalog.products.fields.prices'))
                            ->schema(fn(Get $get) => [
                                // Text::make('debug')->content(fn() => 'DEBUG = ' . json_encode($get('../../optionSignatures'))),
                                ...static::priceFields($get, $currencies),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                    ->columnSpanFull()
                    ->minItems(1),
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
            ->unique(fn ($signature) => static::signatureKey($signature))
            ->values();

        if ($rows->isEmpty()) {
            return collect(['base' => ['label' => __('admin.catalog.products.fields.base_price'), 'signature' => null]]);
        }

        return $rows->mapWithKeys(fn ($signature) => [
            static::signatureKey($signature) => [
                'label'     => static::signatureLabel($signature, $optionsDescription),
                'signature' => $signature,
            ],
        ]);
    }

    protected static function signatureLabel(array $signature, Collection $optionsDescription): string
    {
        return collect($signature)->map(function ($valueId, $groupId) use ($optionsDescription) {
            $group = $optionsDescription->first(fn ($g) => (int) ($g['option_id'] ?? null) === (int) $groupId);
            $value = collect($group['option_values_description'] ?? [])
                ->first(fn ($v) => (int) ($v['option_value_id'] ?? null) === (int) $valueId);

            return $value['name'][app()->getLocale()]
                ?? OptionValue::find($valueId)?->name
                ?? "#{$valueId}";
        })->implode(', ');
    }

    protected static function signatureKey(array $signature): string
    {
        ksort($signature);
        return collect($signature)->map(fn ($v, $k) => "{$k}-{$v}")->implode('_');
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