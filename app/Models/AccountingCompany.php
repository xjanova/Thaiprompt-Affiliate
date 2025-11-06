<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCompany extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'name_eng',
        'tax_id',
        'branch_code',
        'phone',
        'email',
        'website',
        'address',
        'district',
        'province',
        'postal_code',
        'country',
        'logo',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the company
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get invoices for this company
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(AccountingInvoice::class, 'company_id');
    }

    /**
     * Get expenses for this company
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(AccountingExpense::class, 'company_id');
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->district,
            $this->province,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
