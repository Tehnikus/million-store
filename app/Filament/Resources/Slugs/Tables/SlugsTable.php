<?php

namespace App\Filament\Resources\Slugs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;

class SlugsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('slug')
                    ->label(__('admin.seo.slugs.fields.url'))
                    ->searchable(),
                TextColumn::make('language.name')
                    ->searchable()
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->label(__('admin.seo.slugs.fields.language')),
                TextColumn::make('sluggable_type')
                    ->formatStateUsing(function (string $state) {
                        $badgeName = explode('\\', $state);
                        $badgeName = end($badgeName);
                        return $badgeName;
                    })
                    ->searchable()
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->label(__('admin.seo.slugs.fields.type')),
                TextColumn::make('sluggable_id')
                    ->numeric()
                    ->sortable()
                    ->width('1%')
                    ->label(__('admin.seo.slugs.fields.id')),
                TextColumn::make('redirectedTo.slug')
                    ->label(__('admin.seo.slugs.fields.redirect'))
                    ->placeholder('--'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->label(__('admin.common.fields.is_active')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->label(__('admin.common.fields.updated_at')),
            ])
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
