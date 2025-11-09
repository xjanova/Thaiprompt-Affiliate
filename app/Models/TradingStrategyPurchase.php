<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingStrategyPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'strategy_id',
        'price',
        'currency',
        'commission_percentage',
        'commission_amount',
        'seller_payout',
        'status',
        'payment_id',
        'payment_method',
        'purchased_at',
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'seller_payout' => 'decimal:2',
        'purchased_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function strategy()
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }
}
