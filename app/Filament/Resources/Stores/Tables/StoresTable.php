<?php

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;


class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.stores.fields.name')),
                TextColumn::make('host')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.stores.fields.host'))
                    ->alignment(Alignment::Center),
                // Language badges column
                TextColumn::make('languages.name')
                    ->state(function (Model $record): array {
                        return $record->languages
                            ->filter(fn ($language) => (bool) $language->pivot?->is_active)
                            ->pluck('name')
                            ->toArray();
                    })
                    ->label(__('admin.stores.fields.languages'))
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->width('1%'),
                // Default currency
                TextColumn::make('currencies.iso_code')
                    ->state(function (Model $record): array {
                        return $record->currencies
                            ->filter(fn ($currency) => (bool) $currency->pivot?->is_active)
                            ->pluck('iso_code')
                            ->toArray();
                    })
                    ->label(__('admin.stores.fields.currencies'))
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->width('1%'),
                TextColumn::make('countries.iso_code')
                    ->state(function (Model $record): array {
                        return $record->countries
                            ->filter(fn ($country) => (bool) $country->pivot?->is_active)
                            ->pluck('name')
                            ->toArray();
                    })
                    ->label(__('admin.stores.fields.countries'))
                    ->alignment(Alignment::Center),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.stores.fields.is_active'))
                    ->width('1%'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
