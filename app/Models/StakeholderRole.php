<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StakeholderRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_type',
        'organization_name',
        'license_number',
        'certifications',
        'verified',
        'verified_at',
        'verified_by_id',
        'operating_location',
        'service_areas',
        'can_add_quality_checkpoints',
        'can_issue_certificates',
        'can_verify_carbon_data',
        'reputation_score',
        'total_products_handled',
        'total_quality_checks',
        'status',
    ];

    protected $casts = [
        'certifications' => 'array',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'operating_location' => 'array',
        'service_areas' => 'array',
        'can_add_quality_checkpoints' => 'boolean',
        'can_issue_certificates' => 'boolean',
        'can_verify_carbon_data' => 'boolean',
        'reputation_score' => 'decimal:2',
    ];

    /**
     * Get the user this role belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verifier who verified this role
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    /**
     * Check if role is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->verified;
    }

    /**
     * Check if can perform quality checks
     */
    public function canPerformQualityChecks(): bool
    {
        return $this->can_add_quality_checkpoints
            && $this->isActive();
    }

    /**
     * Check if can issue certifications
     */
    public function canIssueCertifications(): bool
    {
        return $this->can_issue_certificates
            && $this->isActive();
    }

    /**
     * Check if can verify carbon data
     */
    public function canVerifyCarbonData(): bool
    {
        return $this->can_verify_carbon_data
            && $this->isActive();
    }

    /**
     * Increment products handled counter
     */
    public function incrementProductsHandled(int $count = 1): void
    {
        $this->increment('total_products_handled', $count);
    }

    /**
     * Increment quality checks counter
     */
    public function incrementQualityChecks(int $count = 1): void
    {
        $this->increment('total_quality_checks', $count);
    }

    /**
     * Update reputation score
     */
    public function updateReputationScore(float $newRating): void
    {
        // Simple average for now, can be more sophisticated
        $totalRatings = $this->total_quality_checks;
        $currentScore = $this->reputation_score;

        $newScore = (($currentScore * $totalRatings) + $newRating) / ($totalRatings + 1);

        $this->update(['reputation_score' => round($newScore, 2)]);
    }

    /**
     * Get reputation level
     */
    public function getReputationLevelAttribute(): string
    {
        $score = $this->reputation_score;

        return match(true) {
            $score >= 4.5 => 'Excellent',
            $score >= 4.0 => 'Very Good',
            $score >= 3.5 => 'Good',
            $score >= 3.0 => 'Average',
            default => 'Needs Improvement',
        };
    }

    /**
     * Scope: Active roles
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('verified', true);
    }

    /**
     * Scope: By role type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('role_type', $type);
    }

    /**
     * Scope: Verified roles
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope: Pending verification
     */
    public function scopePendingVerification($query)
    {
        return $query->where('verified', false)
            ->where('status', 'active');
    }

    /**
     * Scope: High reputation (4+ stars)
     */
    public function scopeHighReputation($query)
    {
        return $query->where('reputation_score', '>=', 4.0);
    }
}
