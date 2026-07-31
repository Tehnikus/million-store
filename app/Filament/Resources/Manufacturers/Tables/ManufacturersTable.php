<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ManufacturersTable
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
                    ->searchable()
                    ->sortable()
                    ->label(__('admin.blog.posts.fields.name')),

                SelectColumn::make('parent_id')
                    ->optionsRelationship(name: 'parentId', titleAttribute: 'name')
                    ->label(__('admin.catalog.manufacturers.fields.parent_id')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.is_active')),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.manufacturers.fields.show_in_facets')),
                
                TextColumn::make('sort_order')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.manufacturers.fields.sort_order')),
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
