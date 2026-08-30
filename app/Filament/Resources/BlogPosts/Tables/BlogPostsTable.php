<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('blogTags')->with('author')->with('comments'))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.blog.posts.fields.name')),

                TextColumn::make('author.name')
                    ->label(__('admin.blog.authors.model_label_singular'))
                    ->alignment(Alignment::Center)
                    ->width('1%'),

                TextColumn::make('blogTags.name')
                    ->label(__('admin.blog.tags.navigation_label'))
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->width('1%'),

                TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label(__('admin.blog.comments.navigation_label'))
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->width('1%')
                    ->wrapHeader(),

                ToggleColumn::make('is_active')
                    ->label(__('admin.blog.posts.fields.is_active'))
                    ->alignment(Alignment::Center)
                    ->width('100px'),

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
