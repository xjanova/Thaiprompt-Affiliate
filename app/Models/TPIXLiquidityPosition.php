<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TPIXLiquidityPosition extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'tpix_liquidity_positions';

    protected $fillable = [
        'user_id',
        'pool_id',
        'token_a_amount',
        'token_b_amount',
        'lp_tokens',
        'initial_value_tpix',
        'current_value_tpix',
        'fees_earned',
        'fees_claimed',
        'last_fee_claim_at',
        'impermanent_loss',
        'impermanent_loss_percentage',
        'add_tx_hash',
        'remove_tx_hash',
        'status',
        'removed_at',
    ];

    protected $casts = [
        'token_a_amount' => 'decimal:8',
        'token_b_amount' => 'decimal:8',
        'lp_tokens' => 'decimal:8',
        'initial_value_tpix' => 'decimal:8',
        'current_value_tpix' => 'decimal:8',
        'fees_earned' => 'decimal:8',
        'fees_claimed' => 'decimal:8',
        'last_fee_claim_at' => 'datetime',
        'impermanent_loss' => 'decimal:8',
        'impermanent_loss_percentage' => 'decimal:4',
        'removed_at' => 'datetime',
    ];

    /**
     * Get the user who owns this position
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
     * Calculate position share percentage
     */
    public function getSharePercentageAttribute(): float
    {
        if (bccomp($this->pool->total_liquidity, '0', 8) === 0) {
            return 0;
        }

        $share = bcdiv(bcmul($this->lp_tokens, '100', 8), $this->pool->total_liquidity, 4);
        return (float) $share;
    }

    /**
     * Get current position value in TPIX
     */
    public function getCurrentValueTPIX(): string
    {
        if (!$this->pool) {
            return '0';
        }

        $pool = $this->pool;
        $sharePercentage = $this->share_percentage;

        if ($sharePercentage === 0) {
            return '0';
        }

        $shareFraction = bcdiv((string) $sharePercentage, '100', 8);

        $tokenAValue = bcmul(
            bcmul($pool->reserve_a, $shareFraction, 8),
            $pool->tokenA->current_price_tpix ?? '0',
            8
        );

        $tokenBValue = bcmul(
            bcmul($pool->reserve_b, $shareFraction, 8),
            $pool->tokenB->current_price_tpix ?? '0',
            8
        );

        return bcadd($tokenAValue, $tokenBValue, 8);
    }

    /**
     * Calculate unclaimed fees
     */
    public function getUnclaimedFeesAttribute(): string
    {
        return bcsub($this->fees_earned, $this->fees_claimed, 8);
    }

    /**
     * Calculate ROI (Return on Investment)
     */
    public function getROIAttribute(): ?float
    {
        if (!$this->initial_value_tpix || bccomp($this->initial_value_tpix, '0', 8) === 0) {
            return null;
        }

        $currentValue = $this->getCurrentValueTPIX();
        $totalValue = bcadd($currentValue, $this->fees_earned, 8);

        $profit = bcsub($totalValue, $this->initial_value_tpix, 8);
        $roi = bcdiv(bcmul($profit, '100', 8), $this->initial_value_tpix, 2);

        return (float) $roi;
    }

    /**
     * Calculate impermanent loss
     */
    public function calculateImpermanentLoss(): array
    {
        if (!$this->pool || !$this->initial_value_tpix) {
            return [
                'loss' => '0',
                'loss_percentage' => 0,
            ];
        }

        // Calculate current value if held individually
        $initialPriceRatio = bcdiv($this->token_b_amount, $this->token_a_amount, 8);
        $currentPriceRatio = $this->pool->getCurrentPrice();

        if (bccomp($initialPriceRatio, '0', 8) === 0) {
            return ['loss' => '0', 'loss_percentage' => 0];
        }

        // Price ratio change
        $priceRatioChange = bcdiv($currentPriceRatio, $initialPriceRatio, 8);

        // Impermanent loss formula: IL = 2 * sqrt(priceRatioChange) / (1 + priceRatioChange) - 1
        $sqrt = $this->bcsqrt($priceRatioChange);
        $numerator = bcmul('2', $sqrt, 8);
        $denominator = bcadd('1', $priceRatioChange, 8);
        $ratio = bcdiv($numerator, $denominator, 8);
        $ilMultiplier = bcsub($ratio, '1', 8);

        $loss = bcmul($this->initial_value_tpix, $ilMultiplier, 8);
        $lossPercentage = bcmul($ilMultiplier, '100', 2);

        return [
            'loss' => $loss,
            'loss_percentage' => (float) $lossPercentage,
        ];
    }

    /**
     * Square root helper for BC Math
     */
    protected function bcsqrt(string $number, int $scale = 8): string
    {
        $guess = bcdiv($number, '2', $scale);

        for ($i = 0; $i < 10; $i++) {
            $guess = bcdiv(
                bcadd($guess, bcdiv($number, $guess, $scale), $scale),
                '2',
                $scale
            );
        }

        return $guess;
    }

    /**
     * Check if position is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if position is removed
     */
    public function isRemoved(): bool
    {
        return $this->status === 'removed';
    }

    /**
     * Scope for active positions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for removed positions
     */
    public function scopeRemoved($query)
    {
        return $query->where('status', 'removed');
    }

    /**
     * Scope for user's positions
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for positions in a specific pool
     */
    public function scopeInPool($query, int $poolId)
    {
        return $query->where('pool_id', $poolId);
    }

    /**
     * Get blockchain explorer URL for add transaction
     */
    public function getAddExplorerUrlAttribute(): ?string
    {
        if (!$this->add_tx_hash) {
            return null;
        }

        $explorerUrl = config('tpix.explorer_url', 'http://localhost:4000');
        return "{$explorerUrl}/tx/{$this->add_tx_hash}";
    }

    /**
     * Get blockchain explorer URL for remove transaction
     */
    public function getRemoveExplorerUrlAttribute(): ?string
    {
        if (!$this->remove_tx_hash) {
            return null;
        }

        $explorerUrl = config('tpix.explorer_url', 'http://localhost:4000');
        return "{$explorerUrl}/tx/{$this->remove_tx_hash}";
    }

    /**
     * Get position duration in days
     */
    public function getDurationDaysAttribute(): int
    {
        $endDate = $this->removed_at ?? now();
        return $this->created_at->diffInDays($endDate);
    }
}
