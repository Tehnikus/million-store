<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreContact extends Model
{
    use HasTranslations;

    protected $fillable = [
        'store_id',
        'legal_name',
        'organization_description',
        'local_business_description',
        'address_country',
        'address_region',
        'address_locality',
        'address_street',
        'country_iso',
        'postal_code',
        'latitude',
        'longitude',
        'open_hours',
        'email',
        'phones',
        'social_links',
        'social_contacts',
    ];

    public $translatable = [
        'legal_name',
        'organization_description',
        'local_business_description',
        'address_country',
        'address_region',
        'address_locality',
        'address_street',
        'country_iso',
        'postal_code',
        'latitude',
        'longitude',
        'open_hours',
        'email',
        'phones',
        'social_links',
        'social_contacts',
    ];

    protected $casts = [
        'legal_name' => 'array',
        'organization_description' => 'array',
        'local_business_description' => 'array',
        'address_country' => 'array',
        'address_region' => 'array',
        'address_locality' => 'array',
        'address_street' => 'array',
        'country_iso' => 'array',
        'postal_code' => 'array',
        'latitude' => 'array',
        'longitude' => 'array',
        'open_hours' => 'array',
        'email' => 'array',
        'phones' => 'array',
        'social_links' => 'array',
        'social_contacts' => 'array',
    ];

    // This model depends on current store context
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}