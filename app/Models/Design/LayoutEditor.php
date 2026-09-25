<?php

namespace App\Models\Design;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutEditor extends Model
{
    protected $table = 'design_layout';
    protected $fillable = [
        'store_id',
        'layout',
    ];

    public $casts = [
        'layout' => 'array',
    ];
    // This model depends on current store context
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
