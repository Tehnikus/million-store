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

class ManufacturerProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current manufacturer
        return ProductsTable::configure($table)
            ->recordTitleAttribute('global_name')
            ->recordActions([
                Action::make('makePrimary')
                    ->iconButton()
                    ->icon(fn (Product $record) => $this->isPrimaryManufacturer($record) ? Heroicon::Star : Heroicon::OutlinedStar)
                    ->color(fn (Product $record) => $this->isPrimaryManufacturer($record) ? 'warning' : 'gray')
                    ->tooltip(fn (Product $record) => $this->isPrimaryManufacturer($record)
                        ? __('admin.catalog.manufacturers.tabs.products.labels.is_primary')
                        : __('admin.catalog.manufacturers.tabs.products.labels.make_primary'))
                    ->disabled(fn (Product $record) => $this->isPrimaryManufacturer($record))
                    ->action(fn (Product $record) => $this->setPrimaryManufacturer([$record->id], force: true)),
                Action::make('editProduct')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->modalHeading(__('admin.catalog.manufacturers.tabs.products.labels.detach_heading'))
                    ->after(function (Product $record) {
                        $this->reindex([$record->id]);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading(__('admin.common.helpers.manager_page_modal_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                    ->preloadRecordSelect()
                    ->recordSelect(fn (Select $select) => $select
                        ->options(fn (): array => $this->productOptions())
                        ->getSearchResultsUsing(fn (string $search): array => $this->productOptions($search))
                        ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->global_name)
                    )
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;
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
                        ->modalHeading(__('admin.catalog.manufacturers.tabs.products.labels.detach_heading'))
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

    /**
     * Set current manufacturer as primary for products in the current store.
     * $force = false: only for products without a primary manufacturer
     */
    private function setPrimaryManufacturer(array $productIds, bool $force = false): void
    {
        $parentRecord = $this->getOwnerRecord();

        ProductDescription::where('store_id', $parentRecord->store_id)
            ->whereIn('product_id', $productIds)
            ->when(!$force, fn ($query) => $query->whereNull('primary_manufacturer_id'))
            ->update(['primary_manufacturer_id' => $parentRecord->id]);
    }

    private function isPrimaryManufacturer(Product $product): bool
    {
        return (int) $product->currentDescription()?->primary_manufacturer_id === (int) $this->getOwnerRecord()->id;
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
