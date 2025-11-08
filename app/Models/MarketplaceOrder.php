<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id', 'platform_id', 'affiliate_link_id', 'affiliate_user_id',
        'external_order_id', 'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'subtotal', 'shipping_fee', 'tax_amount', 'discount_amount', 'total_amount', 'currency',
        'commission_rate', 'commission_amount', 'platform_fee', 'net_commission',
        'order_status', 'payment_status', 'fulfillment_status',
        'ordered_at', 'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
        'tracking_number', 'shipping_provider',
        'commission_status', 'commission_calculated_at', 'commission_approved_at', 'commission_paid_at',
        'mlm_commission_distributed', 'mlm_distribution_id',
        'order_data', 'last_synced_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_commission' => 'decimal:2',
        'ordered_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'commission_calculated_at' => 'datetime',
        'commission_approved_at' => 'datetime',
        'commission_paid_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'mlm_commission_distributed' => 'boolean',
        'order_data' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'account_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    public function affiliateLink(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAffiliateLink::class, 'affiliate_link_id');
    }

    public function affiliateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'order_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(MarketplaceCommission::class, 'order_id');
    }

    public function scopePending($query)
    {
        return $query->where('commission_status', 'pending');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('affiliate_user_id', $userId);
    }
}
