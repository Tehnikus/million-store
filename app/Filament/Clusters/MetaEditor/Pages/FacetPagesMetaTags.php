<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Support\AdminMenu\NavigationItem;

class FacetPagesMetaTags extends AbstractMetaTagsFormulaPage
{
    protected string $view = 'filament.clusters.meta-editor.pages.facetpages-meta-tags';

    protected function entityType(): string
    {
        return 'facet-page';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::FacetPages->labelPlural();
    }

    public static function getNavigationIcon(): ?string
    {
        return NavigationItem::FacetPages->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationItem::FacetPages->sort();
    }
}