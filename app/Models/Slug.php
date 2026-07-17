<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function redirectedTo(): BelongsTo
    {
        return $this->belongsTo(Slug::class, 'redirected_to_id');
    }
}