<?php

namespace App\Filament\Resources\CustomerGroups\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerGroupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('price_modifier_percent')
                    ->numeric(),
                IconEntry::make('free_shipping')
                    ->boolean(),
                IconEntry::make('requires_approval')
                    ->boolean(),
                IconEntry::make('show_prices')
                    ->boolean(),
                IconEntry::make('tax_exempt')
                    ->boolean(),
                IconEntry::make('is_default')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
