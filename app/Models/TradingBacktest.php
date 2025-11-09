<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBacktest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'strategy_id',
        'name',
        'description',
        'trading_pair',
        'exchange_id',
        'timeframe',
        'initial_capital',
        'start_date',
        'end_date',
        'status',
        'progress_percentage',
        'final_balance',
        'total_return',
        'return_percentage',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'profit_factor',
        'sharpe_ratio',
        'sortino_ratio',
        'max_drawdown',
        'average_win',
        'average_loss',
        'largest_win',
        'largest_loss',
        'max_consecutive_wins',
        'max_consecutive_losses',
        'started_at',
        'completed_at',
        'execution_time_seconds',
        'equity_curve',
        'trade_log',
        'statistics',
        'error_message',
    ];

    protected $casts = [
        'initial_capital' => 'decimal:8',
        'start_date' => 'date',
        'end_date' => 'date',
        'final_balance' => 'decimal:8',
        'total_return' => 'decimal:8',
        'return_percentage' => 'decimal:4',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'sharpe_ratio' => 'decimal:4',
        'sortino_ratio' => 'decimal:4',
        'max_drawdown' => 'decimal:4',
        'average_win' => 'decimal:8',
        'average_loss' => 'decimal:8',
        'largest_win' => 'decimal:8',
        'largest_loss' => 'decimal:8',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'equity_curve' => 'array',
        'trade_log' => 'array',
        'statistics' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function strategy()
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    public function exchange()
    {
        return $this->belongsTo(TradingExchange::class, 'exchange_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }
}
