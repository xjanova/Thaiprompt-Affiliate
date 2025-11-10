<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingSignal extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'strategy_id',
        'trading_pair',
        'signal_type',
        'timeframe',
        'price',
        'suggested_entry',
        'suggested_stop_loss',
        'suggested_take_profit',
        'suggested_position_size',
        'confidence_score',
        'signal_source',
        'indicators_data',
        'ai_prediction',
        'market_trend',
        'volatility',
        'market_data',
        'status',
        'trade_id',
        'executed_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'suggested_entry' => 'decimal:8',
        'suggested_stop_loss' => 'decimal:8',
        'suggested_take_profit' => 'decimal:8',
        'suggested_position_size' => 'decimal:8',
        'confidence_score' => 'decimal:2',
        'indicators_data' => 'array',
        'ai_prediction' => 'array',
        'volatility' => 'decimal:4',
        'market_data' => 'array',
        'executed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function strategy()
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    public function trade()
    {
        return $this->belongsTo(TradingTrade::class, 'trade_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeHighConfidence($query, $threshold = 70)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }
}
