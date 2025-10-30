<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'is_default',
        'is_active',
        'account_name',
        'account_number',
        'bank_name',
        'bank_code',
        'branch',
        'paypal_email',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'qr_code',
        'metadata',
        'notes',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // When setting a payment method as default, unset other defaults
        static::saving(function ($method) {
            if ($method->is_default) {
                static::where('user_id', $method->user_id)
                    ->where('id', '!=', $method->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user that owns the payment method
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get withdrawal requests using this payment method
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'promptpay' => 'พร้อมเพย์',
            'bank_transfer' => 'โอนผ่านธนาคาร',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'other' => 'อื่นๆ',
            default => $this->type,
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'promptpay' => '💳',
            'bank_transfer' => '🏦',
            'stripe' => '💰',
            'paypal' => '💵',
            'other' => '📝',
            default => '💼',
        };
    }

    /**
     * Get display name (for dropdowns)
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->name];

        if ($this->type === 'promptpay' || $this->type === 'bank_transfer') {
            if ($this->account_number) {
                $parts[] = '(' . $this->getMaskedAccountNumberAttribute() . ')';
            }
        } elseif ($this->type === 'paypal' && $this->paypal_email) {
            $parts[] = '(' . $this->paypal_email . ')';
        }

        return implode(' ', $parts);
    }

    /**
     * Get masked account number
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        if (!$this->account_number) {
            return '';
        }

        $length = strlen($this->account_number);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = substr($this->account_number, -4);
        $masked = str_repeat('*', $length - 4);

        return $masked . $visible;
    }

    /**
     * Check if method is PromptPay
     */
    public function isPromptPay(): bool
    {
        return $this->type === 'promptpay';
    }

    /**
     * Check if method is Bank Transfer
     */
    public function isBankTransfer(): bool
    {
        return $this->type === 'bank_transfer';
    }

    /**
     * Check if method is Stripe
     */
    public function isStripe(): bool
    {
        return $this->type === 'stripe';
    }

    /**
     * Check if method is PayPal
     */
    public function isPayPal(): bool
    {
        return $this->type === 'paypal';
    }

    /**
     * Scope for active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified payment methods
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for default payment method
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
