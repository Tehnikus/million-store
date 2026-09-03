<?php

namespace App\Domain\Catalog;
use App\Filament\Support\AdminMenu\NavigationItem;
use Filament\Support\Contracts\HasLabel;
use App\Models\Catalog\{AttributeValue, Category, Manufacturer, OptionValue, Tag};

/**
 * Facet type dictionary
 * Used in Product create/edit/delete, all product related entities, Facet index build/cleanup, filter page table
 * Be careful here
 */
enum FacetType: int implements HasLabel
{

    /**
     * Most crucial: facet_type_id which is saved to DB
     * Other stuf here is mostly cosmetic
     */
    case Category           = 1;
    case Manufacturer       = 2;
    case AttributeValue     = 3;
    case OptionValue        = 4;
    case Tag                = 5;
    case IsFeatured         = 6;
    case HasDiscount        = 7;
    case Bestseller         = 8;
    case BestReviews        = 9;

    /**
     * List of facet that can be selected as root for FacetPage and FacetPageIndex
     * @return bool
     */
    public function canBeRoot(): bool
    {
        return match ($this) {
            self::Category, self::Manufacturer => true,
            default => false,
        };
    }

    /**
     * Defines wether facet type can be selected multiple times
     * @return bool
     */
    public function isSingleton(): bool
    {
        return match ($this) {
            self::Category, self::Manufacturer => true,
            self::AttributeValue, self::OptionValue, self::Tag => false,
            default => true, // static flag facets are also singletons
        };
    }

    public function modelClass(): ?string
    {
        return match ($this) {
            self::Category          => Category::class,
            self::Manufacturer      => Manufacturer::class,
            self::AttributeValue    => AttributeValue::class,
            self::OptionValue       => OptionValue::class,
            self::Tag               => Tag::class,
            default                 => null, // Static/dynamic facets has no backing model
        };
    }

    public function groupIdColumn(): ?string
    {
        return match ($this) {
            self::Category, self::Manufacturer 
                => 'parent_id',
            self::AttributeValue
                => 'attribute_id',
            self::OptionValue
                => 'option_id',
            default 
                => null, // Static/dynamic facets has no parent
        };
    }

    /**
     * Get label for app\Filament\Resources\FacetPages\Tables\FacetPagesTable.php
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Category          => __('admin.catalog.facets.types.category'),
            self::Manufacturer      => __('admin.catalog.facets.types.manufacturer'),
            self::AttributeValue    => __('admin.catalog.facets.types.attribute'),
            self::OptionValue       => __('admin.catalog.facets.types.option'),
            self::Tag               => __('admin.catalog.facets.types.tag'),
            self::IsFeatured        => __('admin.catalog.facets.types.is_featured'),
            self::HasDiscount       => __('admin.catalog.facets.types.has_discount'),
            self::Bestseller        => __('admin.catalog.facets.types.bestseller'),
            self::BestReviews       => __('admin.catalog.facets.types.best_reviews'),
        };
    }

    /**
     * Get icon for app\Filament\Resources\FacetPages\Tables\FacetPagesTable.php
     * @return string
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Category          => NavigationItem::Categories->icon(),
            self::Manufacturer      => NavigationItem::Manufacturers->icon(),
            self::AttributeValue    => NavigationItem::Attributes->icon(),
            self::OptionValue       => NavigationItem::Options->icon(),
            self::Tag               => NavigationItem::Tags->icon(),
            self::IsFeatured        => 'heroicon-o-star',
            self::HasDiscount       => 'heroicon-o-currency-dollar',
            self::Bestseller        => 'heroicon-o-arrow-trending-up',
            self::BestReviews       => 'heroicon-o-hand-thumb-up',
            
        };
    }

    /**
     * Facets badge color in admin FacetPage table in facet column
     * @return int
     */    
    public function getColor(): ?string
    {
        return match ($this) {
            self::Category          => 'success',
            self::Manufacturer      => 'success',
            self::AttributeValue    => 'primary',
            self::OptionValue       => 'primary',
            self::Tag               => 'primary',
            self::IsFeatured        => 'info',
            self::HasDiscount       => 'info',
            self::Bestseller        => 'info',
            self::BestReviews       => 'info',
        };
    }

    /**
     * Facets sort in admin FacetPage table in facet column
     * @return int
     */
    public function sortPriority(): int
    {
        return match ($this) {
            self::Category, self::Manufacturer 
                => 1,
            self::AttributeValue, self::OptionValue, self::Tag  
                => 2,
            default                                   
                => 3,
        };
    }
}