<?php
namespace App\Models\Seo;

use App\Models\Global\Store;
use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Facades\Filament;

class Keyword extends Model
{
    protected $fillable = ['store_id', 'language_id', 'keyword_group_id', 'keyword', 'url'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (blank($model->store_id)) {
                $model->store_id = Filament::getTenant()->id;
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KeywordGroup::class, 'keyword_group_id');
    }
}