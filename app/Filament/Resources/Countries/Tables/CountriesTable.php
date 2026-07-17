<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.countries.fields.name'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('iso_code')
                    ->label(__('admin.countries.fields.iso_code'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->badge()
                    ->searchable(),

                TextColumn::make('defaultCurrency.iso_code')
                    ->label(__('admin.countries.fields.default_currency_id'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->badge(),

                IconColumn::make('is_eu_member')
                    ->label(__('admin.countries.fields.is_eu_member'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->boolean()
                    ->width('1%'),

                IconColumn::make('is_active')
                    ->label(__('admin.countries.fields.is_active'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->boolean()
                    ->width('1%'),
            ])
            ->filters([
                //
            ])
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
