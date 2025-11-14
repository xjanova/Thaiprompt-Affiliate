<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_product_id',
        'journey_stage_id',
        'checkpoint_type',
        'checkpoint_name',
        'checked_at',
        'inspector_id',
        'inspector_name',
        'inspector_license',
        'organization',
        'test_parameters',
        'overall_result',
        'pass_score',
        'standards_applied',
        'certification_issued',
        'certificate_number',
        'certificate_expiry',
        'test_report_url',
        'photos',
        'notes',
        'corrective_actions',
        'retest_required',
        'retest_checkpoint_id',
        'blockchain_hash',
        'ipfs_hash',
    ];

    protected $casts = [
        'test_parameters' => 'array',
        'standards_applied' => 'array',
        'photos' => 'array',
        'checked_at' => 'datetime',
        'certificate_expiry' => 'date',
        'pass_score' => 'decimal:2',
        'certification_issued' => 'boolean',
        'retest_required' => 'boolean',
    ];

    /**
     * Get the food product this checkpoint belongs to
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Get the journey stage this checkpoint is for
     */
    public function journeyStage(): BelongsTo
    {
        return $this->belongsTo(ProductJourney::class, 'journey_stage_id');
    }

    /**
     * Get the inspector who performed this check
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Get the retest checkpoint if this requires retest
     */
    public function retestCheckpoint(): BelongsTo
    {
        return $this->belongsTo(QualityCheckpoint::class, 'retest_checkpoint_id');
    }

    /**
     * Check if checkpoint passed
     */
    public function passed(): bool
    {
        return $this->overall_result === 'pass';
    }

    /**
     * Check if checkpoint failed
     */
    public function failed(): bool
    {
        return $this->overall_result === 'fail';
    }

    /**
     * Get pass percentage
     */
    public function getPassPercentageAttribute(): float
    {
        if (!$this->test_parameters) {
            return 0;
        }

        $total = count($this->test_parameters);
        $passed = collect($this->test_parameters)
            ->filter(fn($param) => $param['status'] === 'pass')
            ->count();

        return ($passed / $total) * 100;
    }

    /**
     * Get color code based on result
     */
    public function getResultColorAttribute(): string
    {
        return match($this->overall_result) {
            'pass' => 'green',
            'conditional_pass' => 'yellow',
            'fail' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope: Passed checkpoints
     */
    public function scopePassed($query)
    {
        return $query->where('overall_result', 'pass');
    }

    /**
     * Scope: Failed checkpoints
     */
    public function scopeFailed($query)
    {
        return $query->where('overall_result', 'fail');
    }

    /**
     * Scope: By checkpoint type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('checkpoint_type', $type);
    }

    /**
     * Scope: Requires retest
     */
    public function scopeRequiresRetest($query)
    {
        return $query->where('retest_required', true);
    }
}
