<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Tables\TranslatableColumnState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature'),

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
