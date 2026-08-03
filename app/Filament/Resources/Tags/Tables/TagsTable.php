<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Support\Tables\TranslatableColumnState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->disk('public')
                    ->state(fn ($record) => collect($record->images ?? [])->pluck('conversions.miniature')->filter()->values()->first()) // or ->all()
                    ->imageHeight(70)
                    ->checkFileExistence(false)
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->extraImgAttributes(['loading' => 'lazy', 'style' => 'border-radius: 10px; margin: -0.7rem'])
                    ->label(__('admin.common.fields.image')),

                TextColumn::make('name')
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => TranslatableColumnState::resolve($record, 'name'))
                    ->label(__('admin.catalog.categories.model_label_singular')),
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
