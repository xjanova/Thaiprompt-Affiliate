<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingMarketData extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_id',
        'symbol',
        'timeframe',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'quote_volume',
        'trades_count',
        'vwap',
        'indicators',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
        'quote_volume' => 'decimal:8',
        'vwap' => 'decimal:8',
        'indicators' => 'array',
    ];

    public function exchange()
    {
        return $this->belongsTo(TradingExchange::class, 'exchange_id');
    }

    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeForTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }
}
