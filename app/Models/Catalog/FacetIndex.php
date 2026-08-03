<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\FacetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacetIndex extends Model
{
    protected $table = 'facet_index';
    
    protected $fillable = [
        'product_id', 
        'store_id', 
        'facet_type_id',
        'facet_group_id', 
        'facet_value_id', 
        'sort_order',
    ];

    protected $casts = [
        'facet_type_id' => FacetType::class, // enum cast
        'sort_order'    => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}