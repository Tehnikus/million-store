<?php

namespace App\Models\Seo;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaTagFormula extends Model
{
    protected $table = 'meta_editor_formulas';

    protected $fillable = [
        'store_id',
        'entity_type',
        'target_field',
        'locale',
        'currency_id',
        'formula',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}