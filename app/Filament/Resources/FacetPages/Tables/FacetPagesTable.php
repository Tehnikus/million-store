<?php

namespace App\Filament\Resources\FacetPages\Tables;

use App\Domain\Catalog\FacetType;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\FacetPage;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacetPagesTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with('facetIndex')
                    ->select('facet_pages.*')
                    // Count products in filter page
                    ->selectRaw('(
                        SELECT COUNT(*) FROM (
                            SELECT fi.product_id
                            FROM facet_page_index fpi
                            JOIN facet_index fi
                            ON fi.store_id          = fpi.store_id
                            AND fi.facet_type_id    = fpi.facet_type_id
                            AND fi.facet_value_id   = fpi.facet_value_id
                            WHERE fpi.facet_page_id = facet_pages.id
                            GROUP BY fi.product_id
                            HAVING COUNT(*) = (
                                SELECT COUNT(*) FROM facet_page_index x
                                WHERE x.facet_page_id = facet_pages.id
                            )
                        ) matched
                    ) AS products_count')
            )
            ->emptyStateIcon(NavigationItem::FacetPages->icon())
            ->emptyStateHeading(__('admin.catalog.facet_pages.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.facet_pages.helpers.group_title'))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.facet_pages.model_label_singular')),

                TextColumn::make('facetIndex')
                    ->label(__('admin.catalog.facet_pages.fields.filter_list'))
                    ->state(function (FacetPage $record) {
                        return $record->facetIndex->map(function ($item) use ($record) {
                            $type  = $item->facet_type_id;
                            $names = self::namesFor($type, $record->store_id);

                            return [
                                'type' => $type,
                                'name' => $names[$item->facet_value_id] ?? "#{$item->facet_value_id}",
                            ];
                        })
                        ->sortBy(fn ($facet) => $facet['type']->sortPriority())
                        ->values()
                        ->all();
                    })
                    ->formatStateUsing(fn (array $state) => $state['name'])
                    ->color(fn (array $state) => $state['type']->getColor())
                    ->icon(fn (array $state) => $state['type']->getIcon())
                    ->size(TextSize::Medium)
                    ->badge()
                    ->width('300px')
                    ->wrap()
                    ->wrapHeader(),
                TextColumn::make('products_count')
                    ->label(__('admin.catalog.facet_pages.fields.products_count'))
                    ->sortable()
                    ->color(fn($state) => $state === 0 ? 'danger' : 'success')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->width('1%'),
                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.facet_pages.fields.is_active')),

            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function namesFor(FacetType $type, int $storeId): array
    {
        $names = [];

        foreach ($type->choices($storeId) as $key => $item) {
            // Grouped choices: "Parent" => [id => name]  →  id => "Parent: name"
            if (is_array($item)) {
                foreach ($item as $id => $name) {
                    $names[$id] = "{$key}: {$name}";
                }
                continue;
            }

            $names[$key] = $item;
        }

        return $names;
    }
}
