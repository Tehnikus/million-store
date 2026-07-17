<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class CustomerGroup extends Model
{
    use HasTranslations;
    protected $fillable = [
        'code',
        'name',
        'price_modifier_percent',
        'free_shipping',
        'requires_approval',
        'show_prices',
        'tax_exempt',
        'is_default',
        'sort_order',
        'is_active',
    ];

    public $translatable = [
        'name'
    ];

    protected $casts = [
        'name'                      => 'array',
        'price_modifier_percent'    => 'decimal:2',
        'free_shipping'             => 'boolean',
        'requires_approval'         => 'boolean',
        'show_prices'               => 'boolean',
        'tax_exempt'                => 'boolean',
        'is_default'                => 'boolean',
        'is_active'                 => 'boolean',
    ];


    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class);
    }
}
