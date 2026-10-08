<?php

namespace App\Filament\Resources\Manufacturers\RelationManagers;

use App\Domain\Catalog\Search\{ProductSearch, SearchIndexer};
use App\Filament\Resources\Products\{ProductResource, Tables\ProductsTable};
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use Filament\Actions\{Action, AttachAction, BulkAction, DetachAction};
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class ManufacturerProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected ?array $excludedIds = null; // One time request cache

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current manufacturer
        return ProductsTable::configure($table)
            ->recordTitleAttribute('global_name')
            ->emptyStateHeading(__('admin.catalog.manufacturers.tabs.products.labels.table_title', ['name' => $parentRecord?->name]))
            ->emptyStateDescription(__('admin.catalog.manufacturers.tabs.products.empty_state'))
            ->recordActions([
                Action::make('makePrimary')
                    ->iconButton()
                    // ->icon(fn (Product $record) => $this->isPrimaryManufacturer($record) ? Heroicon::Star : Heroicon::OutlinedStar)
                    ->icon(NavigationItem::Slugs->icon())
                    ->color(fn (Product $record) => $this->isPrimaryManufacturer($record) ? 'warning' : 'gray')
                    ->tooltip(fn (Product $record) => $this->isPrimaryManufacturer($record)
                        ? __('admin.catalog.manufacturers.tabs.products.labels.is_primary')
                        : __('admin.catalog.manufacturers.tabs.products.labels.make_primary'))
                    ->modalHeading(fn (Product $record) => __(
                        'admin.catalog.manufacturers.tabs.products.helpers.make_primary_single',
                        ['product' => $record->global_name]
                    ))
                    ->modalDescription(__('admin.catalog.manufacturers.tabs.products.helpers.make_primary_warning'))
                    ->modalSubmitActionLabel(__('admin.catalog.manufacturers.tabs.products.labels.make_primary'))
                    ->modalIcon(NavigationItem::Slugs->icon())->modalIconColor('warning')
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('manufacturer_id')
                            ->label(__('admin.catalog.manufacturers.tabs.products.labels.is_primary'))
                            ->options(fn () => Manufacturer::manufacturerChoices($parentRecord?->store_id))
                            ->default(fn () => $parentRecord?->id)
                            ->searchable()
                            ->required(),
                    ])
                    // ->fillForm(fn (Product $record): array => [
                    //     'manufacturer_id' => $record->currentDescription()?->primary_manufacturer_id ?? $this->getOwnerRecord()->id,
                    // ])
                    ->action(function (Product $record, array $data) {
                        $this->setPrimaryManufacturer([$record->id], (int) $data['manufacturer_id']);
                        // TODO Reindex Sitemap here
                    }),
                Action::make('editProduct')
                    ->label(__('filament-actions::edit.single.label'))
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->disabled(fn (Product $record) => $this->isPrimaryManufacturer($record))
                    ->color(fn(Product $record) => $this->isPrimaryManufacturer($record) ? 'gray' : 'danger')
                    ->tooltip(fn (Product $record) => $this->isPrimaryManufacturer($record)
                        ? __('admin.catalog.manufacturers.tabs.products.labels.detach_forbidden')
                        : __('filament-actions::detach.single.label'))
                    // ->modalHeading(__('admin.common.helpers.manager_page_detach_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
                    ->modalHeading(fn(Product $record) => __('admin.catalog.manufacturers.tabs.products.labels.detach_from', ['name' => $parentRecord?->name, 'product' => $record->global_name]))
                    ->modalDescription(__('admin.catalog.manufacturers.tabs.products.helpers.detach_single'))
                    ->modalSubmitActionLabel(__('admin.catalog.manufacturers.tabs.products.labels.detach_button'))
                    ->modalWidth(Width::Large)
                    ->after(function (Product $record) {
                        $this->reindex([$record->id]);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('admin.catalog.manufacturers.tabs.products.labels.add_products'))
                    ->modalHeading(__('admin.catalog.manufacturers.tabs.products.labels.add_products_heading', ['name' => $parentRecord?->name]))
                    ->modalDescription(__('admin.catalog.manufacturers.tabs.products.helpers.attach_description'))
                    ->modalSubmitActionLabel(__('admin.catalog.manufacturers.tabs.products.labels.add_products'))
                    ->modalIcon(Heroicon::Plus)
                    ->modalWidth(Width::ExtraLarge)
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
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;
                        return $data;
                    })
                    ->after(function (array $data) {
                        $productIds = (array) $data['recordId'];

                        $this->setPrimaryManufacturer($productIds, force: false);
                        $this->reindex($productIds);
                        $this->dispatch('refresh-tabs');
                    }),
            ])
            ->recordAction(null)
            ->filters([])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->toolbarActions([
                BulkAction::make('makePrimaryBulk')
                    ->label(__('admin.catalog.manufacturers.tabs.products.labels.make_primary_bulk'))
                    ->icon(NavigationItem::Slugs->icon())
                    ->modalIcon(NavigationItem::Slugs->icon())
                    ->modalIconColor('warning')
                    ->modalHeading(__('admin.catalog.manufacturers.tabs.products.labels.make_primary_bulk'))
                    ->modalDescription(__('admin.catalog.manufacturers.tabs.products.helpers.make_primary_warning'))
                    ->modalSubmitActionLabel(__('admin.catalog.manufacturers.tabs.products.labels.make_primary'))
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('manufacturer_id')
                            ->label(__('admin.catalog.manufacturers.tabs.products.labels.is_primary'))
                            ->options(fn () => Manufacturer::manufacturerChoices($parentRecord->store_id))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (EloquentCollection $records, array $data) {
                        $this->setPrimaryManufacturer($records->modelKeys(), (int) $data['manufacturer_id']);
                        // TODO Reindex Sitemap here
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('detachSelected')
                    ->label(__('admin.catalog.manufacturers.tabs.products.labels.detach_bulk'))
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.catalog.manufacturers.tabs.products.labels.detach_bulk'))
                    ->modalDescription(__('admin.catalog.manufacturers.tabs.products.helpers.detach_bulk'))
                    ->modalSubmitActionLabel(__('admin.catalog.manufacturers.tabs.products.labels.detach_button'))
                    ->modalWidth(Width::Large)
                    ->action(function (EloquentCollection $records) use ($parentRecord) {
                        [$skipped, $allowed] = $records->partition(fn (Product $p) => $this->isPrimaryManufacturer($p));

                        if ($allowed->isNotEmpty()) {
                            $parentRecord->products()->detach($allowed->modelKeys());
                            $this->reindex($allowed->modelKeys());
                        }

                        $notification = Notification::make()
                            ->title(__('admin.catalog.manufacturers.tabs.products.notifications.detached', ['count' => $allowed->count()]));

                        if ($skipped->isNotEmpty()) {
                            $notification->warning()
                                ->body(__('admin.catalog.manufacturers.tabs.products.notifications.detach_skipped') . ' '
                                    . $skipped->map(fn (Product $p) => $p->global_name)->implode(', '))
                                ->persistent();
                        } else {
                            $notification->success();
                        }

                        $notification->send();
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

    /**
     * Set current manufacturer as primary for products in the current store.
     * $force = false: only for products without a primary manufacturer
     */
    private function setPrimaryManufacturer(array $productIds, ?int $manufacturerId = null, bool $force = true): void
    {
        $owner      = $this->getOwnerRecord();
        $manufacturerId ??= $owner->id;

        DB::transaction(function () use ($owner, $productIds, $manufacturerId, $force) {
            if ($manufacturerId !== $owner->id) {
                $manufacturer = Manufacturer::where('store_id', $owner->store_id)->findOrFail($manufacturerId);

                $attached = $manufacturer->products()->whereKey($productIds)->pluck('products.id')->all();
                $missing  = array_values(array_diff($productIds, $attached));

                if ($missing) {
                    $manufacturer->products()->attach($missing, [
                        'store_id'       => $manufacturer->store_id,
                        'facet_group_id' => $manufacturer->parent_id ?? 0,
                    ]);
                    $this->reindex($missing);
                }
            }

            ProductDescription::where('store_id', $owner->store_id)
                ->whereIn('product_id', $productIds)
                ->when(! $force, fn ($query) => $query->whereNull('primary_manufacturer_id'))
                ->update(['primary_manufacturer_id' => $manufacturerId]);
        });
    }

    private function isPrimaryManufacturer(Product $product): bool
    {
        return (int) $product->currentDescription()?->primary_manufacturer_id === (int) $this->getOwnerRecord()->id;
    }

    // Reindex products global search index after products were attached/detached from parent record
    private function reindex(array $productIds): void
    {
        // TODO Reindex search here
        // app(SearchIndexer::class)->products($productIds, $this->getOwnerRecord()->store_id);
    }

    protected function getTableHeading(): string
    {
         return __('admin.catalog.manufacturers.tabs.products.labels.table_title', ['name' => $this->getOwnerRecord()?->name]);
    }
}
