<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class RankBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'rank_id',
        'bonus_type',
        'name',
        'name_th',
        'description',
        'description_th',
        'amount',
        'percentage',
        'reward_type',
        'reward_details',
        'conditions',
        'is_active',
        'auto_apply',
        'priority',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'auto_apply' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the rank that owns this bonus
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * Get display name based on current locale
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'th' && $this->name_th ? $this->name_th : $this->name;
    }

    /**
     * Get display description based on current locale
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'th' && $this->description_th ? $this->description_th : $this->description;
    }

    /**
     * Scope for active bonuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for auto-apply bonuses
     */
    public function scopeAutoApply($query)
    {
        return $query->where('auto_apply', true);
    }

    /**
     * Scope by bonus type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('bonus_type', $type);
    }

    /**
     * Apply this bonus to a user
     */
    public function applyToUser(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check conditions if any
        if ($this->conditions && !$this->checkConditions($user)) {
            return false;
        }

        switch ($this->bonus_type) {
            case 'one_time':
                return $this->applyOneTimeBonus($user);
            case 'monthly':
                return $this->applyMonthlyBonus($user);
            case 'commission':
                return $this->applyCommissionBonus($user);
            case 'multiplier':
                return $this->applyMultiplierBonus($user);
            default:
                return false;
        }
    }

    /**
     * Check if user meets conditions
     */
    private function checkConditions(User $user): bool
    {
        if (!$this->conditions) {
            return true;
        }

        // Implement condition checking logic based on your requirements
        // This is a placeholder
        return true;
    }

    /**
     * Apply one-time bonus
     * แก้ Bug #17: ใช้ atomic increment แทน manual balance update + ครอบ DB::transaction
     */
    private function applyOneTimeBonus(User $user): bool
    {
        if (!$user->wallet) {
            return false;
        }

        return DB::transaction(function () use ($user) {
            // Log transaction ก่อน เพื่อบันทึก balance_before ที่ถูกต้อง
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $user->wallet->id,
                'type' => 'credit',
                'amount' => $this->amount,
                'balance_after' => $user->wallet->balance + $this->amount,
                'description' => "Rank bonus: {$this->display_name}",
                'status' => 'completed',
            ]);

            // ใช้ atomic increment แทน manual assignment เพื่อป้องกัน race condition
            $user->wallet->increment('balance', $this->amount);

            return true;
        });
    }

    /**
     * Apply monthly bonus
     */
    private function applyMonthlyBonus(User $user): bool
    {
        // Similar to one-time but would be scheduled monthly
        return $this->applyOneTimeBonus($user);
    }

    /**
     * Apply commission bonus
     *
     * ถ้ามี amount > 0: จ่ายเงินเข้ากระเป๋าเป็นโบนัสคอมมิชชัน (เหมือน one_time)
     * ถ้ามีเฉพาะ percentage: เป็น passive bonus ที่ใช้ผ่าน Rank.bonus_multiplier
     */
    private function applyCommissionBonus(User $user): bool
    {
        // ถ้ามี amount ให้จ่ายเป็นเงินโบนัสเข้ากระเป๋า
        if ($this->amount > 0) {
            return $this->applyOneTimeBonus($user);
        }

        // percentage-only: เป็น passive bonus (ใช้ผ่าน Rank.bonus_multiplier ในการคำนวณ commission)
        return true;
    }

    /**
     * Apply multiplier bonus
     *
     * Multiplier bonus เป็น passive bonus ที่ทำงานผ่าน Rank.bonus_multiplier
     * ซึ่งจะถูกใช้อัตโนมัติใน MlmUnilevelService และ InvestmentService
     *
     * ถ้ามี amount > 0: จ่ายเงินเข้ากระเป๋าเพิ่มเติมด้วย
     */
    private function applyMultiplierBonus(User $user): bool
    {
        // ถ้ามี amount ให้จ่ายเป็นเงินโบนัสเข้ากระเป๋าด้วย
        if ($this->amount > 0) {
            return $this->applyOneTimeBonus($user);
        }

        // passive: bonus_multiplier จะถูกใช้ใน commission calculations อัตโนมัติ
        return true;
    }
}
