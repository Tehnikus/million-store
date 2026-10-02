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

class OptionValue extends Model
{
    use HasTranslations;
    use HasSlugs;
    // use HasProcessedImages;

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

    // Cleanup facet index on delete
    use HasFacetIndexCleanup;
    public function facetType(): FacetType
    {
        return FacetType::OptionValue;
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    // public function imageColumns(): array
    // {
    //     return [
    //         'images' => [
    //             'type'        => 'option',    // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
    //             'slug_source' => 'name',      // Translatable field to take converted image names from. Will be slugged
    //         ],
    //     ];
    // }

        // Cache option list for select dropdowns with Octane support
    protected static function optionValueChoices(?int $optionId, ?int $storeId): Collection
    {
        if (blank($optionId) || blank($storeId)) {
            return collect();
        }

        $key = "option_value_choices.{$optionId}";

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
}
