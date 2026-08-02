<?php

namespace App\Models\Store;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class StoreHomepageDescription extends Model
{
    use HasTranslations;
    protected $fillable = [
        'store_id',
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
        'robots',
    ];
    public $translatable = [
        'name',
        'h1',
        'meta_title',
        'meta_description',
        'description_short',
        'description_full',
        'seo_keywords',
        'faq',
        'how_to',
        'footer'
    ];
    public $casts = [
        'name'                 => 'array',
        'h1'                   => 'array',
        'meta_title'           => 'array',
        'meta_description'     => 'array',
        'description_short'    => 'array',
        'description_full'     => 'array',
        'seo_keywords'         => 'array',
        'faq'                  => 'array',
        'how_to'               => 'array',
        'footer'               => 'array',
    ];
    // This model depends on current store context
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
