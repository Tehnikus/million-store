<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Actions\UpsertProduct;
use App\Models\Customer\CustomerGroup;
// use App\Models\Catalog\OptionValue;
use Arr;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
// use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
// use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class PricesTab
{
    
    public static function make($store, $currencies, $languages): Tab
    {
        $defaultCurrency = $currencies->firstWhere('rate_default', true);
        return Tab::make('prices')
            ->badge(fn($record) => ($count = $record?->priceTiers()->where('store_id', $store->id)->count()) ? $count : null)
            ->schema([

                Repeater::make('priceTiers')
                    ->schema([

                        Fieldset::make(__('admin.catalog.products.tabs.prices.labels.price_terms'))
                            ->schema([
                                // Price badge
                                FusedGroup::make([
                                    ...collect($languages)->map(
                                        fn($language) =>
                                        TextInput::make("name.$language->locale")
                                            ->prefix($language->locale)
                                            ->label(__('admin.catalog.products.tabs.prices.labels.price_name'))
                                            ->placeholder(__('admin.catalog.products.tabs.prices.labels.price_name'))
                                            ->hiddenLabel(),
                                    )

                                ])
                                ->label(__('admin.catalog.products.tabs.prices.labels.price_name'))
                                ->helperText(__('admin.catalog.products.tabs.prices.helpers.price_name')),


                                Select::make('customer_group_id')
                                    ->options(fn() => static::customerGroupChoices($store->id))
                                    ->placeholder(__('admin.catalog.products.tabs.prices.labels.no_group'))
                                    ->label(__('admin.catalog.products.tabs.prices.labels.customer_group'))
                                    ->helperText(__('admin.catalog.products.tabs.prices.helpers.customer_group')),

                                // Group::make([
                                //     Toggle::make('is_base')
                                //         ->distinct()
                                //         ->fixIndistinctState()
                                //         ->live()
                                //         ->columnSpan(1)
                                //         ->label(__('admin.catalog.products.tabs.prices.labels.is_base')),
                                //     Toggle::make('is_discount')
                                //         ->visible(fn(Get $get) => !$get('is_base'))
                                //         ->columnSpan(1)
                                //         ->label(__('admin.catalog.products.tabs.prices.labels.is_discount')),
                                // ])
                                // ->columns(2),
                                ToggleButtons::make('status')
                                    ->options([
                                        'is_base'           => __('admin.catalog.products.tabs.prices.labels.is_base'),
                                        'discount_amount'   => __('admin.catalog.products.tabs.prices.labels.discount_amount'),
                                        'discount_percent'  => __('admin.catalog.products.tabs.prices.labels.discount_percent'),
                                    ])
                                    ->colors([
                                        'is_base'           => 'success',
                                        'discount_amount'   => 'primary',
                                        'discount_percent'  => 'primary',
                                    ])
                                    ->default('is_base')
                                    ->disabled(fn (Get $get): bool => \count($get('../../priceTiers')) <= 1)
                                    // ->fullWidth()
                                    ->required()
                                    ->grouped()
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['discount'])
                                    ->inline(),
                                
                                TextInput::make('discount')
                                    ->label(function(Get $get) {
                                        return match ($get('status')) {
                                            'discount_percent' => __('admin.catalog.products.tabs.prices.labels.discount_percent'),
                                            'discount_amount'  => __('admin.catalog.products.tabs.prices.labels.discount_amount'),
                                            default            => null,
                                        };
                                    })
                                    ->numeric()
                                    ->visible(fn(Get $get) => $get('status') !== 'is_base')
                                    ->live(onBlur:true, debounce: 500)
                                    ->skipRenderAfterStateUpdated(true)
                                    ->prefixIcon(function(Get $get) {
                                        return match ($get('status')) {
                                            'discount_percent' => Heroicon::PercentBadge,
                                            'discount_amount'  => Heroicon::OutlinedMinus,
                                            default            => null,
                                        };
                                    })
                                    ->afterStateUpdatedJs(<<<JS
                                       (() => {
                                            const tiers = \$get('../../priceTiers');
                                            const baseKey = Object.keys(tiers).find(k => tiers[k].status === 'is_base');
                                            if (baseKey === undefined) return;

                                            const basePrices   = tiers[baseKey].price ?? {};
                                            const myPrices     = \$get('price') ?? {};
                                            const discountType = \$get('status');
                                            const discountValue = parseFloat(\$state) || 0;

                                            Object.keys(myPrices).forEach(comboKey => {
                                                Object.keys(myPrices[comboKey]).forEach(currencyId => {
                                                    const base = basePrices[comboKey]?.[currencyId];
                                                    if (base === null || base === undefined || base === '') return;

                                                    const newPrice = discountType === 'discount_percent'
                                                        ? base * (1 - discountValue / 100)
                                                        : base - discountValue;

                                                    \$set(`price.\${comboKey}.\${currencyId}`, Math.max(newPrice, 0).toFixed(2));
                                                });
                                            });
                                        })();
                                    JS),

                                Group::make([
                                    TextInput::make('priority')
                                        ->numeric()
                                        // ->live()
                                        ->default(fn (Get $get): int => \count($get('../../priceTiers')))
                                        ->columnSpan(1)
                                        ->label(__('admin.catalog.products.tabs.prices.labels.priority'))
                                        ->helperText(__('admin.catalog.products.tabs.prices.helpers.priority')),
                                    TextInput::make('valid_quantity')
                                        ->numeric()
                                        ->nullable()
                                        ->columnSpan(1)
                                        ->label(__('admin.catalog.products.tabs.prices.labels.valid_quantity'))
                                        ->helperText(__('admin.catalog.products.tabs.prices.helpers.valid_quantity')),
                                ])
                                ->columns(2),

                                FusedGroup::make([
                                    DateTimePicker::make('date_valid_from')
                                        ->native(false)
                                        ->placeholder(__('admin.catalog.products.tabs.prices.labels.valid_from'))
                                        ->columnSpan(1),
                                    DateTimePicker::make('date_valid_until')
                                        ->native(false)
                                        ->placeholder(__('admin.catalog.products.tabs.prices.labels.valid_until'))
                                        ->columnSpan(1),
                                ])
                                ->columns(2)
                                ->label(__('admin.catalog.products.tabs.prices.labels.valid_dates'))
                                ->helperText(__('admin.catalog.products.tabs.prices.helpers.valid_dates')),

                            ])
                            ->columns(1)
                            ->columnSpan(1),

                        Fieldset::make('price')
                            ->label(__('admin.catalog.products.tabs.prices.labels.price_values'))
                            ->schema(fn(Get $get) => [
                                // Text::make('debug')->content(fn() => 'DEBUG = ' . json_encode($get('../../optionSignatures'))),
                                ...static::priceFields($get, $currencies),
                            ])
                            ->columnSpan(1)
                            ->dense(),
                    ])
                    ->addActionLabel(__('admin.catalog.products.tabs.prices.buttons.add_price_tier'))
                    ->label(__('admin.catalog.products.tabs.prices.labels.price_tiers'))
                    ->belowLabel(__('admin.catalog.products.tabs.prices.helpers.price_tiers'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->defaultItems(1)
                    ->minItems(1)
                    ->live(onBlur: true)
                    ->collapsible()
                    ->reorderable()
                    ->cloneable()
                    ->orderColumn('priority')
                    ->deletable(fn (Get $get): bool => \count($get('priceTiers')) > 1)
                    ->afterStateUpdatedJs(static::badgeUpdateJs('Object.keys($state ?? {}).length'))
                    ->itemLabel(function (array $state) use ($store, $currencies, $defaultCurrency): ?HtmlString {
                        $name            = $state['name'][app()->getLocale()] ?? Arr::first($state['name'] ?? []);
                        $priceText       = static::priceSummary($state['price'] ?? [], $defaultCurrency);
                        $customerGroupId = $state['customer_group_id'] ?? null;
                        $customerGroup   = $customerGroupId ? static::customerGroupChoices($store->id)->get($customerGroupId) : '';
                        // $isDiscount      = ($state['is_discount'] ?? false) ? '<span style="color: var(--primary-500); font-weight: 600;">' . e(__('admin.catalog.products.tabs.prices.item_label.is_discount')) . '</span>' : '';
                        // $isBase          = ($state['is_base'] ?? false) ? '<span style="color: var(--success-500); font-weight: 600;">' . e(__('admin.catalog.products.tabs.prices.item_label.is_base')) . '</span>' : '';
                        $quantity        = ($state['valid_quantity'] ?? false) ? __('admin.catalog.products.tabs.prices.item_label.from_qty', ['qty' => $state['valid_quantity']]) : '';
                        $validFrom       = static::formatDate($state['date_valid_from'] ?? null);
                        $validUntil      = static::formatDate($state['date_valid_until'] ?? null);

                        $validity = match (true) {
                            $validFrom && $validUntil => __('admin.catalog.products.tabs.prices.item_label.dates_valid') . " {$validFrom} - {$validUntil}",
                            (bool) $validFrom         => __('admin.catalog.products.tabs.prices.item_label.valid_from') . " {$validFrom}",
                            (bool) $validUntil        => __('admin.catalog.products.tabs.prices.item_label.valid_until') . " {$validUntil}",
                            default                   => null,
                        };

                        $parts = array_filter([
                            // $isBase,
                            // $isDiscount,
                            e($name),
                            $priceText,
                            e($customerGroup),
                            e($quantity),
                            e($validity),
                        ]);

                        return filled($parts) ? new HtmlString(implode(', ', $parts)) : null;
                    })
            ]);
    }

    protected static function priceFields(Get $get, $currencies): array
    {
        $defaultCurrencyId  = $currencies->firstWhere('rate_default', true)?->id;
        $combinations       = collect(static::liveCombinations($get));
        $comboKeys          = $combinations->keys();
        return $combinations->map(fn ($combo, $comboKey) =>
                FusedGroup::make(
                    collect($currencies)->map(fn ($currency) =>
                        TextInput::make("price.{$comboKey}.{$currency->id}")
                            ->numeric()
                            ->required()
                            ->prefix($currency->sign)
                            ->label(__('admin.catalog.products.tabs.prices.labels.price_input', ['currency' => $currency->name]))
                            ->hiddenLabel()
                            ->placeholder($currency->name)
                            ->skipRenderAfterStateUpdated(true)
                            ->suffixActions([
                                Action::make(__('admin.catalog.products.tabs.prices.buttons.exchange_rate'))
                                    ->icon(Heroicon::OutlinedCalculator)
                                    ->actionJs(<<<JS
                                        \$set('price.{$comboKey}.{$currency->id}', \$get('price.{$comboKey}.{$defaultCurrencyId}') * {$currency->rate})
                                    JS)
                                    ->tooltip(__('admin.catalog.products.tabs.prices.buttons.exchange_rate'))
                                    ->visible($currency->id !== $defaultCurrencyId && $defaultCurrencyId !== null),
                                Action::make(__('admin.catalog.products.tabs.prices.buttons.fill_all_combinations'))
                                    ->icon(Heroicon::ChevronUpDown)
                                    ->actionJs(static::fillAllCombinationsJs($comboKeys, $comboKey, $currency->id))
                                    ->visible($comboKeys->count() > 1)
                                    ->requiresConfirmation()
                                    ->tooltip(__('admin.catalog.products.tabs.prices.buttons.fill_all_combinations')),
                            ])
                            ->suffixIcon(fn() => ($currency->id == $defaultCurrencyId) ? Heroicon::CheckCircle : null)
                            ->suffixIconColor('gray')
                    )->toArray()
                )->label($combo['label'])
            )->toArray();
    }

    protected static function fillAllCombinationsJs(Collection $comboKeys, string $currentComboKey, $currencyId): string
    {
        $assignments = $comboKeys
            ->reject(fn ($key) => $key === $currentComboKey)
            ->map(fn ($key) => "\$set('price.{$key}.{$currencyId}', value);")
            ->implode("\n");

        return <<<JS
            const value = \$get('price.{$currentComboKey}.{$currencyId}');
            {$assignments}
            JS;
    }

    protected static function liveCombinations(Get $get): Collection
    {
        $optionsDescription = collect($get('../../description.options_description'));

        $rows = collect($get('../../combinations') ?? [])
            ->map(fn ($key) => UpsertProduct::signatureFromKey($key))
            ->filter()
            ->unique(fn ($signature) => UpsertProduct::signatureKey($signature))
            ->values();

        if ($rows->isEmpty()) {
            return collect(['base' => ['label' => __('admin.catalog.products.tabs.prices.labels.base_price'), 'signature' => null]]);
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

            return Arr::first(array_filter($value['name'] ?? [])) ?? "#{$valueId}";
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

    protected static function priceSummary(array $priceGrid, $defaultCurrency): ?string
    {
        if (!$defaultCurrency) {
            return null;
        }

        $currencyId = $defaultCurrency->id;

        if (array_key_exists('base', $priceGrid)) {
            $amount = $priceGrid['base'][$currencyId] ?? null;
            return filled($amount) ? static::formatPrice((float) $amount, $defaultCurrency) : null;
        }

        $amounts = collect($priceGrid)
            ->map(fn ($amounts) => $amounts[$currencyId] ?? null)
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => (float) $v);

        if ($amounts->isEmpty()) {
            return null;
        }

        $min = $amounts->min();
        $max = $amounts->max();

        return $min === $max
            ? static::formatPrice($min, $defaultCurrency)
            : static::formatPrice($min, $defaultCurrency) . ' - ' . static::formatPrice($max, $defaultCurrency);
    }

    protected static function formatPrice(float $amount, $currency): string
    {
        // return $currency->sign . number_format($amount, 2);
        return Number::currency($amount, in: $currency->iso_code);
    }

    protected static function formatDate(?string $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)->translatedFormat('d.m.Y');
        } catch (\Throwable) {
            return null;
        }
    }

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