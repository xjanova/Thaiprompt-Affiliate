<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TradingTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'account_id',
        'signal_id',
        'trade_id',
        'exchange_order_id',
        'trading_pair',
        'side',
        'type',
        'position_side',
        'quantity',
        'entry_price',
        'exit_price',
        'average_entry',
        'stop_loss',
        'take_profit',
        'entry_fee',
        'exit_fee',
        'total_fees',
        'fee_currency',
        'gross_profit',
        'net_profit',
        'profit_percentage',
        'roi',
        'risk_amount',
        'reward_amount',
        'risk_reward_ratio',
        'status',
        'opened_at',
        'closed_at',
        'duration_seconds',
        'close_reason',
        'is_paper_trade',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'exit_price' => 'decimal:8',
        'average_entry' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'entry_fee' => 'decimal:8',
        'exit_fee' => 'decimal:8',
        'total_fees' => 'decimal:8',
        'gross_profit' => 'decimal:8',
        'net_profit' => 'decimal:8',
        'profit_percentage' => 'decimal:4',
        'roi' => 'decimal:4',
        'risk_amount' => 'decimal:8',
        'reward_amount' => 'decimal:8',
        'risk_reward_ratio' => 'decimal:4',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_paper_trade' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trade) {
            if (empty($trade->trade_id)) {
                $trade->trade_id = 'TRD-' . Str::upper(Str::random(12));
            }
        });
    }

    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function account()
    {
        return $this->belongsTo(TradingAccount::class, 'account_id');
    }

    public function signal()
    {
        return $this->belongsTo(TradingSignal::class, 'signal_id');
    }

    public function calculateProfit(): void
    {
        if ($this->exit_price && $this->entry_price) {
            $priceDiff = $this->side === 'buy'
                ? $this->exit_price - $this->entry_price
                : $this->entry_price - $this->exit_price;

            $this->gross_profit = $priceDiff * $this->quantity;
            $this->net_profit = $this->gross_profit - $this->total_fees;
            $this->profit_percentage = ($this->net_profit / ($this->entry_price * $this->quantity)) * 100;
            $this->save();
        }
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeProfitable($query)
    {
        return $query->where('net_profit', '>', 0);
    }
}
