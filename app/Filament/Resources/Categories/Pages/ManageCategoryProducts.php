<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Product;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
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
            ->headerActions([
                AttachAction::make()
                    ->recordSelect(
                        fn (Select $select) => $select
                            ->getSearchResultsUsing(
                                fn (string $search): array => Product::query()
                                    ->whereRaw('global_name::text ilike ?', ['%' . $search . '%'])
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Product $product) => [$product->id => $product->global_name])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(
                                fn ($value): ?string => Product::find($value)?->global_name
                            )
                    )
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_type_id']  = FacetType::Category->value;
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;
                        return $data;
                    })
                    ->color('primary')
                    ->icon('heroicon-o-plus')
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
        return NavigationItem::Products->icon();
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Products->labelPlural();
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
