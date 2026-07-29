<?php

namespace App\Filament\Support\AdminMenu;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    // Parent menu items
    // Same order is used in admin sidebar
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
}