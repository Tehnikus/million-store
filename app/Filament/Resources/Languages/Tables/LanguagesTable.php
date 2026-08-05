<?php

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('currency'); // ?
            })
            ->columns([
                ImageColumn::make('image')
                    ->label(__('admin.global.languages.fields.flag'))
                    ->disk('public')
                    ->imageSize(32),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.global.languages.fields.name')),

                TextColumn::make('iso_code')
                    ->searchable()
                    ->label(__('admin.global.languages.fields.iso_code'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('locale')
                    ->searchable()
                    ->label(__('admin.global.languages.fields.locale'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('ts_config')
                    ->searchable()
                    ->label(__('admin.global.languages.fields.fulltext_search_language'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('currency.name')
                    ->label(__('admin.global.languages.fields.default_currency'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('stores.name')
                    ->label(__('admin.global.languages.fields.stores'))
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->separator(','),

                IconColumn::make('is_active')
                    ->sortable()
                    ->label(__('admin.global.languages.fields.is_active'))
                    ->alignment(Alignment::Center)
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
