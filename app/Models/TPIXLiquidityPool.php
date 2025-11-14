<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TPIXLiquidityPool extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'tpix_liquidity_pools';

    protected $fillable = [
        'token_a_id',
        'token_b_id',
        'reserve_a',
        'reserve_b',
        'total_liquidity',
        'fee_percentage',
        'volume_24h',
        'volume_7d',
        'fees_collected',
        'tx_count',
        'is_active',
        'last_trade_at',
    ];

    protected $casts = [
        'reserve_a' => 'decimal:8',
        'reserve_b' => 'decimal:8',
        'total_liquidity' => 'decimal:8',
        'fee_percentage' => 'decimal:3',
        'volume_24h' => 'decimal:8',
        'volume_7d' => 'decimal:8',
        'fees_collected' => 'decimal:8',
        'tx_count' => 'integer',
        'is_active' => 'boolean',
        'last_trade_at' => 'datetime',
    ];

    /**
     * Get token A
     */
    public function tokenA(): BelongsTo
    {
        return $this->belongsTo(TPIXToken::class, 'token_a_id');
    }

    /**
     * Get token B
     */
    public function tokenB(): BelongsTo
    {
        return $this->belongsTo(TPIXToken::class, 'token_b_id');
    }

    /**
     * Get all swaps in this pool
     */
    public function swaps(): HasMany
    {
        return $this->hasMany(TPIXSwap::class, 'pool_id');
    }

    /**
     * Get all liquidity positions in this pool
     */
    public function positions(): HasMany
    {
        return $this->hasMany(TPIXLiquidityPosition::class, 'pool_id');
    }

    /**
     * Get active positions only
     */
    public function activePositions(): HasMany
    {
        return $this->hasMany(TPIXLiquidityPosition::class, 'pool_id')
            ->where('status', 'active');
    }

    /**
     * Calculate current price (tokenB per tokenA)
     */
    public function getCurrentPrice(): string
    {
        if (bccomp($this->reserve_a, '0', 8) === 0) {
            return '0';
        }
        return bcdiv($this->reserve_b, $this->reserve_a, 8);
    }

    /**
     * Calculate pool TVL (Total Value Locked) in TPIX
     */
    public function getTVL(): string
    {
        $valueA = bcmul($this->reserve_a, $this->tokenA->current_price_tpix ?? '0', 8);
        $valueB = bcmul($this->reserve_b, $this->tokenB->current_price_tpix ?? '0', 8);
        return bcadd($valueA, $valueB, 8);
    }

    /**
     * Get pool APY based on 24h fees
     */
    public function getAPY(): float
    {
        $tvl = $this->getTVL();
        if (bccomp($tvl, '0', 8) === 0) {
            return 0;
        }

        // Estimate annual fees from 24h volume
        $dailyFees = bcmul($this->volume_24h, $this->fee_percentage, 8);
        $annualFees = bcmul($dailyFees, '365', 8);
        $apy = bcdiv(bcmul($annualFees, '100', 8), $tvl, 2);

        return (float) $apy;
    }

    /**
     * Scope for active pools
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for pools with liquidity
     */
    public function scopeWithLiquidity($query)
    {
        return $query->where('total_liquidity', '>', 0);
    }

    /**
     * Scope for popular pools (high volume)
     */
    public function scopePopular($query)
    {
        return $query->orderBy('volume_24h', 'desc');
    }

    /**
     * Get pool pair name
     */
    public function getPairNameAttribute(): string
    {
        return "{$this->tokenA->symbol}/{$this->tokenB->symbol}";
    }
}
