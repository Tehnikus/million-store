<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogComment extends Model
{
    protected $fillable = [
        'blog_post_id',
        'parent_id',
        'locale',
        'author_name',
        'author_email',
        'body',
        'rating',
        'is_admin_reply',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_admin_reply' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    

    protected static function booted(): void
    {
        static::creating(function (self $comment) {
            if ($comment->parent_id) {
                $parent = static::find($comment->parent_id);
                $comment->thread_id = $parent?->thread_id ?? $comment->parent_id;
            }
            // If it is the first comment in thread, it has no own id, neither thread_id. So we set thread_id = id after comment is created()
        });


        // Update entry after comment is created.
        // If thread id is null, then it is the first comment. So set thread id to self 
        static::created(function (self $comment) {
            if ($comment->thread_id === null) {
                $comment->thread_id = $comment->id;
                $comment->saveQuietly(); // Skip events for this write
            }
        });
        
        // Update is_approved on all child comments below current
        static::updated(function (self $comment) {
            if ($comment->wasChanged('is_approved')) {
                $comment->replies()->get()->each(
                    fn(self $reply) => $reply->update(['is_approved' => $comment->is_approved])
                );
            }
        });
    }
}