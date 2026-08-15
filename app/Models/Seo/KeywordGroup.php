<?php
namespace App\Models\Seo;

use App\Models\Global\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeywordGroup extends Model
{
    protected $fillable = ['store_id', 'name'];

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

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }
}