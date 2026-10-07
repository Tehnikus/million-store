<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Domain\Catalog\Search\{ProductSearch, SearchIndexer};
use App\Filament\Resources\Products\{ProductResource, Tables\ProductsTable};
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use Filament\Actions\{Action, AttachAction, BulkActionGroup, DetachAction, DetachBulkAction};
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class TagProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current tag
        return ProductsTable::configure($table)
            ->recordTitleAttribute('global_name')
            ->emptyStateHeading(__('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
            ->emptyStateDescription(__('admin.catalog.tags.tabs.products.empty_state'))
            ->recordActions([
                Action::make('editProduct')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                    ->after(function (Product $record) {
                        $this->reindex([$record->id]);
                        $this->dispatch('refresh-tabs');
                    }),
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
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_group_id'] = 0;
                        return $data;
                    })
                   ->after(function (array $data) {
                        $this->reindex((array) $data['recordId']);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->recordAction(null)
            ->filters([])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                        ->after(function (Collection $records) {
                            $this->reindex($records->modelKeys());
                            $this->dispatch('refresh-tabs');
                        }),
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
        $parentRecord = $this->getOwnerRecord();

        return ProductSearch::query($search, $parentRecord->store_id)
            ->whereKeyNot($this->excludedProductIds())
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
            ->all();
    }

    // Reindex products global search index after products were attached/detached from parent record
    private function reindex(array $productIds): void
    {
        // app(SearchIndexer::class)->products($productIds, $this->getOwnerRecord()->store_id);
    }

    protected function getTableHeading(): string
    {
        return __('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $this->getOwnerRecord()?->name]);
    }
}
