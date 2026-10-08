<?php

namespace App\Filament\Resources\Tags\RelationManagers;

use App\Domain\Catalog\Search\{ProductSearch, SearchIndexer};
use App\Filament\Resources\Products\{ProductResource, Tables\ProductsTable};
use App\Models\Catalog\Product;
use Filament\Actions\{Action, AttachAction, BulkAction, DetachAction};
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class TagProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current tag
        return ProductsTable::configure($table)
            ->recordTitleAttribute('global_name')
            ->emptyStateHeading(__('admin.catalog.tags.tabs.products.labels.table_title', ['name' => $parentRecord?->name]))
            ->emptyStateDescription(__('admin.catalog.tags.tabs.products.empty_state'))
            ->recordActions([
                Action::make('editProduct')
                    ->label(__('filament-actions::edit.single.label'))
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->tooltip(__('filament-actions::detach.single.label'))
                    ->modalHeading(fn(Product $record) => __('admin.catalog.tags.tabs.products.labels.detach_from', ['name' => $parentRecord?->name, 'product' => $record->global_name]))
                    ->modalDescription(__('admin.catalog.tags.tabs.products.helpers.detach_single'))
                    ->modalSubmitActionLabel(__('admin.catalog.tags.tabs.products.labels.detach_button'))
                    ->modalWidth(Width::TwoExtraLarge)
                    ->after(function (Product $record) {
                        $this->reindex([$record->id]);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('admin.catalog.tags.tabs.products.labels.add_products'))
                    ->modalHeading(__('admin.catalog.tags.tabs.products.labels.add_products_heading', ['name' => $parentRecord?->name]))
                    ->modalDescription(__('admin.catalog.tags.tabs.products.helpers.attach_description'))
                    ->modalSubmitActionLabel(__('admin.catalog.tags.tabs.products.labels.add_products'))
                    ->modalIcon(Heroicon::Plus)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelect(fn (Select $select) => $select
                        ->options(fn (): array => $this->productOptions())
                        ->getSearchResultsUsing(fn (string $search): array => $this->productOptions($search))
                        ->getOptionLabelsUsing(fn (array $values): array => Product::whereKey($values)
                            ->get()
                            ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
                            ->all()
                        )
                    )
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_group_id'] = 0; // Tags have no parent, always 0
                        return $data;
                    })
                    ->after(function (array $data) {
                        $productIds = (array) $data['recordId'];

                        $this->reindex($productIds);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->recordAction(null)
            ->filters([])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->toolbarActions([
                BulkAction::make('detachSelected')
                    ->label(__('admin.catalog.tags.tabs.products.labels.detach_bulk'))
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.catalog.tags.tabs.products.labels.detach_bulk'))
                    ->modalDescription(__('admin.catalog.tags.tabs.products.helpers.detach_bulk'))
                    ->modalSubmitActionLabel(__('admin.catalog.tags.tabs.products.labels.detach_button'))
                    ->modalWidth(Width::TwoExtraLarge)
                    ->action(function (EloquentCollection $records) use ($parentRecord) {
                        $parentRecord->products()->detach($records);
                        Notification::make()
                            ->title(__('admin.catalog.tags.tabs.products.notifications.detached', ['count' => $records->count()]))
                            ->success()
                            ->send();

                        $this->dispatch('refresh-tabs');
                    })
                    ->deselectRecordsAfterCompletion(),

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

        return ProductSearch::query($search, $this->getOwnerRecord()?->store_id)
            ->whereKeyNot($this->excludedProductIds())
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
            ->all();
    }

    // Reindex products global search index after products were attached/detached from parent record
    private function reindex(array $productIds): void
    {
        // TODO Reindex search here
        // app(SearchIndexer::class)->products($productIds, $this->getOwnerRecord()->store_id);
    }

    protected function getTableHeading(): string
    {
         return __('admin.catalog.tags.tabs.products.labels.table_title', ['name' => $this->getOwnerRecord()?->name]);
    }
}
