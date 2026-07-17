<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'store_id',
        'customer_group_id',
        'locale',
        'email',
        'addresses',
        'contacts',
        'wishlist',
        'password',
        'email_verified_at',
        'first_name',
        'last_name',
        'phone',
        'company_name',
        'vat_number',
        'gdpr_consent_at',
        'marketing_opt_in',
        'is_approved',
        'is_anonymized',
        'anonymized_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'          => 'hashed',
        'email_verified_at' => 'datetime',
        'gdpr_consent_at'   => 'datetime',
        'anonymized_at'     => 'datetime',
        'addresses'         => 'array',
        'contacts'          => 'array',
        'wishlist'          => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function group(): BelongsTo
    {
        // return $this->belongsTo(CustomerGroup::class);
        // Eloquent guesses foreign key = Str::snake('group') . '_id' = 'group_id', we need to set is explicitly
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    protected function fullName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}"),
        );
    }
    // Anonymize customer data to comply GDPR
    public function anonymize(): void
    {
        $this->update([
            // Remove personal data
            'first_name'        => '--',
            'last_name'         => '--',
            'email'             => '--',
            'contacts'          => '{}',
            'phone'             => null,
            'company_name'      => null,
            'vat_number'        => null,
            'anonymized_at'     => now(),
            'password'          => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40)),
            'remember_token'    => null,

            // Remove notifications if present
            'marketing_opt_in'  => false,
            'is_approved'       => false,
            'is_anonymized'     => true,

            // Keep data integrity
            'email_verified_at' => null,
            'gdpr_consent_at'   => null,
        ]);
    }
}
