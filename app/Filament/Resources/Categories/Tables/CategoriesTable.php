<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Tables\TranslatableColumnState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CategoriesTable
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

                SelectColumn::make('parent_id')
                    ->optionsRelationship(name: 'parentId', titleAttribute: 'name')
                    ->label(__('admin.catalog.categories.fields.parent_id')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.categories.fields.is_active')),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.categories.fields.show_in_facets')),
                
                TextColumn::make('sort_order')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.categories.fields.sort_order')),
            ])
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
