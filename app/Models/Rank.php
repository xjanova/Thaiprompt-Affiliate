<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'level',
        'icon',
        'color',
        'badge_icon',
        'stars',
        'commission_rate',
        'bonus_multiplier',
        'min_points',
        'min_referrals',
        'min_sales',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'bonus_multiplier' => 'decimal:2',
        'min_sales' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'level' => 'integer',
        'min_points' => 'integer',
        'min_referrals' => 'integer',
        'stars' => 'integer',
    ];

    /**
     * Get requirements for this rank
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RankRequirement::class);
    }

    /**
     * Get active requirements
     */
    public function activeRequirements(): HasMany
    {
        return $this->requirements()->where('is_active', true);
    }

    /**
     * Get bonuses for this rank
     */
    public function bonuses(): HasMany
    {
        return $this->hasMany(RankBonus::class);
    }

    /**
     * Get active bonuses
     */
    public function activeBonuses(): HasMany
    {
        return $this->bonuses()->where('is_active', true);
    }

    /**
     * Get users with this rank
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_rank_id');
    }

    /**
     * Get affiliates with this rank
     */
    public function affiliates(): HasMany
    {
        return $this->hasMany(Affiliate::class, 'rank_id');
    }

    /**
     * Get promotions to this rank
     */
    public function promotionsTo(): HasMany
    {
        return $this->hasMany(RankPromotion::class, 'to_rank_id');
    }

    /**
     * Get promotions from this rank
     */
    public function promotionsFrom(): HasMany
    {
        return $this->hasMany(RankPromotion::class, 'from_rank_id');
    }

    /**
     * Get user progress tracking for this rank
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserRankProgress::class, 'target_rank_id');
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
     * Get icon URL
     */
    public function getIconUrlAttribute(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        if (str_starts_with($this->icon, 'http')) {
            return $this->icon;
        }

        return asset($this->icon);
    }

    /**
     * Get badge icon URL
     */
    public function getBadgeIconUrlAttribute(): ?string
    {
        if (!$this->badge_icon) {
            return null;
        }

        if (str_starts_with($this->badge_icon, 'http')) {
            return $this->badge_icon;
        }

        return asset($this->badge_icon);
    }

    /**
     * Scope to get active ranks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level
     */
    public function scopeByLevel($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Get next rank
     */
    public function getNextRankAttribute(): ?Rank
    {
        return static::where('level', '>', $this->level)
            ->where('is_active', true)
            ->orderBy('level')
            ->first();
    }

    /**
     * Get previous rank
     */
    public function getPreviousRankAttribute(): ?Rank
    {
        return static::where('level', '<', $this->level)
            ->where('is_active', true)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Check if user meets requirements for this rank
     */
    public function checkUserEligibility(User $user): array
    {
        $requirements = $this->activeRequirements;
        $results = [];
        $metCount = 0;

        foreach ($requirements as $requirement) {
            $met = $requirement->checkUserMeetsRequirement($user);
            $results[] = [
                'requirement' => $requirement,
                'met' => $met,
                'current_value' => $requirement->getUserCurrentValue($user),
                'target_value' => $requirement->target_value,
            ];

            if ($met) {
                $metCount++;
            }
        }

        return [
            'eligible' => $metCount === $requirements->count(),
            'met_count' => $metCount,
            'total_count' => $requirements->count(),
            'requirements' => $results,
        ];
    }
}
