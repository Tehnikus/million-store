<?php

namespace App\Models\Order;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class OrderStatus extends Model
{
    use SoftDeletes; 
    use HasTranslations;
    protected $fillable = [
        'store_id',
        'is_active',
        'is_default',
        'is_paid',
        'is_shipped',
        'is_finished',
        'name',
        'icon',
        'color',
    ];

    public $translatable = [
        'name'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
