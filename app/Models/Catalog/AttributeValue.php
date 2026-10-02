<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Concerns\HasFacetIndexCleanup;
use App\Domain\Catalog\FacetType;
use App\Domain\Media\Concerns\HasProcessedImages;
use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Spatie\Translatable\HasTranslations;

class AttributeValue extends Model
{
    use HasTranslations;
    use HasSlugs;
    // use HasProcessedImages;

    protected $fillable = [
        'store_id',
        'is_active',
        'show_in_facets',
        'sort_order',
        'name',
        'description',
        'images',
        'robots',
    ];
    protected $casts = [
        'store_id'         => 'integer',
        'is_active'        => 'boolean',
        'show_in_facets'   => 'boolean',
        'sort_order'       => 'integer',
        'name'             => 'array',
        'description'      => 'array',
        'images'           => 'array',
    ];
    protected $translatable = [
        'name',
        'description',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    // Cleanup facet index on delete
    use HasFacetIndexCleanup;
    public function facetType(): FacetType
    {
        return FacetType::AttributeValue;
    }

    // public function imageColumns(): array
    // {
    //     return [
    //         'images' => [
    //             'type'        => 'attribute',    // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
    //             'slug_source' => 'name',        // Translatable field to take converted image names from. Will be slugged
    //         ],
    //     ];
    // }

    /**
     * Cache attribute values list for select dropdowns with Octane support
     * @param mixed $attributeId
     * @return Collection
     */
    public static function attributeValueChoices(?int $attributeId): Collection
    {
        if (blank($attributeId)) {
            return collect();
        }

        $key = "attribute_value_choices.{$attributeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = AttributeValue::query()
            ->where('attribute_id', $attributeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (self $model) => [$model->id => $model->name]);

        Context::add($key, $choices->all());

        return $choices;
    }
}
