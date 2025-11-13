<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonFootprintRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_product_id',
        'journey_stage_id',
        'emission_category',
        'emission_source',
        'activity_data',
        'emission_factor',
        'emission_factor_source',
        'co2_emission',
        'ch4_emission',
        'n2o_emission',
        'total_co2_equivalent',
        'carbon_offset_applied',
        'offset_source',
        'net_emission',
        'calculation_method',
        'calculation_date',
        'verified_by_id',
        'verification_status',
        'supporting_documents',
        'notes',
    ];

    protected $casts = [
        'activity_data' => 'array',
        'supporting_documents' => 'array',
        'emission_factor' => 'decimal:6',
        'co2_emission' => 'decimal:4',
        'ch4_emission' => 'decimal:4',
        'n2o_emission' => 'decimal:4',
        'total_co2_equivalent' => 'decimal:4',
        'carbon_offset_applied' => 'decimal:4',
        'net_emission' => 'decimal:4',
        'calculation_date' => 'datetime',
    ];

    /**
     * Get the food product this record belongs to
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Get the journey stage this emission is from
     */
    public function journeyStage(): BelongsTo
    {
        return $this->belongsTo(ProductJourney::class, 'journey_stage_id');
    }

    /**
     * Get the verifier who verified this record
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    /**
     * Check if verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if pending verification
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Get emission reduction percentage
     */
    public function getReductionPercentageAttribute(): float
    {
        if (!$this->carbon_offset_applied || !$this->total_co2_equivalent) {
            return 0;
        }

        return ($this->carbon_offset_applied / $this->total_co2_equivalent) * 100;
    }

    /**
     * Scope: By category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('emission_category', $category);
    }

    /**
     * Scope: Verified records
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope: Pending verification
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }
}
