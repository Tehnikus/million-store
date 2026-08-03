<?php

namespace App\Domain\Catalog;

/**
 * Facet type dictionary
 */
enum FacetType: int
{
    case Category      = 1;
    case Manufacturer  = 2;
    case Attribute     = 3;
    case Option        = 4;
    case Tag           = 5;

    public function label(): string
    {
        return match ($this) {
            self::Category      => __('admin.catalog.facets.types.category'),
            self::Manufacturer  => __('admin.catalog.facets.types.manufacturer'),
            self::Attribute     => __('admin.catalog.facets.types.attribute'),
            self::Option        => __('admin.catalog.facets.types.option'),
            self::Tag           => __('admin.catalog.facets.types.tag'),
        };
    }
}