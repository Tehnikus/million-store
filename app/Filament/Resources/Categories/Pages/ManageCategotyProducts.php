<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Catalog\FacetType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Catalog\FacetIndex;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Livewire;

class ManageCategotyProducts extends ManageRelatedRecords
{
    protected static string $resource = CategoryResource::class;

    protected static string $relationship = 'products';

    public function table(Table $table): Table
    {
        $parentRecord = $this->getOwnerRecord(); // Current category

        return $table
            ->recordTitleAttribute('global_name')
            ->columns([
                TextColumn::make('sku')
                    ->label(__('admin.catalog.products.fields.sku'))
                    ->searchable(),
                TextColumn::make('global_name')
                    ->label(__('admin.catalog.products.fields.global_name'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    // ->form(fn (AttachAction $action): array => [])
                    ->mutateDataUsing(function (array $data) use ($parentRecord): array {
                        $data['store_id'] = Filament::getTenant()->id;
                        $data['facet_type_id'] = FacetType::Category->value;
                        $data['facet_group_id'] = $parentRecord->parent_id ?? 0;

                        return $data;
                    })
                    ->color('primary')
                    ->icon('heroicon-o-plus')
            ])
            ->recordActions([
                // EditAction::make(),
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
