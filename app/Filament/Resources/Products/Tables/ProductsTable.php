<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\Columns\ConversionImageColumn;
use App\Models\Catalog\Product;
use App\Models\Global\Currency;
// use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
// use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
       
        $defaultCurrencyId = Currency::where('rate_default', true)->value('id');
        return $table
            // Filter results bu current store id
            // This separates model from store_id, only filament forms know about it
            // Thus models do not depend of filament tenant context
            ->modifyQueryUsing(function (Builder $query) {
                $storeId =  Filament::getTenant()->id;
                
                $query
                    ->with(['descriptions' => function ($subQuery) use ($storeId) {
                        // Get descriptions of current store only
                        $subQuery->where('store_id', $storeId);

                    }])
                    ->with(['priceTiers' => function ($subQuery) use ($storeId) {
                        // Get prices of current store only
                        $subQuery
                            ->where('store_id', $storeId)
                            ->whereNull('customer_group_id') // Customer group TODO
                            ->where(function ($q) {
                                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                            })
                            ->orderByDesc('priority')
                            ->with('prices'); // Join all tier prices
                    }])
                ;
            })

            ->columns([
                ConversionImageColumn::make('images')
                    ->conversion('miniature'),

                // Global SKU and name
                TextColumn::make('sku')
                    ->label(__('admin.catalog.products.fields.global_name') .'/'. __('admin.catalog.products.fields.sku'))
                    ->formatStateUsing(function ($record) {
                        return new HtmlString(
                            "<div>{$record->global_name}</div>" . 
                            "<div style=\"color: var(--gray-400)\">{$record->sku}</div>" 
                        );
                    }),
                // Store scoped name
                TextColumn::make('descriptions.name')
                    ->label(__('admin.catalog.products.fields.store_name')),

                TextColumn::make('regular_price')
                    ->label(__('admin.catalog.products.fields.price'))
                    ->getStateUsing(function (Product $record) use ($defaultCurrencyId) {
                        $tier = $record->priceTiers->firstWhere('is_discount', false);
                        $price = $tier?->prices->firstWhere('currency_id', $defaultCurrencyId);

                        return $price?->price;
                    })
                    ->money(fn () => Currency::find($defaultCurrencyId)?->iso_code ?? 'USD'), // or ->suffix($sign) TODO

                TextColumn::make('discount_price')
                    ->label(__('admin.catalog.products.fields.discount'))
                    ->getStateUsing(function (Product $record) use ($defaultCurrencyId) {
                        $tier = $record->priceTiers->firstWhere('is_discount', true);
                        $price = $tier?->prices->firstWhere('currency_id', $defaultCurrencyId);

                        return $price?->price;
                    })
                    ->placeholder('--'),

                // Product status: if it is active and if exists in current store
                // IconColumn::make('is_active')
                //     ->label(__('admin.catalog.products.fields.is_active'))
                //     ->getStateUsing(fn(Product $record) => $record->descriptions?->first()?->is_active ?? 'not_associated')
                //     ->icon(fn($state): string => match ($state) {
                //         true                => 'heroicon-s-play',
                //         false               => 'heroicon-s-stop',
                //         'not_associated'    => 'heroicon-s-x-circle',
                //         default             => 'heroicon-s-x-circle',
                //     })
                //     ->color(fn($state): string => match ($state) {
                //         true                => 'success',
                //         false               => 'danger',
                //         'not_associated'    => 'gray',
                //         default             => 'gray',
                //     })
                //     ->tooltip(fn($state): string => match ($state) {
                //         true                => __('admin.catalog.products.fields.is_active'),
                //         false               => __('admin.catalog.products.fields.is_not_active'),
                //         'not_associated'    => __('admin.catalog.products.fields.is_not_associated'),
                //         default             => __('admin.catalog.products.fields.is_not_associated'),
                //     })
                //     ->alignment('center')
                //     ->width('1%')
                //     ->action(
                //         Action::make('toggleActive')
                //             ->requiresConfirmation(false)
                //             ->action(function (Product $record) {
                //                 $record->currentDescription()?->update([
                //                     'is_active' => ! $record->currentDescription()?->is_active,
                //                 ]);
                //             })
                //     ),

                // Toggle product state. Has three states, actually: true, fasle and null (when not associated to store)
                // Thus a little bit of logic used here
                ToggleColumn::make('descriptions.is_active')
                    ->updateStateUsing(function (Product $record, bool $state) {$record->descriptions->first()?->update(['is_active' => $state]);})
                    ->getStateUsing(fn(Product $record) => $record->descriptions?->first()?->is_active)
                    ->onColor('success')
                    ->onIcon('heroicon-s-play')
                    ->offColor(fn(Product $record) => $record->descriptions?->first()?->is_active === null ? 'black' : 'danger')
                    ->offIcon(fn(Product $record) => $record->descriptions?->first()?->is_active === null ? 'heroicon-s-x-mark' : 'heroicon-s-stop')
                    ->disabled(fn(Product $record) => $record->descriptions?->first()?->is_active === null)
                    // // Due some kind of bug the tooltip is not updated with toggle state I have to comment this out and use single tooltip 
                    // ->tooltip(fn(Product $record) => match ($record->descriptions?->first()?->is_active) {
                    //     true   => __('admin.catalog.products.fields.is_active'),
                    //     false  => __('admin.catalog.products.fields.is_not_active'),
                    //     null   => __('admin.catalog.products.fields.is_not_associated'),
                    // })
                    ->tooltip(fn (Product $record) => $record->descriptions?->first()?->is_active === null ? __('admin.catalog.products.fields.is_not_associated') : null)
                    ->label(__('admin.catalog.products.fields.is_active')),


                // Dates
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
