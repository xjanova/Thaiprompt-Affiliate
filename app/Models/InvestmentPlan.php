<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'min_amount',
        'max_amount',
        'roi_rate',
        'roi_frequency',
        'term_days',
        'lock_days',
        'allow_compound',
        'allow_early_withdrawal',
        'early_withdrawal_penalty',
        'referral_bonus_rate',
        'is_active',
        'is_paused',
        'pause_reason',
        'is_featured',
        'allow_coin_investment',
        'coin_to_money_rate',
        'min_coin_amount',
        'max_coin_amount',
        'max_investors',
        'max_positions_per_user',
        'current_investors',
        'sort_order',
        'open_at',
        'close_at',
        'required_rank_id',
        'requires_kyc',
        'features',
        'terms_conditions',
        'risk_warning',
        'risk_level',
        'icon',
        'color',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'roi_rate' => 'decimal:4',
        'early_withdrawal_penalty' => 'decimal:2',
        'referral_bonus_rate' => 'decimal:2',
        'coin_to_money_rate' => 'decimal:4',
        'min_coin_amount' => 'decimal:2',
        'max_coin_amount' => 'decimal:2',
        'allow_compound' => 'boolean',
        'allow_early_withdrawal' => 'boolean',
        'allow_coin_investment' => 'boolean',
        'is_active' => 'boolean',
        'is_paused' => 'boolean',
        'is_featured' => 'boolean',
        'requires_kyc' => 'boolean',
        'features' => 'array',
        'terms_conditions' => 'array',
        'open_at' => 'datetime',
        'close_at' => 'datetime',
    ];

    /**
     * ระดับความเสี่ยง
     */
    const RISK_LEVELS = [
        'low' => ['label' => 'ต่ำ', 'color' => 'green', 'icon' => '🟢'],
        'medium' => ['label' => 'ปานกลาง', 'color' => 'yellow', 'icon' => '🟡'],
        'high' => ['label' => 'สูง', 'color' => 'orange', 'icon' => '🟠'],
        'very_high' => ['label' => 'สูงมาก', 'color' => 'red', 'icon' => '🔴'],
    ];

    /**
     * Get the rank required for this plan
     */
    public function requiredRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'required_rank_id');
    }

    /**
     * Get all staking positions for this plan
     */
    public function stakingPositions(): HasMany
    {
        return $this->hasMany(StakingPosition::class);
    }

    /**
     * Get active staking positions
     */
    public function activePositions(): HasMany
    {
        return $this->hasMany(StakingPosition::class)->where('status', 'active');
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for available plans (not full)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_investors')
                    ->orWhereRaw('current_investors < max_investors');
            });
    }

    /**
     * Check if user can invest in this plan
     */
    public function canUserInvest(User $user): bool
    {
        // Check if plan is active
        if (!$this->is_active) {
            return false;
        }

        // Check if plan is full
        if ($this->max_investors && $this->current_investors >= $this->max_investors) {
            return false;
        }

        // Check KYC requirement
        if ($this->requires_kyc && $user->kyc_status !== 'verified') {
            return false;
        }

        // Check rank requirement
        if ($this->required_rank_id && (!$user->current_rank_id || $user->currentRank->level < $this->requiredRank->level)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate expected ROI for given amount
     */
    public function calculateExpectedROI(float $amount): float
    {
        $dailyRate = $this->roi_rate / 100;

        switch ($this->roi_frequency) {
            case 'daily':
                $distributionCount = $this->term_days;
                break;
            case 'weekly':
                $distributionCount = ceil($this->term_days / 7);
                $dailyRate *= 7;
                break;
            case 'monthly':
                $distributionCount = ceil($this->term_days / 30);
                $dailyRate *= 30;
                break;
            default:
                $distributionCount = $this->term_days;
        }

        return $amount * $dailyRate * $distributionCount;
    }

    /**
     * Calculate daily ROI amount
     */
    public function calculateDailyROI(float $amount): float
    {
        $rate = $this->roi_rate / 100;

        switch ($this->roi_frequency) {
            case 'daily':
                return $amount * $rate;
            case 'weekly':
                return ($amount * $rate * 7) / 7;
            case 'monthly':
                return ($amount * $rate * 30) / 30;
            default:
                return $amount * $rate;
        }
    }

    /**
     * Increment investor count
     */
    public function incrementInvestors(): void
    {
        $this->increment('current_investors');
    }

    /**
     * Decrement investor count
     */
    public function decrementInvestors(): void
    {
        $this->decrement('current_investors');
    }

    /**
     * Get display name based on locale
     */
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' && $this->name_th
            ? $this->name_th
            : $this->name;
    }

    /**
     * Get display description based on locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'th' && $this->description_th
            ? $this->description_th
            : $this->description;
    }

    /**
     * Check if plan has space for new investors
     */
    public function hasSpace(): bool
    {
        if (!$this->max_investors) {
            return true;
        }

        return $this->current_investors < $this->max_investors;
    }

    /**
     * Get total invested amount in this plan
     */
    public function getTotalInvestedAttribute(): float
    {
        return $this->stakingPositions()
            ->whereIn('status', ['active', 'locked', 'matured'])
            ->sum('amount');
    }

    /**
     * Get total ROI paid from this plan
     */
    public function getTotalRoiPaidAttribute(): float
    {
        return $this->stakingPositions()
            ->sum('earned_roi');
    }

    /**
     * ตรวจสอบว่าแผนเปิดรับการลงทุนอยู่หรือไม่
     *
     * @return bool
     *
     * @example
     * if ($plan->isOpen()) {
     *     // แสดงปุ่มลงทุน
     * }
     */
    public function isOpen(): bool
    {
        // ตรวจสอบสถานะ active
        if (!$this->is_active) {
            return false;
        }

        // ตรวจสอบการหยุดชั่วคราว
        if ($this->is_paused) {
            return false;
        }

        // ตรวจสอบช่วงเวลา
        $now = now();

        if ($this->open_at && $now->lt($this->open_at)) {
            return false;
        }

        if ($this->close_at && $now->gt($this->close_at)) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่า user สามารถลงทุนด้วย Coin ได้หรือไม่
     *
     * @param float $coinAmount จำนวน Coin
     * @return array ['can' => bool, 'message' => string|null]
     */
    public function canInvestWithCoin(float $coinAmount): array
    {
        if (!$this->allow_coin_investment) {
            return [
                'can' => false,
                'message' => 'แผนนี้ไม่รองรับการลงทุนด้วย Coin',
            ];
        }

        if ($this->min_coin_amount && $coinAmount < $this->min_coin_amount) {
            return [
                'can' => false,
                'message' => "จำนวน Coin ขั้นต่ำคือ {$this->min_coin_amount} Coin",
            ];
        }

        if ($this->max_coin_amount && $coinAmount > $this->max_coin_amount) {
            return [
                'can' => false,
                'message' => "จำนวน Coin สูงสุดคือ {$this->max_coin_amount} Coin",
            ];
        }

        return ['can' => true, 'message' => null];
    }

    /**
     * แปลง Coin เป็น THB ตามอัตราของแผน
     *
     * @param float $coinAmount จำนวน Coin
     * @return float จำนวน THB
     */
    public function convertCoinToThb(float $coinAmount): float
    {
        $rate = $this->coin_to_money_rate ?? 1.0;

        return $coinAmount * $rate;
    }

    /**
     * ดึงข้อมูลระดับความเสี่ยง
     *
     * @return array
     */
    public function getRiskLevelInfoAttribute(): array
    {
        $level = $this->risk_level ?? 'medium';

        return self::RISK_LEVELS[$level] ?? self::RISK_LEVELS['medium'];
    }

    /**
     * ดึงข้อความเตือนความเสี่ยง
     *
     * @return string
     */
    public function getRiskWarningTextAttribute(): string
    {
        if ($this->risk_warning) {
            return $this->risk_warning;
        }

        return StakingCoinSetting::active()->risk_warning;
    }

    /**
     * ตรวจสอบว่า user ลงทุนเกินจำนวน pot สูงสุดหรือไม่
     *
     * @param User $user
     * @return bool true ถ้ายังลงทุนได้
     */
    public function canUserAddPosition(User $user): bool
    {
        if (!$this->max_positions_per_user) {
            return true; // ไม่จำกัด
        }

        $currentPositions = $this->stakingPositions()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'locked'])
            ->count();

        return $currentPositions < $this->max_positions_per_user;
    }

    /**
     * นับจำนวน pot ที่ user ลงทุนอยู่
     *
     * @param User $user
     * @return int
     */
    public function getUserPositionCount(User $user): int
    {
        return $this->stakingPositions()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'locked'])
            ->count();
    }

    /**
     * Scope สำหรับแผนที่เปิดรับการลงทุน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_paused', false)
                    ->orWhereNull('is_paused');
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('open_at')
                    ->orWhere('open_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('close_at')
                    ->orWhere('close_at', '>=', $now);
            });
    }

    /**
     * Scope สำหรับแผนที่รองรับ Coin
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAcceptsCoin($query)
    {
        return $query->where('allow_coin_investment', true);
    }

    /**
     * หยุดรับการลงทุนชั่วคราว
     *
     * @param string|null $reason เหตุผล
     * @return bool
     */
    public function pause(?string $reason = null): bool
    {
        return $this->update([
            'is_paused' => true,
            'pause_reason' => $reason ?? 'หยุดรับการลงทุนชั่วคราว',
        ]);
    }

    /**
     * เปิดรับการลงทุนอีกครั้ง
     *
     * @return bool
     */
    public function resume(): bool
    {
        return $this->update([
            'is_paused' => false,
            'pause_reason' => null,
        ]);
    }
}
