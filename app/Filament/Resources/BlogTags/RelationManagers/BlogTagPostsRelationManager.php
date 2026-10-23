<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Blog\BlogPost;
use Filament\Actions\{Action, AttachAction, DetachAction, DetachBulkAction};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlogTagPostsRelationManager extends RelationManager
{
    protected static string $relationship = 'blogPosts';

    protected ?array $excludedIds = null; // One time request cache
    protected ?array $primaryIds = null; // One time request cache, reset in setPrimaryCategory()

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current category
        return BlogPostsTable::configure($table)
            ->recordTitleAttribute('name')
            ->reorderable('blog_post_tag.sort_order')
            ->defaultSort('blog_post_tag.sort_order')
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple()
                    ->label(__('admin.common.buttons.attach_record'))
                    ->modalHeading(__('admin.common.helpers.manager_page_attach_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $this->getOwnerRecord()?->name]))
                    ->after(fn() => $this->dispatch('refresh-tabs'))
            ])
            ->recordAction(null) // Reset previous actions (remove "edit on click")
            ->recordActions([
                Action::make('editProduct')
                    ->label(__('filament-actions::edit.single.label'))
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (BlogPost $record): string => BlogPostResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->after(fn() => $this->dispatch('refresh-tabs')),
            ])
            ->toolbarActions([
                DetachBulkAction::make()
                    ->after(fn() => $this->dispatch('refresh-tabs')),
            ]);
    }

    public function getTableHeading(): string
    {
        return __('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $this->getOwnerRecord()?->name]);
    }
}