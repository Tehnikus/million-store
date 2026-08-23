<?php

namespace App\Models\Catalog;

use App\Domain\Catalog\FacetType;
use App\Domain\Support\Concerns\InheritsColumnFromParent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacetPageIndex extends Model
{
    protected $table = 'facet_page_index';

    protected $fillable = [
        'facet_page_id',
        'facet_type_id',
        'facet_group_id',
        'facet_value_id',
        'is_root',
    ];
    
    protected $casts = [
        'facet_page_id'     => 'integer',
        'facet_type_id'     =>  FacetType::class,
        'facet_group_id'    => 'integer',
        'facet_value_id'    => 'integer',
        'is_root'           => 'boolean',
    ];

    public function facetPage(): BelongsTo
    {
        return $this->belongsTo(FacetPage::class, 'facet_page_id');
    }

    use InheritsColumnFromParent;
    protected static function inheritedColumns(): array
    {
        return [
            'store_id' => ['facetPage', 'store_id'],
        ];
    }
}
