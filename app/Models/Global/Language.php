<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'iso_code',
        'locale',
        'ts_config',
        'image',
        'default_currency_id',
        'is_active',
    ];

    /**
     * Update store_languages.is_active and store_languages.is_default if language was updated
     */
    protected static function booted(): void
    {
        // Set language is_active = false across all stores if language is set is_active = false through it's edit page
        static::updated(function (Language $language) {
            
            // Check wether is_active was changed and set to false
            if ($language->wasChanged('is_active') && $language->is_active === false) {
                
                // If so, then set all store_languages.is_active and store_languages.is_default to false
                StoreLanguage::where('language_id', $language->id)->update(['is_active' => false, 'is_default' => false]);
            }
            
        });
    }

    /**
     * Get the currency that owns the Language
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id', 'id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(
            Store::class,         // Model
            'store_languages',    // Table name
            'language_id',        // Column of this model (Language) in pivot table
            'store_id'            // Column of related model (Store) in pivot table
        )->withPivot('is_active');
    }
}
