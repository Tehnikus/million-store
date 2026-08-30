<?php

namespace App\Filament\Resources\OrderStatuses\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class OrderStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        $store      = Filament::getTenant();
        $languages  = $store->activeLanguages();
        
        return $schema
            ->components([
                FusedGroup::make([
                    ...collect($languages)->map(fn($language) =>
                        TextInput::make("name.{$language->locale}")
                            ->required()
                            ->prefix($language->locale)
                            ->label(__('admin.orders.statuses.fields.name'))
                            ->placeholder(__('admin.orders.statuses.fields.name'))

                    )

                ]),

                ColorPicker::make('color')
                    ->label(__('admin.orders.statuses.fields.color'))
                    ->required(),

                Select::make('icon')
                ->options(
                        collect(Heroicon::cases())
                            ->mapWithKeys(function (Heroicon $icon) {
                                $iconName = str_starts_with($icon->value, 'o-') 
                                    ? ('heroicon-' . $icon->value) 
                                    : ('heroicon-s-' . $icon->value);

                                $html = '<div style="display: flex; width: 200px; height: 40px; align-items: center; flex-direction: row;">' . svg($iconName)->toHtml() . ' <span style="flex: 1 0 180px">' . $icon->name . '</span></div>';

                                return [$icon->value => $html];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->allowHtml()
                    ->label(__('admin.orders.statuses.fields.icon')),

                    

                Toggle::make('is_active')
                    ->label(__('admin.orders.statuses.fields.is_active')),

                Toggle::make('is_default')
                    ->label(__('admin.orders.statuses.fields.is_default')),

                Toggle::make('is_finished')
                    ->label(__('admin.orders.statuses.fields.is_finished')),

            ])
            ->columns(1);
    }
}
