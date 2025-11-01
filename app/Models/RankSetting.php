<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enable_ranking_system',
        'enable_auto_promotion',
        'enable_manual_approval',
        'enable_rank_purchase',
        'promotion_check_frequency',
        'notify_on_eligible',
        'notify_on_promotion',
        'approval_admins',
        'require_all_approvals',
        'allow_skip_requirements',
        'purchase_discount_percentage',
        'purchase_requires_approval',
        'show_leaderboard',
        'show_rank_badges',
        'show_progress_bar',
        'show_team_ranks',
        'points_per_referral',
        'points_per_sale',
        'points_per_active_month',
        'bonus_calculation_period',
        'accumulate_bonuses',
    ];

    protected $casts = [
        'enable_ranking_system' => 'boolean',
        'enable_auto_promotion' => 'boolean',
        'enable_manual_approval' => 'boolean',
        'enable_rank_purchase' => 'boolean',
        'notify_on_eligible' => 'boolean',
        'notify_on_promotion' => 'boolean',
        'approval_admins' => 'array',
        'require_all_approvals' => 'boolean',
        'allow_skip_requirements' => 'boolean',
        'purchase_requires_approval' => 'boolean',
        'show_leaderboard' => 'boolean',
        'show_rank_badges' => 'boolean',
        'show_progress_bar' => 'boolean',
        'show_team_ranks' => 'boolean',
        'points_per_referral' => 'integer',
        'points_per_sale' => 'decimal:2',
        'points_per_active_month' => 'integer',
        'purchase_discount_percentage' => 'integer',
        'accumulate_bonuses' => 'boolean',
    ];

    /**
     * Get the singleton instance
     */
    public static function get(): self
    {
        return static::firstOrCreate([]);
    }

    /**
     * Update a specific setting
     */
    public static function updateSetting(string $key, $value): bool
    {
        $settings = static::get();
        $settings->$key = $value;
        return $settings->save();
    }

    /**
     * Get a specific setting value
     */
    public static function getSetting(string $key, $default = null)
    {
        $settings = static::get();
        return $settings->$key ?? $default;
    }
}
