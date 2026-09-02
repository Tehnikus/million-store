<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Catalog\FacetType;
use App\Domain\Catalog\Search\ProductSearch;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\Category;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Product;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Context;
use Livewire\Livewire;

class ManageCategoryProducts extends ManageRelatedRecords
{
    protected static string $resource = CategoryResource::class;

    protected static string $relationship = 'products';

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current category
        return ProductsTable::configure($table)
            ->recordTitleAttribute('global_name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->headerActions([
                AttachAction::make()
                    ->recordSelect(
                        fn (Select $select) => $select
                            ->options(fn (): array => self::productOptions($this->getOwnerRecord()))
                            ->getSearchResultsUsing(
                                fn (string $search): array => self::productOptions($this->getOwnerRecord(), $search)
                            )
                            ->getOptionLabelUsing(
                                fn ($value): ?string => Product::find($value)?->global_name
                            )
                    )
                    ->preloadRecordSelect()
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;
                        return $data;
                    })
                    ->label(__('admin.common.buttons.attach_record'))
                    ->modalHeading(__('admin.common.helpers.manager_page_modal_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $parentRecord?->name]))
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


    /**
     * Get already selected product ids to exclude them from search
     * @param Category $category
     * @return array
     */
    private static function excludedProductIds(Category $category): array
    {
        $key = "category_products_excluded:{$category->id}";

        return Context::get($key) ?? tap(
            $category->products()->pluck('products.id')->all(),
            fn (array $ids) => Context::add($key, $ids),
        );
    }

    /**
     * Search products and preload options
     * @param Category $category
     * @param string $search
     * @return array
     */
    private static function productOptions(Category $category, string $search = ''): array
    {
        return ProductSearch::query($search, Filament::getTenant()->id)
            ->whereNotIn('products.id', self::excludedProductIds($category))
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
            ->toArray();
    }

    public static function getNavigationIcon(): string
    {
        return NavigationItem::Products->icon();
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Products->labelPlural();
    }

    public function getTitle(): string
    {
        return __('admin.common.helpers.manager_page_title', ['entities' => NavigationItem::Products->labelPlural(), 'name' => $this->getOwnerRecord()?->name]);
    }

    public static function getNavigationBadge(): ?string
    {
        $parentRecord = Livewire::current()->getRecord();
        return FacetIndex::where('facet_value_id', $parentRecord->id)
            ->where('facet_group_id', $parentRecord->parent_id ?? 0)
            ->where('facet_type_id', FacetType::Category->value)
            ->where('store_id', $parentRecord->store_id)
            ->count();
    }
}
