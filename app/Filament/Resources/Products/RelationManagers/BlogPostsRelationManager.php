<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Blog\BlogPost;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlogPostsRelationManager extends RelationManager
{
    protected static string $relationship = 'blogPosts';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $store = Filament::getTenant();
        $parentRecord = $this->getOwnerRecord()->load(['descriptions']);
        $parentTitle = ProductResource::getRecordTitle($parentRecord); 
        return BlogPostsTable::configure($table)
            // ->recordTitleAttribute('descriptions.name')
            ->heading(__('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $parentTitle]))
            ->emptyStateHeading(__('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $parentTitle]))
            ->emptyStateDescription(__('admin.catalog.products.tabs.blog_posts.empty_state'))
            ->recordActions([
                Action::make('editPost')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (BlogPost $record): string => BlogPostResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $parentTitle]))
                    ->after(fn () => $this->dispatch('refresh-tabs')),
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_attach_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $parentTitle]))
                    ->preloadRecordSelect()
                    ->recordSelect(fn (Select $select) => $select
                        ->options(fn (): array => BlogPost::blogPostChoices($store->id)
                            ->except($this->excludedBlogPostIds())
                            ->all()
                        )
                    )
                    ->mutateDataUsing(function (array $data) use ($store): array {
                        $data['store_id'] = $store->id;
                        return $data;
                    })
                    ->after(fn () => $this->dispatch('refresh-tabs')),
            ])
            ->recordAction(null)
            ->filters([])
            ->reorderable('post_sort_order')->defaultSort('post_sort_order')
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::BlogPosts->labelPlural(), 'name' => $parentRecord?->global_name]))
                        ->after(fn () => $this->dispatch('refresh-tabs')),
                ]),
            ]);
    }

    private function excludedBlogPostIds(): array
    {
        return $this->excludedIds ??= $this->getOwnerRecord()
            ->blogPosts()
            ->wherePivot('store_id', Filament::getTenant()->id)
            ->pluck('blog_posts.id')
            ->all();
    }


    protected function getTableHeading(): string
    {
        return __('admin.blog.posts.tabs.products.label');
    }
}
