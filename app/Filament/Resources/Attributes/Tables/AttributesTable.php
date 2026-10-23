<?php

namespace App\Filament\Resources\Attributes\Tables;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->modifyQueryUsing(fn($query) => $query
                ->with('values') // Values badges column eager loading
                ->addSelect([
                    // Count products of each attribute group
                    'products_count' => DB::table('facet_index as fi')
                        ->join('attribute_values as av', 'av.id', '=', 'fi.facet_value_id')
                        ->whereColumn('av.attribute_id', 'attributes.id')
                        ->where('fi.store_id', $store->id)
                        ->where('fi.facet_type_id', FacetType::AttributeValue->value)
                        ->selectRaw('COUNT(DISTINCT fi.product_id)'),
                ]))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.attributes.fields.group')),

                TextColumn::make('values')
                    ->label(__('admin.catalog.attributes.fields.values'))
                    ->badge()
                    ->limitList(10)
                    ->expandableLimitedList()
                    ->formatStateUsing(fn (AttributeValue $state) => $state->name)
                    ->color(function (AttributeValue $state) {
                        if (!$state->is_active) return 'gray';
                        if (!$state->show_in_facets) return 'warning';
                        return 'info';
                    }),

                TextColumn::make('products_count')
                    ->badge()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.attributes.fields.products')),

                ToggleColumn::make('is_active')
                    ->label(__('admin.catalog.attributes.fields.is_active'))
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                    }),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.attributes.fields.show_in_facets')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateIcon(NavigationItem::Attributes->icon())
            ->emptyStateHeading(__('admin.catalog.attributes.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.attributes.helpers.group_title'))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(
                        function (Attribute $record) use ($store): HtmlString {
                            $valueIds = $record->values->modelKeys();
                            return DeletionWarnings::affectedWarningMessage(FacetType::AttributeValue, $valueIds, $store->id, true);
                        }
                    ),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->modalDescription(function ($records) use ($store): HtmlString {
                        // One query instead of lazy-loading "values" on every selected record
                        $valueIds = AttributeValue::whereIn('attribute_id', $records->modelKeys())->pluck('id')->all();
                        return DeletionWarnings::affectedWarningMessage(FacetType::AttributeValue, $valueIds, $store->id, true);
                    }),
            ]);
    }
}
