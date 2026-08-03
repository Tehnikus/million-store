<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\FacetType;
use App\Domain\Seo\HasSlugs;
use App\Models\Catalog\ProductDescription;
use Illuminate\Database\Eloquent\Model;
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
}
