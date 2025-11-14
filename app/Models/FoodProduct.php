<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'food_passport_id',
        'farm_name',
        'farm_location',
        'farmer_id',
        'product_type',
        'variety',
        'harvest_date',
        'batch_number',
        'estimated_quantity',
        'unit',
        'certifications',
        'current_stage',
        'blockchain_hash',
        'ipfs_hash',
        'total_carbon_footprint',
        'carbon_score',
        'qr_code_url',
    ];

    protected $casts = [
        'farm_location' => 'array',
        'certifications' => 'array',
        'harvest_date' => 'date',
        'total_carbon_footprint' => 'decimal:4',
    ];

    protected $appends = [
        'passport_url',
        'qr_scan_url',
    ];

    /**
     * Get the product this food passport is linked to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the farmer who owns this product
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * Get all journey stages for this product
     */
    public function journeyStages(): HasMany
    {
        return $this->hasMany(ProductJourney::class)->orderBy('sequence_order');
    }

    /**
     * Get all quality checkpoints for this product
     */
    public function qualityCheckpoints(): HasMany
    {
        return $this->hasMany(QualityCheckpoint::class)->orderBy('checked_at', 'desc');
    }

    /**
     * Get all carbon footprint records for this product
     */
    public function carbonRecords(): HasMany
    {
        return $this->hasMany(CarbonFootprintRecord::class);
    }

    /**
     * Get carbon credits earned from this product
     */
    public function carbonCredits(): HasMany
    {
        return $this->hasMany(CarbonCredit::class);
    }

    /**
     * Get all certifications for this product
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(FoodCertification::class);
    }

    /**
     * Get all consumer scans for this product
     */
    public function consumerScans(): HasMany
    {
        return $this->hasMany(ConsumerScan::class);
    }

    /**
     * Get the current journey stage
     */
    public function currentStage()
    {
        return $this->journeyStages()->latest('sequence_order')->first();
    }

    /**
     * Get the latest quality checkpoint
     */
    public function latestQualityCheck()
    {
        return $this->qualityCheckpoints()->latest('checked_at')->first();
    }

    /**
     * Calculate average quality score
     */
    public function getAverageQualityScoreAttribute(): ?float
    {
        return $this->qualityCheckpoints()
            ->where('overall_result', 'pass')
            ->avg('pass_score');
    }

    /**
     * Get total consumer scans count
     */
    public function getTotalScansAttribute(): int
    {
        return $this->consumerScans()->count();
    }

    /**
     * Get passport public URL
     */
    public function getPassportUrlAttribute(): string
    {
        return route('food-passport.public.show', $this->food_passport_id);
    }

    /**
     * Get QR code scan URL
     */
    public function getQrScanUrlAttribute(): string
    {
        return route('food-passport.scan', $this->food_passport_id);
    }

    /**
     * Check if product has valid certifications
     */
    public function hasValidCertifications(): bool
    {
        return $this->certifications()
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->exists();
    }

    /**
     * Get carbon footprint breakdown by category
     */
    public function getCarbonBreakdown(): array
    {
        return $this->carbonRecords()
            ->selectRaw('emission_category, SUM(total_co2_equivalent) as total')
            ->groupBy('emission_category')
            ->get()
            ->mapWithKeys(fn($item) => [$item->emission_category => $item->total])
            ->toArray();
    }

    /**
     * Scope: Filter by product type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    /**
     * Scope: Filter by farmer
     */
    public function scopeByFarmer($query, int $farmerId)
    {
        return $query->where('farmer_id', $farmerId);
    }

    /**
     * Scope: Filter by current stage
     */
    public function scopeInStage($query, string $stage)
    {
        return $query->where('current_stage', $stage);
    }

    /**
     * Scope: Filter by carbon score
     */
    public function scopeWithCarbonScore($query, string $score)
    {
        return $query->where('carbon_score', $score);
    }

    /**
     * Scope: High quality products (score >= 90)
     */
    public function scopeHighQuality($query)
    {
        return $query->whereHas('qualityCheckpoints', function($q) {
            $q->where('pass_score', '>=', 90);
        });
    }
}
