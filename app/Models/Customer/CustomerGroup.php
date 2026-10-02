<?php

namespace App\Models\Customer;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    // TODO Not needed?
    // public function customer(): BelongsToMany
    // {
    //     return $this->belongsToMany(Customer::class);
    // }

    // Relation for tabs in app\Filament\Resources\Customers\Pages\ListCustomers.php
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
