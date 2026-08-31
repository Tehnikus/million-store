<?php

namespace App\Filament\Resources\BlogTags\Pages;

use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Filament\Resources\BlogTags\BlogTagResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Livewire\Livewire;

class ManageBlogPostTags extends ManageRelatedRecords
{
    protected static string $resource = BlogTagResource::class;

    protected static string $relationship = 'blogPosts';

    public function table(Table $table): Table
    {
        return BlogPostsTable::configure($table)
            ->headerActions([
                AttachAction::make()
                    ->color('primary')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordAction(null) // Reset previous actions (remove "edit on click")
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::BlogPosts->icon();
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::BlogPosts->labelPlural();
    }

    public function getTitle(): string
    {
        return __('admin.blog.tags.helpers.manage_posts_title', ['name' => $this->getOwnerRecord()?->name]);
    }

    public static function getNavigationBadge(): ?string
    {
        return Livewire::current()->getRecord()->blogPosts()->count();
    }
}
