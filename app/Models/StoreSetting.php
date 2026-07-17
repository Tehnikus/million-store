<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSetting extends Model
{
    protected $casts = [
        'image_dimensions'      => 'array',
        'delivery_settings'     => 'array',
        'checkout_settings'     => 'array',
        'legal_settings'        => 'array',
        'tax_settings'          => 'array',
        'analytics_settings'    => 'array',
        'seo_defaults'          => 'array',
        'notification_settings' => 'array',
        'maintenance_settings'  => 'array',
    ];

    protected $fillable = [
        'store_id',
        'image_dimensions',
        'delivery_settings',
        'checkout_settings',
        'legal_settings',
        'tax_settings',
        'analytics_settings',
        'seo_defaults',
        'notification_settings',
        'maintenance_settings',
    ];


    // This model depends on current store context
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function infoPage(): BelongsTo
    {
        return $this->belongsTo(StoreInfoPage::class);
    }
}
