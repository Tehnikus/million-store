<?php

namespace App\Filament\Resources\Options\Tables;

use App\Domain\Catalog\Actions\DeletionWarnings;
use App\Domain\Catalog\FacetType;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\Columns\ConversionImageColumn;
use App\Filament\Support\Columns\MultilangTextColumn;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
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

class OptionsTable
{
    public static function configure(Table $table): Table
    {
        $store = Filament::getTenant();
        return $table
            ->modifyQueryUsing(fn($query) => $query
                ->with('values') // Values badges column eager loading
                ->addSelect([
                    // Count products of each option group
                    'products_count' => DB::table('facet_index as fi')
                        ->join('option_values as ov', 'ov.id', '=', 'fi.facet_value_id')
                        ->whereColumn('ov.option_id', 'options.id')
                        ->where('fi.store_id', $store->id)
                        ->where('fi.facet_type_id', FacetType::OptionValue->value)
                        ->selectRaw('COUNT(DISTINCT fi.product_id)'),
                ]))
            ->emptyStateIcon(NavigationItem::Options->icon())
            ->emptyStateHeading(__('admin.catalog.options.navigation_label'))
            ->emptyStateDescription(__('admin.catalog.options.helpers.group_title'))
            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature')
                    ->label(__('admin.common.fields.image')),

                MultilangTextColumn::make('name')
                    ->recordColumnAll(fn ($record) => $record->getTranslations('name') ?? [])
                    ->wrapHeader()
                    ->label(__('admin.catalog.options.fields.group')),

                TextColumn::make('values')
                    ->label(__('admin.catalog.options.fields.values'))
                    ->badge()
                    ->limitList(10)
                    ->expandableLimitedList()
                    ->formatStateUsing(fn (OptionValue $state) => $state->name)
                    ->color(function (OptionValue $state) {
                        if (! $state->is_active) {
                            return 'gray';
                        }

                        if (! $state->show_in_facets) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('products_count')
                    ->badge()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.products')),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        if ($record->type == 'radio') return __('admin.catalog.options.fields.radio');
                        if ($record->type == 'checkbox') return __('admin.catalog.options.fields.checkbox');
                    })
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.type')),

                ToggleColumn::make('is_active')
                    ->sortable()
                    ->width('100px')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.is_active'))
                    ->afterStateUpdated(function ($livewire) {
                        $livewire->dispatch('refresh-sidebar');
                    }),

                ToggleColumn::make('show_in_facets')
                    ->sortable()
                    ->width('100px')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.catalog.options.fields.show_in_facets')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(
                        function (Option $record) use ($store): HtmlString {
                            $valueIds = $record->values->modelKeys();
                            return DeletionWarnings::affectedWarningMessage(FacetType::OptionValue, $valueIds, $store->id, true);
                        }
                    ),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->modalDescription(function ($records) use ($store): HtmlString {
                        // One query instead of lazy-loading "values" on every selected record
                        $valueIds = OptionValue::whereIn('option_id', $records->modelKeys())->pluck('id')->all();
                        return DeletionWarnings::affectedWarningMessage(FacetType::OptionValue, $valueIds, $store->id, true);
                    }),
            ]);
    }
}
