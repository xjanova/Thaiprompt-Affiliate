<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     */
    private function applyOneTimeBonus(User $user): bool
    {
        if (!$user->wallet) {
            return false;
        }

        // Add to wallet
        $user->wallet->balance += $this->amount;
        $user->wallet->save();

        // Log transaction
        WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'credit',
            'amount' => $this->amount,
            'description' => "Rank bonus: {$this->display_name}",
            'status' => 'completed',
        ]);

        return true;
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
     */
    private function applyCommissionBonus(User $user): bool
    {
        // This would be applied automatically when calculating commissions
        // Just return true to indicate it's set up
        return true;
    }

    /**
     * Apply multiplier bonus
     */
    private function applyMultiplierBonus(User $user): bool
    {
        // This would be applied automatically when calculating earnings
        // Just return true to indicate it's set up
        return true;
    }
}
