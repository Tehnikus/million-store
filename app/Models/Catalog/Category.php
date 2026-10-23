<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Concerns\BatchesCatalogDeletion;
use App\Domain\Catalog\FacetType;
use App\Domain\Media\ImageType;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;
use App\Domain\Seo\HasSlugs;
use App\Domain\Media\Concerns\HasProcessedImages;

class Category extends Model
{
    use HasTranslations;
    use HasSlugs;
    use HasProcessedImages;
    use BatchesCatalogDeletion;

    protected $fillable = [
        'store_id',
        'is_active',
        'parent_id',
        'show_in_facets',
        'sort_order',
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'images',
        'description_short',
        'description_full',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
        'robots',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'show_in_facets'        => 'boolean',
        'sort_order'            => 'integer',
        'name'                  => 'array',
        'h1'                    => 'array',
        'meta_title'            => 'array',
        'meta_description'      => 'array',
        'images'                => 'array',
        'description_short'     => 'array',
        'description_full'      => 'array',
        'seo_keywords'          => 'array',
        'faq'                   => 'array',
        'how_to'                => 'array',
        'footer'                => 'array',
    ];
    protected $translatable = [
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'description_short',
        'description_full',
        'seo_keywords',
        'faq',
        'how_to',
        'footer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parent(): BelongsTo
    {
        // Pass the class itself and the explicit foreign key column
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the immediate child records for this record.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // Reverse category relation for ManageCategoryProducts
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'facet_index', 'facet_value_id', 'product_id')
            ->withPivotValue('facet_type_id', FacetType::Category->value)
            ->withPivot(['store_id', 'facet_group_id', 'sort_order']);
    }

    protected static function booted(): void
    {
        // Update facet_group_id if category->parent_id was changed
        static::updated(function (Category $category) {
            if (!$category->wasChanged('parent_id')) {
                return;
            }

            FacetIndex::where('facet_type_id', FacetType::Category->value)
                ->where('facet_value_id', $category->id)
                ->where('store_id', $category->store_id)
                ->update(['facet_group_id' => $category->parent_id ?? 0]);

            DB::table('facet_page_index')
                ->where('facet_type_id', FacetType::Category->value)
                ->where('facet_value_id', $category->id)
                ->where('store_id', $category->store_id)
                ->update(['facet_group_id' => $category->parent_id ?? 0, 'updated_at' => now()]);
                // TODO: Maybe touch() affected facet pages, so if cache is bound to model events it can be updated?
        });
    }

    /**
     * Settings for image conversions
     * @return array{images: array{slug_source: string, type: string}}
     */
    public function imageColumns(): array
    {
        return [
            'images' => [
                'type'          => ImageType::Category, // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source'   => 'name',              // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }

    /**
     * Cache category list for select dropdowns with Octane support
     * @param int $storeId
     * @return Collection
     */
    public static function categoryChoices(int $storeId): Collection
    {
        $key = __METHOD__ . ".{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = self::query()
            ->where('store_id', $storeId)
            ->get()
            ->mapWithKeys(fn (self $model) => [$model->id => $model->name]);

        Context::add($key, $choices->all());

        return $choices;
    }


    /**
     * id => parent_id of every category of the store, one query per request (Octane-safe via Context)
     * @return array<int, int|null>
     */
    public static function parentMap(int $storeId): array
    {
        $key = __METHOD__ . ".{$storeId}";

        if (Context::has($key)) {
            return Context::get($key);
        }

        $map = self::query()
            ->where('store_id', $storeId)
            ->pluck('parent_id', 'id')
            ->map(fn($parentId) => $parentId === null ? null : (int) $parentId)
            ->all();

        Context::add($key, $map);

        return $map;
    }

    /**
     * Ids of the whole subtree below this category, built in memory from parentMap()
     * @return int[]
     */
    public function descendantIds(): array
    {
        $map = self::parentMap($this->store_id);
        $result = [];
        $queue = [$this->id];

        while ($queue) {
            $current = array_shift($queue);
            foreach ($map as $id => $parentId) {
                if ($parentId === $current && !in_array($id, $result, true)) {
                    $result[] = $id;
                    $queue[] = $id;
                }
            }
        }

        return $result;
    }
}
