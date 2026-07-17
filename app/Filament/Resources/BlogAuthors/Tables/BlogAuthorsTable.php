<?php

namespace App\Filament\Resources\BlogAuthors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogAuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.blog.authors.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('blog_posts_count')
                    ->label(__('admin.blog.authors.fields.posts_count'))
                    ->counts('blogPosts')
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('sort_order')
                    ->label(__('admin.blog.authors.fields.sort_order'))
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center),

                IconColumn::make('is_active')
                    ->label(__('admin.blog.authors.fields.is_active'))
                    ->boolean()
                    ->width('1%')
                    ->alignment(Alignment::Center),

                TextColumn::make('created_at')
                    ->label(__('admin.blog.authors.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->width('1%')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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