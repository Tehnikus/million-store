<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Currency extends Model
{
    protected $fillable = [
        'name', 
        'iso_code', 
        'sign', 
        'rate', 
        'decimal_places', 
        'rate_default', 
        'is_active'
    ];

    protected $casts = [
        'rate_default' => 'boolean',
        'is_active'    => 'boolean',
        'rate'         => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Currency $currency) {
            $becomingDefault = $currency->rate_default && $currency->isDirty('rate_default');

            if ($becomingDefault) {
                // If currency is new use rate from the POST, if currency exists use it's previous rate.
                $oldRateOfNewBase = $currency->exists ? $currency->getOriginal('rate') : $currency->rate;

                // Get other currencies
                $query = static::query();
                if ($currency->exists) {
                    $query->where('id', '!=', $currency->id);
                }

                if ($oldRateOfNewBase && $oldRateOfNewBase > 0) {
                    // Safely replace comma (e.g. 1,25) to period (e.g. 1.25)
                    $safeRate = sprintf('%F', $oldRateOfNewBase);

                    $query->update([
                        'rate_default' => false,
                        'rate'         => DB::raw("rate / {$safeRate}"),
                    ]);
                } else {
                    // Update all other currencies to rate_default = false
                    $query->update([
                        'rate_default' => false,
                    ]);
                }

                // Set rate of current default currency to 1
                $currency->rate = 1;
            }
        });

        // Set currency is_active = false across all stores if currency is set is_active = false through it's edit page
        static::updated(function (Currency $currency) {
            // Check if is_active was changed and set to false
            if ($currency->wasChanged('is_active') && $currency->is_active === false) {
                // If so, then set all store_currencies.is_active and store_currencies.is_default to false
                StoreCurrency::where('currency_id', $currency->id)->update(['is_active' => false, 'is_default' => false]);
            }
        });
    }
}
