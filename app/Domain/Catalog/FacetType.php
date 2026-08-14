<?php

namespace App\Domain\Catalog;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Support\Contracts\HasLabel;

/**
 * Facet type dictionary
 * Used in Product create/edit/delete, all product related entities, Facet index build/cleanup, filter page table
 * Be careful here
 */
enum FacetType: int implements HasLabel
{
    case Category      = 1;
    case Manufacturer  = 2;
    case Attribute     = 3;
    case Option        = 4;
    case Tag           = 5;
    case IsFeatured    = 6;
    case HasDiscount   = 7;
    case Bestseller    = 8;
    case BestReviews   = 9;

    /**
     * Get label for app\Filament\Resources\FacetPages\Tables\FacetPagesTable.php
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Category      => __('admin.catalog.facets.types.category'),
            self::Manufacturer  => __('admin.catalog.facets.types.manufacturer'),
            self::Attribute     => __('admin.catalog.facets.types.attribute'),
            self::Option        => __('admin.catalog.facets.types.option'),
            self::Tag           => __('admin.catalog.facets.types.tag'),
            self::IsFeatured    => __('admin.catalog.facets.types.is_featured'),
            self::HasDiscount   => __('admin.catalog.facets.types.has_discount'),
            self::Bestseller    => __('admin.catalog.facets.types.bestseller'),
            self::BestReviews   => __('admin.catalog.facets.types.best_reviews'),
        };
    }

    /**
     * Get icon for app\Filament\Resources\FacetPages\Tables\FacetPagesTable.php
     * @return string
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Category      => NavigationItem::Categories->icon(),
            self::Manufacturer  => NavigationItem::Manufacturers->icon(),
            self::Attribute     => NavigationItem::Attributes->icon(),
            self::Option        => NavigationItem::Options->icon(),
            self::Tag           => NavigationItem::Tags->icon(),
            self::IsFeatured    => 'heroicon-o-fire',
            self::HasDiscount   => 'heroicon-o-currency-dollar',
            self::Bestseller    => 'heroicon-o-arrow-trending-up',
            self::BestReviews   => 'heroicon-o-hand-thumb-up',
            
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Category      => 'success',
            self::Manufacturer  => 'success',
            self::Attribute     => 'primary',
            self::Option        => 'primary',
            self::Tag           => 'primary',
            self::IsFeatured    => 'info',
            self::HasDiscount   => 'info',
            self::Bestseller    => 'info',
            self::BestReviews   => 'info',
        };
    }

    public function sortPriority(): int
    {
        return match ($this) {
            self::Category, self::Manufacturer        => 1,
            self::Attribute, self::Option, self::Tag  => 2,
            default                                   => 3,
        };
    }
}