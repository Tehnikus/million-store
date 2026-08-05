<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('currency'); // ?
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.global.countries.fields.name'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('iso_code')
                    ->label(__('admin.global.countries.fields.iso_code'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->badge()
                    ->searchable()
                    ->width('150px')
                    ->wrapHeader(),

                TextColumn::make('currency.iso_code')
                    ->label(__('admin.global.countries.fields.default_currency_id'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->badge()
                    ->width('100px')
                    ->wrapHeader(),

                IconColumn::make('is_eu_member')
                    ->label(__('admin.global.countries.fields.is_eu_member'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->boolean()
                    ->width('1%'),

                IconColumn::make('is_active')
                    ->label(__('admin.global.countries.fields.is_active'))
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->boolean()
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
