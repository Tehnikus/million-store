<?php

namespace App\Filament\Resources\BlogAuthors\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogAuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('blogPosts'))
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('admin.blog.authors.fields.avatar'))
                    ->disk('public')
                    ->circular()
                    ->imageHeight(70)
                    ->checkFileExistence(false)
                    ->alignment(Alignment::Center)
                    ->width('1%')
                    ->extraImgAttributes(['loading' => 'lazy', 'style' => 'border-radius: 50%; margin: -0.7rem 0']),

                MultilangTextColumn::make('name')
                    ->recordColumnAll('name')
                    ->label(__('admin.blog.authors.fields.name')),

                TextColumn::make('blog_posts_count')
                    ->counts('blogPosts')
                    ->badge()
                    ->label(__('admin.blog.authors.fields.posts_count'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

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