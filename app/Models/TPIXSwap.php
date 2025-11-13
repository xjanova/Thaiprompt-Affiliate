<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TPIXSwap extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pool_id',
        'token_in_id',
        'token_out_id',
        'amount_in',
        'amount_out',
        'fee',
        'price_impact',
        'price_before',
        'price_after',
        'min_amount_out',
        'slippage_tolerance',
        'tx_hash',
        'status',
        'error_message',
        'block_number',
        'gas_used',
        'gas_price_tpix',
    ];

    protected $casts = [
        'amount_in' => 'decimal:8',
        'amount_out' => 'decimal:8',
        'fee' => 'decimal:8',
        'price_impact' => 'decimal:4',
        'price_before' => 'decimal:8',
        'price_after' => 'decimal:8',
        'min_amount_out' => 'decimal:8',
        'slippage_tolerance' => 'decimal:2',
        'block_number' => 'integer',
        'gas_used' => 'integer',
        'gas_price_tpix' => 'decimal:8',
    ];

    /**
     * Get the user who made the swap
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the liquidity pool
     */
    public function pool(): BelongsTo
    {
        return $this->belongsTo(TPIXLiquidityPool::class, 'pool_id');
    }

    /**
     * Get the input token
     */
    public function tokenIn(): BelongsTo
    {
        return $this->belongsTo(TPIXToken::class, 'token_in_id');
    }

    /**
     * Get the output token
     */
    public function tokenOut(): BelongsTo
    {
        return $this->belongsTo(TPIXToken::class, 'token_out_id');
    }

    /**
     * Get exchange rate
     */
    public function getExchangeRateAttribute(): string
    {
        if (bccomp($this->amount_in, '0', 8) === 0) {
            return '0';
        }
        return bcdiv($this->amount_out, $this->amount_in, 8);
    }

    /**
     * Get total cost including fee
     */
    public function getTotalCostAttribute(): string
    {
        return bcadd($this->amount_in, $this->fee, 8);
    }

    /**
     * Get swap direction as readable string
     */
    public function getSwapDirectionAttribute(): string
    {
        return "{$this->tokenIn->symbol} → {$this->tokenOut->symbol}";
    }

    /**
     * Check if swap was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if swap failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if swap is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope for completed swaps
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed swaps
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending swaps
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for user's swaps
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for swaps in a specific pool
     */
    public function scopeInPool($query, int $poolId)
    {
        return $query->where('pool_id', $poolId);
    }

    /**
     * Scope for recent swaps (last 24h)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Get blockchain explorer URL
     */
    public function getExplorerUrlAttribute(): string
    {
        $explorerUrl = config('tpix.explorer_url', 'http://localhost:4000');
        return "{$explorerUrl}/tx/{$this->tx_hash}";
    }

    /**
     * Calculate slippage percentage
     */
    public function getActualSlippageAttribute(): ?float
    {
        if (!$this->min_amount_out || bccomp($this->min_amount_out, '0', 8) === 0) {
            return null;
        }

        $difference = bcsub($this->min_amount_out, $this->amount_out, 8);
        $slippage = bcdiv(bcmul($difference, '100', 8), $this->min_amount_out, 2);

        return (float) $slippage;
    }
}
