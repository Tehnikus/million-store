<?php

namespace App\Models\Catalog;

use App\Models\Catalog\ProductDescription;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
