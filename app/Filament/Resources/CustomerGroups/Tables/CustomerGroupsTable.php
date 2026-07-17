<?php

namespace App\Filament\Resources\CustomerGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\HtmlString;
use App\Models\CustomerGroup;

class CustomerGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('admin.customers.customer_groups.fields.name')),
                TextColumn::make('code')
                    ->searchable()
                    ->width('1%')
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.code')),
                TextColumn::make('price_modifier_percent')
                    ->formatStateUsing(function(CustomerGroup $group) {
                        $badgeColor = $group->price_modifier_percent <= 0 ? 'success' : 'danger';
                        $prefixSign = $group->price_modifier_percent <= 0 ? '' : '+';
                        return new HtmlString('<span class="fi-color fi-color-' . $badgeColor . ' fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-md text-lg">' . $prefixSign . $group->price_modifier_percent . '%</span>');
                    })
                    ->sortable()
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    
                    ->label(__('admin.customers.customer_groups.fields.price_modifier_percent')),
                ToggleColumn::make('free_shipping')
                    ->width('1%')
                    ->wrapHeader()
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.free_shipping')),
                ToggleColumn::make('requires_approval')
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.requires_approval')),
                ToggleColumn::make('show_prices')
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.show_prices')),
                ToggleColumn::make('tax_exempt')
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.tax_exempt')),
                ToggleColumn::make('is_default')
                    ->beforeStateUpdated(function ($record, $state) {
                        // Reset 'is_default' to false for all records except current
                        if ($state) {
                            CustomerGroup::where('id', '!=', $record->id)->update(['is_default' => false]);
                        }
                    })
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(new HtmlString(__('admin.customers.customer_groups.fields.is_default'))),
                ToggleColumn::make('is_active')
                    ->width('1%')
                    ->wrapHeader()
                    ->alignment(Alignment::Center)
                    ->label(__('admin.customers.customer_groups.fields.is_active')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
