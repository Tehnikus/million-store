<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\Actions\PurgeDeletedCatalogValue;
use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;

class Attribute extends Model
{
    use HasTranslations;
    protected $fillable = [
        'store_id',
        'is_active',
        'show_in_facets',
        'sort_order',
        'name',
    ];
    protected $casts = [
        'is_active'             => 'boolean',
        'show_in_facets'        => 'boolean',
        'sort_order'            => 'integer',
        'name'                  => 'array',
    ];
    protected $translatable = [
        'name'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * The DB cascade would remove the values without model events, leaving their slugs, facet_index rows and
     * product data behind. Delete them through the model instead, so every value runs its own cleanup.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Attribute $group) => $group->values()->get()->each->delete());

        static::deleted(fn (Attribute $group) => app(PurgeDeletedCatalogValue::class)
            ->attributeGroup((int) $group->getKey(), (int) $group->store_id));
    }

    /**
     * Wrap delete procedure in one DB transaction
     * The group, all its values and the product cleanup succeed or fail together
     */
    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

    /**
     * Cache attribute list for select dropdowns with Octane support
     * @param int $storeId
     * @return Collection
     */
    public static function attributeChoices(int $storeId): Collection
    {
        $key = __METHOD__ . ".{$storeId}";

        if (Context::has($key)) {
            return collect(Context::get($key));
        }

        $choices = Attribute::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (self $model) => [$model->id => $model->name]);

        Context::add($key, $choices->all());

        return $choices;
    }
}