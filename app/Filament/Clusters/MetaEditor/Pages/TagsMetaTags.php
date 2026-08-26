<?php

namespace App\Filament\Clusters\MetaEditor\Pages;

use App\Filament\Support\AdminMenu\NavigationItem;

class TagsMetaTags extends AbstractMetaTagsFormulaPage
{
    protected string $view = 'filament.clusters.meta-editor.pages.tags-meta-tags';

    protected function entityType(): string
    {
        return 'tag';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationItem::Tags->labelPlural();
    }

    public static function getNavigationIcon(): ?string
    {
        return NavigationItem::Tags->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationItem::Tags->sort();
    }
}