<?php

namespace App\Filament\Resources\OrderStatuses\Tables;

use App\Models\Order\OrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrderStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->formatStateUsing(function($record) {
                        // Disply icon
                        $icon = '';
                        if ($record->icon) {
                            $iconName = str_starts_with($record->icon, 'o-') ? ('heroicon-' . $record->icon) : ('heroicon-s-' . $record->icon);
                            $icon = svg($iconName)->toHtml();
                        }
                        // CSS filter for disabled records
                        $filter = $record->is_active ? '' : 'filter: grayscale(1);';
                        // Display status name as color badge with icon and grayscaled if status is not active
                        return new HtmlString("
                            <span class='fi-badge fi-size-xl' style='--bg-color: {$record->color}; font-size:1rem; background-color: var(--bg-color); color: contrast-color(var(--bg-color)); {$filter}'><span style='width: 20px; height: 20px'>{$icon}</span> {$record->name}</span>
                        ");
                    })
                    ->html()
                    ->label(__('admin.orders.statuses.fields.name')),
                ToggleColumn::make('is_default')
                    ->beforeStateUpdated(function (OrderStatus $record) {
                        OrderStatus::where('id', '!=', $record->id)->update(['is_default' => false]);
                    })
                    ->width('100px')
                    ->label(__('admin.orders.statuses.fields.is_default')),
                ToggleColumn::make('is_shipped')
                    ->width('100px')
                    ->label(__('admin.orders.statuses.fields.is_shipped')),
                ToggleColumn::make('is_paid')
                    ->width('100px')
                    ->label(__('admin.orders.statuses.fields.is_paid')),
                ToggleColumn::make('is_finished')
                    ->beforeStateUpdated(function (OrderStatus $record) {
                        OrderStatus::where('id', '!=', $record->id)->update(['is_finished' => false]);
                    })
                    ->width('100px')
                    ->label(__('admin.orders.statuses.fields.is_finished')),
                ToggleColumn::make('is_active')
                    ->width('100px')
                    ->label(__('admin.orders.statuses.fields.is_active')),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
