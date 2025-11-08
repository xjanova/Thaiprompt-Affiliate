<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingStrategyReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'strategy_id',
        'purchase_id',
        'rating',
        'review',
        'performance_rating',
        'is_verified_purchase',
        'is_featured',
        'helpful_count',
    ];

    protected $casts = [
        'performance_rating' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function strategy()
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    public function purchase()
    {
        return $this->belongsTo(TradingStrategyPurchase::class, 'purchase_id');
    }
}
