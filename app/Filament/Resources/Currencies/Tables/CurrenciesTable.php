<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.global.currencies.fields.name')),
                TextColumn::make('iso_code')
                    ->badge()
                    ->searchable()
                    ->alignment('center')
                    ->label(__('admin.global.currencies.fields.iso_code')),
                TextColumn::make('sign')
                    ->badge()
                    ->alignment('center')
                    ->label(__('admin.global.currencies.fields.sign')),
                TextColumn::make('rate')
                    ->alignment('right')
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->label(__('admin.global.currencies.fields.rate')),
                IconColumn::make('rate_default')
                    ->boolean()
                    ->sortable()
                    ->alignment('center')
                    ->label(__('admin.global.currencies.fields.rate_default'))
                    ->width('1%'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->alignment('center')
                    ->label(__('admin.global.currencies.fields.is_active'))
                    ->width('1%'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
