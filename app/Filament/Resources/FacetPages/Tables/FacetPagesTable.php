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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacetPagesTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();

        // Products matching ALL facets of the current facet page (correlated to facet_pages.id).
        // Used twice: for products_count in SELECT and for EXISTS in the "has products" filter,
        // because a SELECT alias can't be used in WHERE.
        $matchedProductsSql = '
            SELECT fi.product_id
            FROM facet_page_index fpi
            JOIN facet_index fi 
                ON fi.store_id        = fpi.store_id
                AND fi.facet_type_id  = fpi.facet_type_id
                AND fi.facet_value_id = fpi.facet_value_id
            WHERE fpi.facet_page_id = facet_pages.id
            GROUP BY fi.product_id
            HAVING COUNT(*) = (
                SELECT COUNT(*) FROM facet_page_index x
                WHERE x.facet_page_id = facet_pages.id
            )
        ';

        // Pages whose facet set already belongs to another ACTIVE page of the store.
        // Uncorrelated subquery: Postgres runs it once per table query (hashed SubPlan), not per row.
        $activeDuplicatesSql = "
            SELECT facet_page_id FROM (
                SELECT sig.facet_page_id,
                    COUNT(*) FILTER (WHERE fp.is_active) OVER (PARTITION BY sig.signature) AS active_peers
                FROM (
                    SELECT facet_page_id,
                        string_agg(facet_type_id || ':' || facet_value_id, ',' ORDER BY facet_type_id, facet_value_id) AS signature
                    FROM facet_page_index
                    WHERE store_id = ?
                    GROUP BY facet_page_id
                ) sig
                JOIN facet_pages fp ON fp.id = sig.facet_page_id
            ) d
            WHERE active_peers > 0
        ";

        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with('facetIndex')
                    ->select('facet_pages.*')
                    // Count matching products
                    ->selectRaw("
                        (SELECT 
                            COUNT(*) 
                        FROM ({$matchedProductsSql}) matched) 
                        AS products_count
                    ")
                    // An inactive page can't be switched on if an active page has the same facet set
                    // or if its root facet is gone (root entity deleted). Active pages can always be switched off.
                    ->selectRaw("
                        (NOT facet_pages.is_active AND (
                            facet_pages.id IN ({$activeDuplicatesSql})
                            -- Must have exactly the root AND at least one additional facet.
                            -- COALESCE: bool_or() over zero rows is NULL, and NULL would silently read as 'not blocked'
                            OR NOT COALESCE((
                                SELECT bool_or(s.is_root) AND bool_or(NOT s.is_root)
                                FROM facet_page_index s
                                WHERE s.facet_page_id = facet_pages.id
                            ), false)
                        )) AS activation_blocked
                    ", [$store->id])
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
                    ->label(__('admin.catalog.facet_pages.fields.name')),

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
                    ->label(__('admin.catalog.facet_pages.fields.is_active'))
                    ->disabled(fn(FacetPage $record): bool => (bool) $record->activation_blocked)
                    ->offColor(fn(FacetPage $record): string => (bool) $record->activation_blocked ? '#000000' : 'gray')
                    ->sortable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    // ->tooltip(fn(FacetPage $record): ?string => $record->activation_blocked ? __('admin.catalog.facet_pages.helpers.activation_blocked') : null)
                    ->extraAttributes(fn(FacetPage $record) => [
                        // Workaround on tooltips not working on disabled toggles
                        'style' => 'pointer-events: auto;',
                        'x-tooltip.raw' => $record->activation_blocked ? __('admin.catalog.facet_pages.helpers.activation_blocked') : null,
                    ])
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                        // TODO: Rebuild sitemap here
                    })
                    ,

            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('facets')
                    ->label(__('admin.catalog.facet_pages.filters.facets'))
                    ->multiple()
                    ->searchable()
                    // Keys are "type:id": ids of different entity types overlap (tag #5 vs category #5)
                    ->options(function () use ($store) {
                        $options = [];

                        foreach (FacetType::cases() as $type) {
                            foreach (self::namesFor($type, $store->id) as $id => $name) {
                                $options[$type->getLabel()]["{$type->value}:{$id}"] = $name;
                            }
                        }

                        return $options;
                    })
                    // Page must contain ALL selected facets (root included): one EXISTS per facet
                    ->query(function (Builder $query, array $data) {
                        foreach ($data['values'] ?? [] as $key) {
                            [$typeId, $valueId] = explode(':', $key);

                            $query->whereHas(
                                'facetIndex',
                                fn(Builder $q) => $q
                                    ->where('facet_type_id', (int) $typeId)
                                    ->where('facet_value_id', (int) $valueId)
                            );
                        }
                    }),

                TernaryFilter::make('has_products')
                    ->label(__('admin.catalog.facet_pages.filters.has_products'))
                    ->trueLabel(__('admin.catalog.facet_pages.filters.with_products'))
                    ->falseLabel(__('admin.catalog.facet_pages.filters.without_products'))
                    ->native(false)
                    ->queries(
                        true: fn(Builder $query) => $query->whereRaw("EXISTS ({$matchedProductsSql})"),
                        false: fn(Builder $query) => $query->whereRaw("NOT EXISTS ({$matchedProductsSql})"),
                        blank: fn(Builder $query) => $query,
                    ),

                TernaryFilter::make('is_active')
                    ->label(__('admin.catalog.facet_pages.fields.is_active'))
                    ->trueLabel(__('admin.catalog.facet_pages.filters.active'))
                    ->falseLabel(__('admin.catalog.facet_pages.filters.inactive'))
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()->tooltip(__('filament-actions::edit.single.label')),
                DeleteAction::make()->tooltip(__('filament-actions::delete.single.label'))
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function namesFor(FacetType $type, int $storeId): array
    {
        $names = [];

        foreach ($type->choices($storeId) as $key => $item) {
            // Grouped choices: "Parent" => [id => name]    id => "Parent: name"
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
