<?php

namespace App\Filament\Resources\Products\Tables;

use App\Domain\Catalog\Search\ProductSearch;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDescription;
use App\Models\Global\Currency;
use Arr;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Support\HtmlString;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
       
        $defaultCurrencyId = Currency::where('rate_default', true)->value('id');
        return $table
            // Filter results by current store id
            // This separates model from store_id, only filament forms know about it
            // Thus models do not depend of filament tenant context
            ->modifyQueryUsing(function (Builder $query) {
                $storeId =  Filament::getTenant()->id;
                
                $query
                    ->with(['descriptions' => function ($subQuery) use ($storeId) {
                        $subQuery->where('store_id', $storeId); // Get descriptions of current store only
                    }])
                    // ->with(['priceTiers' => function ($subQuery) use ($storeId) {
                    //     // Get prices of current store only
                    //     $subQuery
                    //         ->where('store_id', $storeId)
                    //         ->whereNull('customer_group_id') // Customer group TODO
                    //         ->where(function ($q) {
                    //             $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                    //         })
                    //         ->where(function ($q) {
                    //             $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                    //         })
                    //         ->orderByDesc('priority')
                    //         ->with('prices'); // Join all tier prices
                    // }])
                ;
            })
            ->searchUsing(function (Builder $query, $search): Builder {
                if (filled($search)) {
                    $query->whereIn(
                        'products.id',
                        ProductSearch::query($search, Filament::getTenant()->id)->pluck('id'),
                    );
                }

                return $query;
            })
            ->searchDebounce('250ms')
            ->emptyStateIcon(NavigationItem::Products->icon())
            ->emptyStateHeading(__('admin.catalog.products.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.products.table.messages.empty_state_message'))
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
                //
            ])
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
