<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Support\AdminMenu\NavigationItem;

class ProductsMetaTags extends AbstractMetaTagsFormulaPage
{
    protected string $view = 'filament.clusters.meta-editor.pages.products-meta-tags';

    protected function entityType(): string
    {
        return 'product';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Products->labelPlural();
    }

    public static function getNavigationIcon(): ?string
    {
        return NavigationItem::Products->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationItem::Products->sort();
    }
}