<?php

namespace App\Filament\Resources\StoreInfoPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;

class StoreInfoPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label(__('admin.blog.posts.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('admin.blog.posts.fields.sort_order'))
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label(__('admin.blog.posts.fields.is_active'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('created_at')
                    ->label(__('admin.blog.posts.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
