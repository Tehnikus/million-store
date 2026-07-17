<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    
    // Default language currency so Google bot can see actual prices in separate language
    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    // Every store may have own set of associated languages
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_languages')
            ->using(StoreLanguage::class)
            ->withPivot(['is_default', 'is_active']);
    }
}
