<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Customer\CustomerGroup;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class PricesTab
{
    public static function schema($storeId, $languages, $currencies): array
    {

        return [
            Group::make([
                Repeater::make('product_price_tiers')
                    ->relationship(
                        name: 'priceTiers',
                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', $storeId),
                    )
                    ->mutateRelationshipDataBeforeCreateUsing(function ($storeId, array $data): array {
                        $data['store_id'] = $storeId;
                        return $data;
                    })
                    ->schema([
                        // Nested repeater with all prices for this tier
                        // See nested relation
                        Repeater::make('prices')
                            ->relationship('prices')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.fields.prices'))->markAsRequired(),
                            ])
                            ->schema([
                                Group::make([
                                    Hidden::make('currency_id'),
                                    TextInput::make('price')
                                        ->numeric()
                                        ->step(0.01)
                                        ->required()
                                        ->prefix(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->sign)
                                        ->placeholder(fn(Get $get) => $currencies->firstWhere('id', $get('currency_id'))?->name)
                                        ->live(),
                                ])
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
                            ->disableLabel()
                            ->label(__('admin.catalog.products.fields.prices')),

                        // Price name badge
                        Fieldset::make(__('admin.catalog.products.fields.price_name'))
                            ->schema([
                                ...collect($languages)->map(
                                    fn($language) =>
                                    TextInput::make("name.{$language->locale}")
                                        ->prefix($language->locale)
                                        ->placeholder(__('admin.catalog.products.fields.price_name'))
                                        ->label(__('admin.catalog.products.fields.price_name'))
                                        ->hiddenLabel()
                                        ->live(),
                                )->all(),
                                Text::make(__('admin.catalog.products.helpers.price_name'))
                                    ->columnSpanFull()
                            ])
                            ->columns(\count($languages))
                            ->columnSpanFull()
                            ->dense(true),

                        Fieldset::make(__('admin.catalog.products.fields.price_terms'))
                            ->schema([
                                Group::make([
                                    Toggle::make('is_discount')
                                        ->label(__('admin.catalog.products.fields.discount'))
                                        ->helperText(__('admin.catalog.products.helpers.discount'))
                                        ->inline(false)
                                        ->live(),
                                        
                                    TextInput::make('valid_quantity')
                                        ->numeric()
                                        ->label(__('admin.catalog.products.fields.valid_quantity'))
                                        ->helperText(__('admin.catalog.products.helpers.valid_quantity'))
                                        ->live(onBlur: true),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),


                                Group::make([
                                    DateTimePicker::make('valid_from')
                                        ->label(__('admin.catalog.products.fields.valid_from'))
                                        ->helperText(__('admin.catalog.products.helpers.valid_from'))
                                        ->live(),

                                    DateTimePicker::make('valid_until')
                                        ->label(__('admin.catalog.products.fields.valid_until'))
                                        ->helperText(__('admin.catalog.products.helpers.valid_until'))
                                        ->live(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                                Select::make('customer_group_id')
                                    ->relationship(
                                        name: 'customerGroup',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', $storeId),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->label(__('admin.catalog.products.fields.prices_customer_group'))
                                    ->helperText(__('admin.catalog.products.helpers.prices_customer_group'))
                                    ->live()
                                    ->columnSpanFull(),

                        ])
                        ->dense(true)
                        ->columnSpanFull(),
                    ])
                    ->orderColumn('priority')
                    // ->itemLabel(
                    //     fn(array $state): ?string =>
                    //     ($state['name'][app()->getLocale()] ?? Arr::first($state['name'] ?? []) ?? null) . ' ' .


                    //     // (!empty($state['prices']['price']) ? implode(', ', $state['prices']['price']) : null) . ' ' .
                    //     ($state['customer_group_id'] ? CustomerGroup::find($state['customer_group_id'])?->name : null) . ' ' .
                    //     ($state['is_discount'] ? __('admin.catalog.products.fields.discount') : null) . ' ' .
                    //     ($state['valid_from'] !== null ? __('admin.catalog.products.fields.valid_from_short') . ' ' . $state['valid_from'] : '') . ' ' .
                    //     ($state['valid_until'] !== null ? __('admin.catalog.products.fields.valid_until_short') . ' ' . $state['valid_until'] : '') . ' '
                    // )
                    ->itemLabel(function (Get $get, array $state, Repeater $component): HtmlString {

                        $labelArray = [];
                        foreach ($state as $key => $value) {
                            if ($value === null || $value === false || !in_array($key, ['name', 'is_discount', 'customer_group_id', 'valid_from', 'valid_until', 'valid_quantity'])) continue;


                            if ($key === 'name') {
                                $labelArray[$key] = 
                                    '<span style="font-weight: bold; color: var(--primary-400)">' . 
                                        ($state['name'][app()->getLocale()] ?? Arr::first($state['name'])) . 
                                    '</span>'
                                ;
                                continue;
                            }

                            if ($key == 'is_discount') {
                                $labelArray[$key] = 
                                    '<span style="font-weight: bold; color: var(--danger-400)">' . 
                                        __('admin.catalog.products.fields.discount') .
                                    '</span>'
                                ;
                                continue;
                            }

                            $labelArray[$key] = __("admin.catalog.products.fields.{$key}_short") . ': ' . $value;

                        }

                        // $state2 = $component->getState();
                        // dd($state2);


                        return new HtmlString(implode(', ', $labelArray ?? []));
                    })
                    // ->itemNumbers()
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(fn($operation) => $operation !== 'create')
                    ->label(__('admin.catalog.products.fields.price_tiers'))
                    ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                    ->addActionAlignment('end')
            ])
            ->columnSpanFull(),
        ];
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.prices');
    }
}