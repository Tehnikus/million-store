<?php

namespace App\Filament\Resources\Attributes\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('values')) // Eagel loading of attribute values
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.attributes.fields.group')),

                TextColumn::make('values.name')
                    ->badge()
                    ->limitList(10)
                    ->expandableLimitedList(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
