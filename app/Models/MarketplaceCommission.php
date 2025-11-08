<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_id', 'affiliate_link_id', 'platform_id',
        'commission_type', 'mlm_level', 'mlm_sponsor_id',
        'order_amount', 'commission_rate', 'commission_amount', 'platform_fee', 'net_commission', 'currency',
        'status', 'approved_at', 'approved_by', 'rejected_at', 'rejected_reason', 'paid_at',
        'wallet_transaction_id', 'notes',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_commission' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    public function affiliateLink(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAffiliateLink::class, 'affiliate_link_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mlm_sponsor_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
