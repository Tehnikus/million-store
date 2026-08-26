<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Global\Store;
use App\Models\Global\Language;

class Slug extends Model
{
    protected $fillable = [
        'store_id',
        'language_id',
        'slug',
        'sluggable_type',
        'sluggable_id',
        'redirected_to_id',
        'is_active',
        'robots',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function redirectedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'redirected_to_id');
    }
}