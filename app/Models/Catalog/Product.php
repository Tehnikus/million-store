<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\FacetType;
use App\Domain\Seo\HasSlugs;
use App\Models\Catalog\FacetIndex;
use App\Models\Catalog\ProductDescription;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductPriceTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;
    use HasSlugs;
    protected $fillable = ['sku', 'global_name'];
    protected $casts = [
        'global_name' => 'array',
    ];

    protected $translatable = ['global_name'];

    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(ProductDescription::class);
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }
    
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function currentDescription(): ?ProductDescription
    {
        if (! $this->relationLoaded('descriptions')) {
            throw new \LogicException(
                'descriptions relation must be eager-loaded with a store_id constraint before calling currentDescription().'
            );
        }

        return $this->descriptions->first();
    }

    // Collect form data
    // See app\Filament\Resources\Products\Pages\EditProduct.php -> mutateFormDataBeforeFill()
    // Category facets
    public function categoryFacets(): HasMany
    {
        return $this->hasMany(FacetIndex::class)
            ->where('facet_type_id', FacetType::Category->value);
    }
    // Manufacturer facets
    public function manufacturerFacets(): HasMany
    {
        return $this->hasMany(FacetIndex::class)
            ->where('facet_type_id', FacetType::Manufacturer->value);
    }
    // Tag facets
    public function tagFacets(): HasMany
    {
        return $this->hasMany(FacetIndex::class)
            ->where('facet_type_id', FacetType::Tag->value);
    }

    // Reverse category relation for ManageCategoryProducts
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'facet_index', 'product_id', 'facet_value_id')
            ->withPivotValue('facet_type_id', FacetType::Category->value)
            ->withPivot(['store_id', 'facet_group_id', 'sort_order']);
    }

    // Reverse category relation for ManageManufacturerProducts
    public function manufacturers(): BelongsToMany
    {
        return $this->belongsToMany(Manufacturer::class, 'facet_index', 'product_id', 'facet_value_id')
            ->withPivotValue('facet_type_id', FacetType::Manufacturer->value)
            ->withPivot(['store_id', 'facet_group_id', 'sort_order']);
    }
}
