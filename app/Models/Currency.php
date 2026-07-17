<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Currency extends Model
{
    protected $fillable = [
        'name',
        'iso_code',
        'sign',
        'rate',
        'rate_default',
        'is_active',
    ];
    protected static function booted(): void
    {
        // If currency is saved as default update other currencies and set rate_default to false
        // Also set current currency rate to 1 and calculate other currency rates according to previous rate   
        static::saving(function (Currency $currency) {
            $becomingDefault = $currency->rate_default && $currency->isDirty('rate_default');

            if ($becomingDefault) {
                $oldRateOfNewBase = $currency->getOriginal('rate');

                if ($oldRateOfNewBase > 0) {
                    static::where('id', '!=', $currency->id)
                        ->update([
                            'rate_default' => false,
                            'rate' => DB::raw("rate / {$oldRateOfNewBase}"),
                        ]);
                }

                $currency->rate = 1;
            }
        });
    }
}
