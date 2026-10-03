<?php

namespace App\Models\Store;

use App\Models\Global\Store;
use App\Models\Store\StoreInfoPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    protected $fillable = [
        'store_id',
        'image_dimensions',
        'delivery',
        'checkout',
        'legal',
        'taxes',
        'analytics',
        'seo_defaults',
        'notifications',
        'ai_settings',
        'layouts',
        'menu',
        'contacts',
        'homepage',
        'maintenance',
    ];

    protected $casts = [
        'image_dimensions'      => 'array',
        'delivery'              => 'array',
        'checkout'              => 'array',
        'legal'                 => 'array',
        'taxes'                 => 'array',
        'analytics'             => 'array',
        'seo_defaults'          => 'array',
        'notifications'         => 'array',
        'ai_settings'           => 'array',
        'layouts'               => 'array',
        'menu'                  => 'array',
        'contacts'              => 'array',
        'homepage'              => 'array',
        'maintenance'           => 'array',
    ];

    // This model depends on current store context
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

}
