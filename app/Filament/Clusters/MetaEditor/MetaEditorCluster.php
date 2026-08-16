<?php

namespace App\Filament\Clusters\MetaEditor;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class MetaEditorCluster extends Cluster
{
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::MetaEditor;
    }
}
