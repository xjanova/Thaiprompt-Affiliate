<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingBot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'account_id',
        'strategy_id',
        'name',
        'description',
        'trading_pair',
        'base_currency',
        'quote_currency',
        'timeframe',
        'allocated_capital',
        'available_capital',
        'position_size_percentage',
        'use_leverage',
        'leverage',
        'total_profit',
        'total_loss',
        'net_profit',
        'roi_percentage',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'average_win',
        'average_loss',
        'profit_factor',
        'max_consecutive_wins',
        'max_consecutive_losses',
        'status',
        'error_message',
        'started_at',
        'stopped_at',
        'last_trade_at',
        'uptime_seconds',
        'dry_run',
        'auto_restart',
        'notifications_enabled',
        'notification_channels',
        'advanced_settings',
    ];

    protected $casts = [
        'allocated_capital' => 'decimal:8',
        'available_capital' => 'decimal:8',
        'position_size_percentage' => 'decimal:2',
        'use_leverage' => 'boolean',
        'leverage' => 'decimal:2',
        'total_profit' => 'decimal:8',
        'total_loss' => 'decimal:8',
        'net_profit' => 'decimal:8',
        'roi_percentage' => 'decimal:4',
        'win_rate' => 'decimal:2',
        'average_win' => 'decimal:8',
        'average_loss' => 'decimal:8',
        'profit_factor' => 'decimal:4',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_trade_at' => 'datetime',
        'dry_run' => 'boolean',
        'auto_restart' => 'boolean',
        'notifications_enabled' => 'boolean',
        'notification_channels' => 'array',
        'advanced_settings' => 'array',
    ];

    /**
     * Get the user that owns the bot
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription
     */
    public function subscription()
    {
        return $this->belongsTo(TradingBotSubscription::class, 'subscription_id');
    }

    /**
     * Get the trading account
     */
    public function account()
    {
        return $this->belongsTo(TradingAccount::class, 'account_id');
    }

    /**
     * Get the strategy
     */
    public function strategy()
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    /**
     * Get trades
     */
    public function trades()
    {
        return $this->hasMany(TradingTrade::class, 'bot_id');
    }

    /**
     * Get signals
     */
    public function signals()
    {
        return $this->hasMany(TradingSignal::class, 'bot_id');
    }

    /**
     * Get notifications
     */
    public function notifications()
    {
        return $this->hasMany(TradingNotification::class, 'bot_id');
    }

    /**
     * Get portfolio snapshots
     */
    public function portfolioSnapshots()
    {
        return $this->hasMany(TradingPortfolioSnapshot::class, 'bot_id');
    }

    /**
     * Check if bot is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if bot is paper trading
     */
    public function isPaperTrading(): bool
    {
        return $this->dry_run === true;
    }

    /**
     * Start the bot
     */
    public function start(): void
    {
        if (!$this->account->canTrade() && !$this->dry_run) {
            throw new \Exception('Trading account is not enabled for trading');
        }

        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'stopped_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Stop the bot
     */
    public function stop(): void
    {
        $this->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);

        // Calculate uptime
        if ($this->started_at) {
            $this->increment('uptime_seconds', now()->diffInSeconds($this->started_at));
        }
    }

    /**
     * Pause the bot
     */
    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
        ]);
    }

    /**
     * Update performance metrics
     */
    public function updatePerformance(): void
    {
        $trades = $this->trades()->where('status', 'closed')->get();

        $this->update([
            'total_trades' => $trades->count(),
            'winning_trades' => $trades->where('net_profit', '>', 0)->count(),
            'losing_trades' => $trades->where('net_profit', '<', 0)->count(),
            'total_profit' => $trades->where('net_profit', '>', 0)->sum('net_profit'),
            'total_loss' => abs($trades->where('net_profit', '<', 0)->sum('net_profit')),
            'net_profit' => $trades->sum('net_profit'),
            'average_win' => $trades->where('net_profit', '>', 0)->avg('net_profit') ?? 0,
            'average_loss' => abs($trades->where('net_profit', '<', 0)->avg('net_profit') ?? 0),
            'win_rate' => $this->calculateWinRate($trades),
            'profit_factor' => $this->calculateProfitFactor($trades),
            'roi_percentage' => $this->calculateROI(),
        ]);
    }

    /**
     * Calculate win rate
     */
    private function calculateWinRate($trades): float
    {
        $total = $trades->count();
        if ($total == 0) return 0;

        $winning = $trades->where('net_profit', '>', 0)->count();
        return ($winning / $total) * 100;
    }

    /**
     * Calculate profit factor
     */
    private function calculateProfitFactor($trades): float
    {
        $totalProfit = $trades->where('net_profit', '>', 0)->sum('net_profit');
        $totalLoss = abs($trades->where('net_profit', '<', 0)->sum('net_profit'));

        if ($totalLoss == 0) return 0;

        return $totalProfit / $totalLoss;
    }

    /**
     * Calculate ROI
     */
    private function calculateROI(): float
    {
        if ($this->allocated_capital == 0) return 0;

        return ($this->net_profit / $this->allocated_capital) * 100;
    }

    /**
     * Scope for running bots
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for stopped bots
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope for profitable bots
     */
    public function scopeProfitable($query)
    {
        return $query->where('net_profit', '>', 0);
    }
}
