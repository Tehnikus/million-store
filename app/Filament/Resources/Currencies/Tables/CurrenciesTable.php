<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;

class CurrenciesTable
{
public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.currencies.fields.name')),
                TextColumn::make('iso_code')
                    ->badge()
                    ->searchable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.currencies.fields.iso_code')),
                TextColumn::make('sign')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.currencies.fields.sign')),
                TextColumn::make('rate')
                    ->alignment(Alignment::Right)
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->label(__('admin.currencies.fields.rate')),
                IconColumn::make('rate_default')
                    ->boolean()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.currencies.fields.rate_default'))
                    ->width('1%'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.currencies.fields.is_active'))
                    ->width('1%'),
            ])
            ->filters([
                //
            ])
            ->persistFiltersInSession() // Save current filters
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
