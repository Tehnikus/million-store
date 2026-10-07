<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Domain\Catalog\Search\ProductSearch;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Product;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PostProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord();
        return ProductsTable::configure($table)
            ->emptyStateHeading(__('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
            ->emptyStateDescription(__('admin.blog.posts.tabs.products.empty_state'))
            ->recordActions([
                Action::make('editPost')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                    ->after(fn () => $this->dispatch('refresh-tabs')),
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_attach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                    ->preloadRecordSelect()
                    ->recordSelect(fn (Select $select) => $select
                        ->options(fn (): array => $this->productOptions())
                        ->getSearchResultsUsing(fn (string $search): array => $this->productOptions($search))
                        ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->global_name)
                    )
                    ->mutateDataUsing(function (array $data): array {
                        $data['store_id'] = $this->getOwnerRecord()->store_id;
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
                        ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                        ->after(fn () => $this->dispatch('refresh-tabs')),
                ]),
            ]);
    }

    private function excludedProductIds(): array
    {
        return $this->excludedIds ??= $this->getOwnerRecord()
            ->products()
            ->pluck('products.id')
            ->all();
    }

    private function productOptions(string $search = ''): array
    {
        $blogPost = $this->getOwnerRecord();

        return ProductSearch::query($search, $blogPost->store_id)
            ->whereKeyNot($this->excludedProductIds())
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
            ->all();
    }

    protected function getTableHeading(): string
    {
         return __('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $this->getOwnerRecord()?->name]);
    }
}
