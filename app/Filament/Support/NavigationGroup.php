<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Contracts\HasIcon;

/**
 * List of main admin navigation groups
 */
enum NavigationGroup: string implements HasLabel, HasIcon
{
    case Orders         = 'orders';
    case Catalog        = 'catalog';
    case Customers      = 'customers';
    case Stock          = 'stock';
    case Blog           = 'blog';
    case Seo            = 'seo';
    case Design         = 'design';
    case StoreSettings  = 'store_settings';
    case GlobalSettings = 'global_settings';

    public function getLabel(): string
    {
        return __("admin.navigation.groups.{$this->value}");
    }

    public function getIcon(): string | BackedEnum | Heroicon | null
    {
        return null; // Maybe set icons some time, for now - no icons on parent
    }
}