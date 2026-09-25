<?php

namespace App\Filament\Resources\StoreInfoPages\Tables;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\MultilangTextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;

class StoreInfoPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateIcon(NavigationItem::InfoPages->icon())
            ->emptyStateHeading(__('admin.stores.info_pages.navigation_label'))
            ->emptyStateDescription(__('admin.stores.info_pages.helpers.group_title'))
            ->columns([

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.blog.posts.fields.name')),

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
