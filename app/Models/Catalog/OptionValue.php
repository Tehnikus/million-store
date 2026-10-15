<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Concerns\BatchesCatalogDeletion;
use App\Domain\Catalog\FacetType;
use App\Domain\Media\Concerns\HasProcessedImages;
use App\Domain\Seo\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Spatie\Translatable\HasTranslations;

class OptionValue extends Model
{
    use HasTranslations;
    use HasSlugs;
    use HasProcessedImages;
    use BatchesCatalogDeletion;

    protected $fillable = [
        'store_id',
        'is_active',
        'is_default',
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
        'is_default'       => 'boolean',
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

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    /**
     * Get products related to attribute value
     * @return BelongsToMany<Product, AttributeValue, TPivotModel>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'facet_index', 'facet_value_id', 'product_id')
            ->withPivotValue('facet_type_id', FacetType::OptionValue->value)
            ->withPivot(['store_id', 'facet_group_id', 'sort_order']);
    }

    public function imageColumns(): array
    {
        return [
            'images' => [
                'type'        => 'option',    // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name',      // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }

    /**
     * Cache option value list for select dropdowns with Octane support
     * @param mixed $optionId
     * @param int $storeId
     * @return Collection
     */
    public static function optionValueChoices(?int $optionId, int $storeId): Collection
    {
        if (blank($optionId)) {
            return collect();
        }

        $key = __METHOD__ . ".{$optionId}.{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = self::query()
            ->where('option_id', $optionId)
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (self $model) => [$model->id => $model->name]);

        Context::add($key, $choices->all());

        return $choices;
    }

    /**
     * Group option values by option groups to display them in select dropdowns
     * @param array $optionGroupIds
     * @param int $storeId
     * @return array
     */
    public static function optionValueGroupedChoices(array $optionGroupIds, int $storeId): array
    {
        $optionGroupNames = Option::optionChoices($storeId);
        $optionGroupIds   = filled($optionGroupIds) ? $optionGroupIds : $optionGroupNames->keys();

        return collect($optionGroupIds)
            ->map(fn ($id) => (int) $id)
            ->mapWithKeys(fn ($optionGroupId) => [
                $optionGroupNames->get($optionGroupId, "#{$optionGroupId}") => static::optionValueChoices($optionGroupId, $storeId)->all(),
            ])
            ->all();
    }
}