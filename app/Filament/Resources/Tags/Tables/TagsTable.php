<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature'),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.tags.model_label_singular')),

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
