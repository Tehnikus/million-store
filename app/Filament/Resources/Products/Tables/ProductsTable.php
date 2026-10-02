<?php

namespace App\Filament\Resources\Products\Tables;

use Arr;
use Number;
use App\Domain\Catalog\Search\ProductSearch;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\{ConversionImageColumn, MultilangTextColumn};
use App\Models\Catalog\{Product, ProductDescription, Category, Manufacturer, Option, OptionValue, Tag, ProductPriceTier};
use App\Models\Global\Currency;
use Filament\Actions\{Action, BulkActionGroup, DeleteBulkAction, EditAction, DeleteAction};
use Filament\Facades\Filament;
use Filament\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\{Carbon, Collection, HtmlString, Facades\DB, Facades\Context};


class ProductsTable
{
    public static function configure(Table $table): Table
    {
       
        $defaultCurrency = Currency::where('rate_default', true)->first();
        $store = Filament::getTenant();
        return $table
            // Filter results by current store id
            // This separates model from store_id, only filament forms know about it
            // Thus models do not depend of filament tenant context
            ->modifyQueryUsing(function (Builder $query) use ($store) {
                $storeId =  $store->id;
                
                $query
                    ->with(['descriptions' => function ($subQuery) use ($storeId) {
                        $subQuery->where('store_id', $storeId); // Get descriptions of current store only
                    }])
                    ->with(['priceTiers' => function ($subQuery) use ($storeId) {
                        // Get prices of current store only
                        $subQuery
                            ->where('store_id', $storeId)
                            ->whereNull('customer_group_id') // Customer group TODO
                            ->where(function ($q) {
                                $q->whereNull('date_valid_from')->orWhere('date_valid_from', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('date_valid_until')->orWhere('date_valid_until', '>=', now());
                            })
                            // ->orderByDesc('priority')
                            ->with('prices'); // Join all tier prices
                    }]);
            })
            ->searchUsing(function (Builder $query, $search) use ($store): Builder {
                if (filled($search)) {
                    $query->whereIn(
                        'products.id',
                        ProductSearch::query($search, $store->id)->pluck('id'),
                    );
                }

                return $query;
            })
            ->searchDebounce('100ms')
            ->emptyStateIcon(NavigationItem::Products->icon())
            ->emptyStateHeading(__('admin.catalog.products.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.products.table.messages.empty_state_message'))
            // ->recordClasses(fn (Model $record) => match ($record->currentDescription()?->is_active) {
            //     false => 'opacity-50',
            //     true => 'border-s-2 border-green-500',
            //     default => null,
            // })
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->toggleable(isToggledHiddenByDefault: false),

                // Global SKU and name
                TextColumn::make('global_name')
                    ->label(__('admin.catalog.products.table.columns.global_name') .'/'. __('admin.catalog.products.table.columns.sku'))
                    ->formatStateUsing(function ($record) {
                        return new HtmlString(
                            "<div>{$record->global_name}</div>" . 
                            "<div style=\"color: var(--gray-400)\">{$record->sku}</div>" 
                        );
                    })
                    ->wrapHeader()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),

                MultilangTextColumn::make('productName')
                    ->recordColumnAll(fn ($record) => $record->currentDescription()?->getTranslations('name'))
                    ->placeholder(__('admin.catalog.products.table.columns.is_not_associated'))
                    ->label(__('admin.catalog.products.table.columns.store_name'))
                    ->wrapHeader()
                    ->sortable(query: function (Builder $query, string $direction) {
                        $locale  = app()->getLocale();
                        $storeId = Filament::getTenant()->id;

                        $sortNames = DB::table('product_descriptions')
                            ->where('store_id', $storeId)
                            ->select('product_id')
                            ->selectRaw(
                                "COALESCE(name->>?, (SELECT value FROM jsonb_each_text(name) ORDER BY key LIMIT 1)) as sort_name",
                                [$locale]
                            );

                        return $query
                            ->leftJoinSub($sortNames, 'sort_names', 'sort_names.product_id', '=', 'products.id')
                            ->orderBy('sort_names.sort_name', $direction);
                    })
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('descriptions.options_description')
                    ->formatStateUsing(fn ($record) => static::renderFacetGroups(
                        $record->currentDescription()?->options_description ?? [], 'description', 'success')
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.products.tabs.options.label')),

                TextColumn::make('descriptions.attributes_description')
                    ->formatStateUsing(fn ($record) => static::renderFacetGroups(
                        $record->currentDescription()?->attributes_description ?? [], 'description', 'info')
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.products.tabs.attributes.label')),

                TextColumn::make('priceSummary')
                    ->getStateUsing(fn($record) => static::renderPriceColumn($record, $defaultCurrency))
                    ->sortable(query: function (Builder $query, string $direction) use ($defaultCurrency) {
                        if (!$defaultCurrency) {
                            return $query;
                        }

                        $topTierPerProduct = DB::table('product_price_tiers')
                            ->select('*')
                            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY priority DESC) as rn')
                            ->where('store_id', Filament::getTenant()->id)
                            ->whereNull('customer_group_id') // TODO add customer group filters, when ready
                            ->where(fn ($q) => $q->whereNull('date_valid_from')->orWhere('date_valid_from', '<=', now()))
                            ->where(fn ($q) => $q->whereNull('date_valid_until')->orWhere('date_valid_until', '>=', now()));

                        $sortPrices = DB::query()
                            ->fromSub($topTierPerProduct, 'top_tier')
                            ->join('product_prices', 'product_prices.product_price_tier_id', '=', 'top_tier.id')
                            ->where('top_tier.rn', 1)
                            ->where('product_prices.currency_id', $defaultCurrency->id)
                            ->groupBy('top_tier.product_id')
                            ->selectRaw('top_tier.product_id, MIN(product_prices.price) as sort_price');

                        return $query
                            ->leftJoinSub($sortPrices, 'sort_prices', 'sort_prices.product_id', '=', 'products.id')
                            ->orderBy('sort_prices.sort_price', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->html()
                    ->label(__('admin.catalog.products.table.columns.price'))
                    ->placeholder('--')
                    ->alignEnd()
                    ->extraCellAttributes([]),

                // Dates
                TextColumn::make('created_at')
                    ->dateTime()
                    ->wrap()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.products.table.columns.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->wrap()
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('admin.catalog.products.table.columns.updated_at')),
            ])
            ->filters([
                QueryBuilder::make()
                    // ->constraintPickerColumns(5)
                    // ->constraintPickerWidth('2xl')
                    ->constraints([
                        BooleanConstraint::make('descriptions.is_active')
                            ->label(__('admin.catalog.products.table.filters.is_active'))
                            ->icon(Heroicon::Play),
                        BooleanConstraint::make('descriptions.is_available')
                            ->label(__('admin.catalog.products.table.filters.is_available'))
                            ->icon(Heroicon::OutlinedShoppingCart),
                        SelectConstraint::make('categoryFacets.facet_value_id')
                            ->options(fn() => Category::categoryChoices($store->id))
                            ->searchable()
                            ->multiple()
                            ->label(__('admin.catalog.products.table.filters.categories'))
                            ->icon(NavigationItem::Categories->icon()),
                        SelectConstraint::make('manufacturerFacets.facet_value_id')
                            ->options(fn() => Manufacturer::manufacturerChoices($store->id))
                            ->searchable()
                            ->multiple()
                            ->label(__('admin.catalog.products.table.filters.manufacturers'))
                            ->icon(NavigationItem::Manufacturers->icon()),
                        SelectConstraint::make('tagFacets.facet_value_id')
                            ->options(fn() => Tag::tagChoices($store->id))
                            ->searchable()
                            ->multiple()
                            ->label(__('admin.catalog.products.table.filters.tags'))
                            ->icon(NavigationItem::Tags->icon()),
                        SelectConstraint::make('optionFacets.facet_value_id')
                            ->options(fn () => OptionValue::optionValueGroupedChoices([], $store->id))
                            ->searchable()
                            ->multiple()
                            ->label(__('admin.catalog.products.table.filters.options'))
                            ->icon(NavigationItem::Options->icon()),

                    ])
            ], layout: FiltersLayout::AboveContentCollapsible)
            // ->filtersFormColumns(3)
            ->recordActions([
                Action::make('toggleActive')
                    ->requiresConfirmation(false)
                    ->action(fn (Product $record) => $record->currentDescription()?->update(['is_active' => !$record->currentDescription()?->is_active]))
                    ->visible(fn($record) => $record->currentDescription() !== null)
                    ->icon(fn($record) => $record->currentDescription()?->is_active == true ? Heroicon::Play : Heroicon::Stop)
                    ->color(fn($record) => $record->currentDescription()?->is_active == true ? 'success' : 'danger')
                    ->tooltip(fn($record) => $record->currentDescription()?->is_active == true ? __('admin.catalog.products.table.columns.is_active') : __('admin.catalog.products.table.columns.is_not_active')),

                EditAction::make()->tooltip(__('admin.catalog.products.table.buttons.edit_product')),
                
                Action::make('deleteFromStore')
                    ->action(fn(Product $product) => ProductDescription::where('product_id', $product->id)->where('store_id', Filament::getTenant()->id)->delete())
                    ->visible(fn($record) => $record->currentDescription() !== null)
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.catalog.products.table.buttons.delete_from_store'))
                    ->modalDescription(__('admin.catalog.products.table.messages.delete_from_store'))
                    ->tooltip(__('admin.catalog.products.table.buttons.delete_from_store')),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.catalog.products.table.buttons.delete_from_all_stores'))
                    ->modalDescription(__('admin.catalog.products.table.messages.delete_from_all_stores'))
                    ->tooltip(__('admin.catalog.products.table.buttons.delete_from_all_stores')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Set state of price column: display base price, discount, price name and date valud until
     * @param mixed $record
     * @param mixed $currency
     * @return HtmlString|null
     */
    protected static function renderPriceColumn($record, ?Currency $currency): ?HtmlString
    {
        if (!$currency) {
            return null;
        }

        $tiers         = $record->priceTiers->sortByDesc('priority');
        $baseTier      = $tiers->firstWhere('is_base', true);
        $discountTier  = $tiers->first(fn ($tier) => !$tier->is_base);

        $basePrice     = static::formatPrice($baseTier, $currency);
        $discountPrice = static::formatPrice($discountTier, $currency);

        if (!$basePrice && !$discountPrice) {
            return null;
        }

        $priceMeta = static::tierMeta($discountTier ?? $baseTier);

        return new HtmlString(
            implode('<br>&nbsp;', [
                $priceMeta,
                $discountPrice ? '<span style="color: var(--success-500); font-weight: 600;">' . e($discountPrice) . '</span>' : '<span>' . e($basePrice) . '</span>',
                $discountPrice ? '<span style="text-decoration: line-through; color: var(--gray-400);">' . e($basePrice) . '</span>' : '',
            ])
        );
    }

    protected static function tierMeta(?ProductPriceTier $tier): string
    {
        if (!$tier) {
            return '';
        }

        $name  = $tier->name[app()->getLocale()] ?? Arr::first($tier->name ?? []);
        $until = $tier->date_valid_until
            ? Carbon::parse($tier->date_valid_until)->translatedFormat('d.m.y')
            : null;

        $parts = array_filter([
            $name ? e($name) : null,
            $until ? __('admin.catalog.products.table.columns.valid_until') . ' ' . e($until) : null,
        ]);

        return $parts ? '<span style="color: var(--primary-500);">' . implode(', ', $parts) . '</span>' : '';
    }

    /**
     * Format price to display in currency
     * @param mixed $tier
     * @param Currency $currency
     * @return bool|string|null
     */
    protected static function formatPrice(?ProductPriceTier $tier, Currency $currency): ?string
    {
        if (!$tier) {
            return null;
        }

        $amounts = $tier->prices
            ->where('currency_id', $currency->id)
            ->pluck('price')
            ->filter(fn ($price) => filled($price))
            ->map(fn ($price) => (float) $price);

        if ($amounts->isEmpty()) {
            return null;
        }

        $min = $amounts->min();
        $max = $amounts->max();

        return $min === $max
            ? Number::currency($min, in: $currency->iso_code)
            : Number::currency($min, in: $currency->iso_code) . ' - ' . Number::currency($max, in: $currency->iso_code);
    }

    protected static function renderFacetGroups(array $groups, string $valuesKey, string $color = 'success'): ?HtmlString
    {
        if (empty($groups)) {
            return null;
        }

        $html = collect($groups)->map(function ($group) use ($valuesKey, $color) {
            $groupName = e(static::localizedText($group['name'] ?? null));

            $values = collect($group[$valuesKey] ?? [])
                ->map(fn ($v) => "
                    <span class=\"inline-flex fi-color fi-color-{$color} fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm\">"
                      .  e(static::localizedText($v['name'] ?? null)) . 
                    "</span>"
                )
                ->filter()
                ->implode(' ');

            return "<span x-tooltip=\"{content: '" . $groupName . "', theme: \$store.theme, allowHTML: false}\" >{$values}</span>";
        })->implode('<br>');

        return new HtmlString($html);
    }

    protected static function localizedText(?array $translations): string
    {
        if (empty($translations)) {
            return '';
        }

        $locale = app()->getLocale();

        return $translations[$locale] ?? Arr::first($translations) ?? '';
    }
}
