<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Support\AdminMenu\NavigationItem;

class ManufacturersMetaTags extends AbstractMetaTagsFormulaPage
{
    protected string $view = 'filament.clusters.meta-editor.pages.manufacturers-meta-tags';

    protected function entityType(): string
    {
        return 'manufacturer';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Manufacturers->labelPlural();
    }

    public static function getNavigationIcon(): ?string
    {
        return NavigationItem::Manufacturers->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationItem::Manufacturers->sort();
    }
}