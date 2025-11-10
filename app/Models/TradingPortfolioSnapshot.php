<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingPortfolioSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bot_id',
        'account_id',
        'snapshot_at',
        'total_balance',
        'available_balance',
        'in_orders',
        'currency',
        'total_profit',
        'total_loss',
        'net_profit',
        'roi_percentage',
        'daily_pnl',
        'max_drawdown',
        'sharpe_ratio',
        'sortino_ratio',
        'calmar_ratio',
        'open_positions',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'holdings',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'total_balance' => 'decimal:8',
        'available_balance' => 'decimal:8',
        'in_orders' => 'decimal:8',
        'total_profit' => 'decimal:8',
        'total_loss' => 'decimal:8',
        'net_profit' => 'decimal:8',
        'roi_percentage' => 'decimal:4',
        'daily_pnl' => 'decimal:8',
        'max_drawdown' => 'decimal:4',
        'sharpe_ratio' => 'decimal:4',
        'sortino_ratio' => 'decimal:4',
        'calmar_ratio' => 'decimal:4',
        'win_rate' => 'decimal:2',
        'holdings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function account()
    {
        return $this->belongsTo(TradingAccount::class, 'account_id');
    }
}
