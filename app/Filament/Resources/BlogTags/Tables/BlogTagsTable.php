<?php

namespace App\Filament\Resources\BlogTags\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Support\Enums\Alignment;

class BlogTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll('name')
                    ->label(__('admin.blog.tags.fields.name')),

                TextColumn::make('blog_posts_count')
                    ->label(__('admin.blog.tags.fields.posts_count'))
                    ->counts('blogPosts')
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->badge(),

                ToggleColumn::make('is_menu')
                    ->label(__('admin.blog.tags.fields.is_menu'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                ToggleColumn::make('is_active')
                    ->label(__('admin.blog.tags.fields.is_active'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('created_at')
                    ->label(__('admin.blog.tags.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('1%')
                    ->alignment(Alignment::Center),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
