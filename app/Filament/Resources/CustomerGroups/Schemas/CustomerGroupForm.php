<?php

namespace App\Filament\Resources\CustomerGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class CustomerGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()->languages()->wherePivot('is_active', true)->get();

        return $schema
            ->components([

                Fieldset::make(__('admin.common.fields.name'))
                    ->schema(
                        collect($languages)->map(
                            fn($language) =>
                            TextInput::make("name.{$language->locale}")
                                ->columnSpanFull()
                                ->prefix($language->locale)
                                ->placeholder(__('admin.common.fields.name'))
                                ->hiddenLabel()
                                ->required()
                        )->all()
                    )
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer_groups.fields.code'))
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->helperText(__('admin.customers.customer_groups.helpers.code')),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer_groups.fields.price_modifier_percent'))
                    ->schema([
                        TextInput::make('price_modifier_percent')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(-100)
                            ->prefix('%')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->helperText(__('admin.customers.customer_groups.helpers.price_modifier_percent')),
                    ])
                    ->columnSpanFull(),
                Fieldset::make(__('admin.customers.customer_groups.fields.group_settings'))
                    ->schema([
                        Toggle::make('free_shipping')
                            ->label(__('admin.customers.customer_groups.fields.free_shipping')),
                        Toggle::make('requires_approval')
                            ->label(__('admin.customers.customer_groups.fields.requires_approval')),
                        Toggle::make('show_prices')
                            ->label(__('admin.customers.customer_groups.fields.show_prices')),
                        Toggle::make('tax_exempt')
                            ->label(__('admin.customers.customer_groups.fields.tax_exempt')),
                        Toggle::make('is_default')
                            ->label(__('admin.customers.customer_groups.fields.is_default')),
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->columnSpanFull()
                    ->label(__('admin.customers.customer_groups.fields.is_active')),
            ]);
    }
}
