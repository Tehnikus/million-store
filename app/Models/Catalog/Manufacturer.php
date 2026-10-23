<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Concerns\BatchesCatalogDeletion;
use App\Domain\Catalog\FacetType;
use App\Domain\Media\ImageType;
use App\Domain\Support\Concerns\InvalidatesNavigationBadges;
use App\Filament\Resources\Manufacturers\ManufacturerResource;
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

class Manufacturer extends Model
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
        'sort_order'            => 'integer',
        'parent_id'             => 'integer',
        'show_in_facets'        => 'boolean',
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


    /**
     * id => parent_id of every manufacturer of the store, one query per request (Octane-safe via Context)
     * Same as Category::parentMap()
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
     * Ids of the whole subtree below this manufacturer, built in memory from parentMap()
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

    // Reverse manufacturer relation for ManageManufacturerProducts
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'facet_index', 'facet_value_id', 'product_id')
            ->withPivotValue('facet_type_id', FacetType::Manufacturer->value)
            ->withPivot(['store_id', 'facet_group_id', 'sort_order']);
    }

    protected static function booted(): void
    {
        static::updated(function (Manufacturer $manufacturer) {
            if (!$manufacturer->wasChanged('parent_id')) {
                return;
            }

            FacetIndex::where('facet_type_id', FacetType::Manufacturer->value)
                ->where('facet_value_id', $manufacturer->id)
                ->where('store_id', $manufacturer->store_id)
                ->update(['facet_group_id' => $manufacturer->parent_id ?? 0]);

            DB::table('facet_page_index')
                ->where('facet_type_id', FacetType::Manufacturer->value)
                ->where('facet_value_id', $manufacturer->id)
                ->where('store_id', $manufacturer->store_id)
                ->update(['facet_group_id' => $manufacturer->parent_id ?? 0, 'updated_at' => now()]);
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
                'type' => ImageType::Manufacturer, // Image type. Sets which dimensions to choose from StoreSettings and what directory to store images in
                'slug_source' => 'name',           // Translatable field to take converted image names from. Will be slugged
            ],
        ];
    }


    /**
     * Cache manufacturer list for select dropdowns with Octane support
     * @param int $storeId
     * @return Collection
     */
    public static function manufacturerChoices(int $storeId): Collection
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

    // Clear cache of admin navigation badges when model is changed
    use InvalidatesNavigationBadges;
    protected static function navigationBadgeResources(): array
    {
        return [ManufacturerResource::class];
    }
}
