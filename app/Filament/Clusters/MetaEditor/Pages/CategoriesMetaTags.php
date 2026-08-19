<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Support\AdminMenu\NavigationItem;

class CategoriesMetaTags extends AbstractMetaTagsFormulaPage
{
    protected string $view = 'filament.clusters.meta-editor.pages.categories-meta-tags';

    protected function entityType(): string
    {
        return 'category';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Categories->labelPlural();
    }

    public static function getNavigationIcon(): ?string
    {
        return NavigationItem::Categories->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationItem::Categories->sort();
    }
}