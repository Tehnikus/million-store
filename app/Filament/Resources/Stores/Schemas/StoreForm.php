<?php
namespace App\Filament\Resources\Stores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Callout;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('admin.stores.fields.name'))
                    ->helperText(new HtmlString(__('admin.stores.helpers.name'))),

                TextInput::make('host')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label(__('admin.stores.fields.host'))
                    ->helperText(new HtmlString(__('admin.stores.helpers.host')))
                    ->prefix('https://'),

                Toggle::make('is_active')
                    ->label(__('admin.stores.fields.is_active'))
                    ->default(true),

                Callout::make(__('admin.stores.helpers.on_save_title'))
                    ->description(__('admin.stores.helpers.on_save_info'))
                    ->info()
                    ->visibleOn('create') // Show only on shop creation
                    ->columnSpanFull(),
            ]);
    }
}