<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AccountingContact extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'name',
        'name_eng',
        'tax_id',
        'branch_code',
        'phone',
        'mobile',
        'email',
        'website',
        'address',
        'district',
        'province',
        'postal_code',
        'country',
        'credit_limit',
        'credit_days',
        'payment_terms',
        'note',
        'is_active',
        'flowaccount_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contact) {
            if (empty($contact->code)) {
                $contact->code = self::generateCode($contact->user_id, $contact->type);
            }
        });
    }

    /**
     * Generate unique contact code
     */
    public static function generateCode($userId, $type): string
    {
        $prefix = $type === 'customer' ? 'CUS' : ($type === 'vendor' ? 'VEN' : 'CON');
        $count = self::where('user_id', $userId)->where('type', $type)->count() + 1;

        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the user that owns the contact
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get invoices for this contact
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(AccountingInvoice::class, 'contact_id');
    }

    /**
     * Get expenses for this contact
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(AccountingExpense::class, 'contact_id');
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
     * Check if contact is a customer
     */
    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both']);
    }

    /**
     * Check if contact is a vendor
     */
    public function isVendor(): bool
    {
        return in_array($this->type, ['vendor', 'both']);
    }

    /**
     * Get total outstanding balance
     */
    public function getOutstandingBalanceAttribute(): float
    {
        if ($this->isCustomer()) {
            return $this->invoices()->where('status', '!=', 'paid')->sum('balance');
        }

        if ($this->isVendor()) {
            return $this->expenses()->where('status', '!=', 'paid')->sum('balance');
        }

        return 0;
    }
}
