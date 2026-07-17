<?php
namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('admin.currencies.fields.name'))
                    ->helperText(__('admin.currencies.helpers.name')),

                TextInput::make('iso_code')
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true)
                    ->label(__('admin.currencies.fields.iso_code'))
                    ->helperText(__('admin.currencies.helpers.iso_code')),

                TextInput::make('sign')
                    ->required()
                    ->maxLength(10)
                    ->label(__('admin.currencies.fields.sign'))
                    ->helperText(__('admin.currencies.helpers.sign')),

                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->step(0.000001)
                    ->label(__('admin.currencies.fields.rate'))
                    ->helperText(__('admin.currencies.helpers.rate')),

                Toggle::make('rate_default')
                    ->default(false)
                    ->label(__('admin.currencies.fields.rate_default'))
                    ->helperText(__('admin.currencies.helpers.rate_default')),

                Toggle::make('is_active')
                    ->default(true)
                    ->label(__('admin.currencies.fields.is_active')),
            ]);
    }
}