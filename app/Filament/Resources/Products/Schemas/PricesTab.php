<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Customer\CustomerGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;




class PricesTab
{
    public static function schema(): array
    {
        $languages  = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();
        $currencies = Filament::getTenant()->currencies()->wherePivot('is_active', true)->get();

        return [
            Group::make([
                Repeater::make('product_price_tiers')
                    ->relationship(
                        name: 'priceTiers',
                        modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                    )
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $data['store_id'] = Filament::getTenant()->id;
                        return $data;
                    })
                    ->schema([
                        // Nested repeater with all prices for this tier
                        // See nested relation
                        Repeater::make('prices')
                            ->relationship('prices')
                            ->table([
                                TableColumn::make(__('admin.catalog.products.fields.prices')),
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

                        Group::make([
                            Toggle::make('is_discount')
                                ->label(__('admin.catalog.products.fields.discount'))
                                ->helperText(__('admin.catalog.products.helpers.discount'))
                                ->inline(false)
                                ->live(),

                            Select::make('customer_group_id')
                                ->relationship(
                                    name: 'customerGroup',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn(Builder $query) => $query->where('store_id', Filament::getTenant()->id),
                                )
                                ->searchable()
                                ->preload()
                                ->label(__('admin.catalog.products.fields.prices_customer_group'))
                                ->helperText(__('admin.catalog.products.helpers.prices_customer_group'))
                                ->live(),

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
                                ->columns(2),

                            TextInput::make('valid_quantity')
                                ->numeric()
                                ->label(__('admin.catalog.products.fields.valid_quantity'))
                                ->helperText(__('admin.catalog.products.helpers.valid_quantity')),

                            TextInput::make('priority')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->label(__('admin.catalog.products.fields.priority'))
                                ->helperText(__('admin.catalog.products.helpers.priority')),
                            Group::make(
                                collect($languages)->map(
                                    fn($language) =>
                                    TextInput::make("name.{$language->locale}")
                                        // ->required()
                                        ->prefix($language->locale)
                                        ->columnSpanFull()
                                        ->placeholder(__('admin.catalog.products.fields.price_name'))
                                        ->label(__('admin.catalog.products.fields.price_name'))
                                        ->helperText(__('admin.catalog.products.helpers.price_name'))
                                        ->live(),
                                )->all(),
                            ),
                        ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->orderColumn('priority')
                    ->itemLabel(
                        fn(array $state): ?string =>
                        ($state['name'][app()->getLocale()] ?? null) . ' ' .


                            // (!empty($state['prices']['price']) ? implode(', ', $state['prices']['price']) : null) . ' ' .
                        ($state['customer_group_id'] ? CustomerGroup::find($state['customer_group_id'])?->name : null) . ' ' .
                        ($state['is_discount'] ? __('admin.catalog.products.fields.discount') : null) . ' ' .
                        ($state['valid_from'] !== null ? __('admin.catalog.products.fields.valid_from_short') . ' ' . $state['valid_from'] : '') . ' ' .
                        ($state['valid_until'] !== null ? __('admin.catalog.products.fields.valid_until_short') . ' ' . $state['valid_until'] : '') . ' '
                    )
                    ->itemNumbers()
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->label(__('admin.catalog.products.fields.price_tiers'))
                    ->addActionLabel(__('admin.catalog.products.buttons.add_price_tier'))
                    ->deleteAction(
                        fn($action) => $action->action(function (array $arguments, Repeater $component): void {
                            $state = $component->getState();
                            $keys = array_keys($state);

                            // Check if the item being deleted is the first key
                            if (!empty($keys) && $arguments['item'] === $keys[0]) {
                                Notification::make()
                                    ->danger()
                                    // ->title(__('admin.catalog.products.errors.last_price_title'))
                                    ->body(__('admin.catalog.products.errors.last_price_text'))
                                    ->send();

                                return;
                            }

                            // Proceed with deletion for other items
                            unset($state[$arguments['item']]);
                            $component->state($state);
                        })
                    ),
            ])
                ->columnSpanFull(),
        ];
    }

    public static function label(): string
    {
        return __('admin.catalog.products.tabs.prices');
    }
}