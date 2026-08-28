<?php

namespace App\Models\Catalog;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'parent_id',
        'store_id',
        'locale',
        'author', 
        'reviewBody', 
        'positiveNotes', 
        'negativeNotes', 
        'reviewRating',
        'author_email',
        'is_admin_reply',
        'is_approved',
    ];

    protected $casts = [
        'rating'            => 'integer',
        'is_admin_reply'    => 'boolean',
        'is_approved'       => 'boolean',
        'positiveNotes'     => 'array',
        'negativeNotes'     => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $review) {
            if ($review->parent_id) {
                $parent = static::find($review->parent_id);
                $review->thread_id = $parent?->thread_id ?? $review->parent_id;
            }
            // If it is the first review in thread, it has no own id, neither thread_id. So we set thread_id = id after review is created()
        });


        // Update entry after review is created.
        // If thread id is null, then it is the first review. So set thread id to self 
        static::created(function (self $review) {
            if ($review->thread_id === null) {
                $review->thread_id = $review->id;
                $review->saveQuietly(); // Skip events for this write
            }
        });
        
        // Update is_approved on all child reviews below current
        static::updated(function (self $review) {
            if ($review->wasChanged('is_approved')) {
                $review->replies()->get()->each(
                    fn(self $reply) => $reply->update(['is_approved' => $review->is_approved])
                );
            }
        });
    }
}
