<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ManufacturersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),
                
                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.manufacturers.model_label_singular')),

                SelectColumn::make('parent_id')
                    ->optionsRelationship(name: 'parent', titleAttribute: 'name')
                    ->wrapHeader()
                    ->width('220px')
                    ->label(__('admin.catalog.manufacturers.fields.parent_id')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.is_active')),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.show_in_facets')),
                
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
