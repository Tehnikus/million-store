<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Global\Store;
use App\Models\Global\Language;

use Filament\Facades\Filament;

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

    // Add store_id on save
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (blank($model->store_id)) {
                $model->store_id = Filament::getTenant()->id;
            }
        });
    }
    
    // Pages relaion
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
    
    // Slug create/edit form relations
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Slug create/edit form relations
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    // Slug create/edit form relations
    public function redirectedTo(): BelongsTo
    {
        return $this->belongsTo(Slug::class, 'redirected_to_id');
    }
}