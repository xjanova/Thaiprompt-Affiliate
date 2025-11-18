<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRankProgress extends Model
{
    use HasFactory;

    protected $table = 'user_rank_progress';

    protected $fillable = [
        'user_id',
        'target_rank_id',
        'current_points',
        'current_referrals',
        'current_sales',
        'active_referrals',
        'team_sales',
        'progress_percentage',
        'requirements_status',
        'requirements_met',
        'total_requirements',
        'last_calculated_at',
        'estimated_completion_at',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'current_referrals' => 'integer',
        'current_sales' => 'decimal:2',
        'active_referrals' => 'integer',
        'team_sales' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'requirements_status' => 'array',
        'requirements_met' => 'integer',
        'total_requirements' => 'integer',
        'last_calculated_at' => 'datetime',
        'estimated_completion_at' => 'datetime',
    ];

    /**
     * Get the user that owns this progress
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the target rank
     */
    public function targetRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'target_rank_id');
    }

    /**
     * Calculate and update progress
     */
    public function calculate(): void
    {
        $user = $this->user;
        $rank = $this->targetRank;

        if (!$user || !$rank) {
            return;
        }

        $mlmMember = $user->mlmMembers()->first();
        if (!$mlmMember) {
            return;
        }

        // Calculate sales from MLM commissions
        $totalSales = $user->mlmCommissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount');

        // Update current metrics
        $this->current_points = $user->rank_points ?? 0;
        $this->current_referrals = $mlmMember->total_direct_referrals ?? 0;
        $this->current_sales = $totalSales;
        $this->active_referrals = \App\Models\MlmMember::where('unilevel_sponsor_id', $mlmMember->id)
            ->where('is_qualified', true)
            ->count();
        $this->team_sales = $mlmMember->total_team_pv ?? 0;

        // Calculate requirements status
        $requirements = $rank->activeRequirements;
        $this->total_requirements = $requirements->count();
        $this->requirements_met = 0;
        $requirementsStatus = [];

        foreach ($requirements as $requirement) {
            $met = $requirement->checkUserMeetsRequirement($user);
            $currentValue = $requirement->getUserCurrentValue($user);
            $progress = $requirement->getProgressPercentage($user);

            $requirementsStatus[] = [
                'requirement_id' => $requirement->id,
                'name' => $requirement->display_name,
                'type' => $requirement->requirement_type,
                'current_value' => $currentValue,
                'target_value' => $requirement->target_value,
                'progress' => $progress,
                'met' => $met,
            ];

            if ($met) {
                $this->requirements_met++;
            }
        }

        $this->requirements_status = $requirementsStatus;

        // Calculate overall progress percentage
        if ($this->total_requirements > 0) {
            $this->progress_percentage = ($this->requirements_met / $this->total_requirements) * 100;
        } else {
            $this->progress_percentage = 0;
        }

        $this->last_calculated_at = now();

        // Estimate completion date (simplified calculation)
        if ($this->progress_percentage < 100 && $this->progress_percentage > 0) {
            $daysActive = max(1, $user->created_at->diffInDays(now()));
            $progressPerDay = $this->progress_percentage / $daysActive;
            if ($progressPerDay > 0) {
                $daysRemaining = (100 - $this->progress_percentage) / $progressPerDay;
                $this->estimated_completion_at = now()->addDays((int) $daysRemaining);
            }
        }

        $this->save();
    }

    /**
     * Check if user is eligible for promotion
     */
    public function isEligible(): bool
    {
        return $this->progress_percentage >= 100;
    }

    /**
     * Get requirements that are not yet met
     */
    public function getRemainingRequirementsAttribute(): array
    {
        if (!$this->requirements_status) {
            return [];
        }

        return array_filter($this->requirements_status, fn($req) => !$req['met']);
    }

    /**
     * Get requirements that are met
     */
    public function getCompletedRequirementsAttribute(): array
    {
        if (!$this->requirements_status) {
            return [];
        }

        return array_filter($this->requirements_status, fn($req) => $req['met']);
    }
}
