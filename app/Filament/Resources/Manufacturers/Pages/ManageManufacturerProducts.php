<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Manufacturers\ManufacturerResource;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\Product;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Livewire\Livewire;

class ManageManufacturerProducts extends ManageRelatedRecords
{
    protected static string $resource = ManufacturerResource::class;

    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('global_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current manufacturer
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
                    ->preloadRecordSelect()
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id']       = $parentRecord->store_id;
                        $data['facet_type_id']  = FacetType::Manufacturer->value;
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;
                        return $data;
                    })
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
            ->where('facet_type_id', FacetType::Manufacturer->value)
            ->where('store_id', $parentRecord->store_id)
            ->count();
    }
}
