<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductJourney extends Model
{
    use HasFactory;

    protected $table = 'product_journey';

    protected $fillable = [
        'food_product_id',
        'stage',
        'stage_name',
        'sequence_order',
        'location_name',
        'location_coordinates',
        'arrived_at',
        'departed_at',
        'duration_hours',
        'handler_id',
        'handler_name',
        'handler_type',
        'organization',
        'temperature_range',
        'humidity_range',
        'storage_conditions',
        'transport_method',
        'distance_km',
        'fuel_type',
        'stage_carbon_emission',
        'quality_status',
        'notes',
        'photos',
        'documents',
        'blockchain_hash',
        'ipfs_hash',
    ];

    protected $casts = [
        'location_coordinates' => 'array',
        'temperature_range' => 'array',
        'humidity_range' => 'array',
        'photos' => 'array',
        'documents' => 'array',
        'arrived_at' => 'datetime',
        'departed_at' => 'datetime',
        'duration_hours' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'stage_carbon_emission' => 'decimal:4',
    ];

    /**
     * Get the food product this journey belongs to
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Get the handler (user) for this stage
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    /**
     * Get quality checkpoints at this stage
     */
    public function qualityCheckpoints(): HasMany
    {
        return $this->hasMany(QualityCheckpoint::class, 'journey_stage_id');
    }

    /**
     * Get carbon records for this stage
     */
    public function carbonRecords(): HasMany
    {
        return $this->hasMany(CarbonFootprintRecord::class, 'journey_stage_id');
    }

    /**
     * Calculate duration when departed
     */
    protected static function booted()
    {
        static::saving(function ($journey) {
            if ($journey->departed_at && $journey->arrived_at) {
                $journey->duration_hours = $journey->arrived_at
                    ->diffInMinutes($journey->departed_at) / 60;
            }
        });
    }

    /**
     * Check if this stage is completed
     */
    public function isCompleted(): bool
    {
        return !is_null($this->departed_at);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_hours) {
            return 'In progress';
        }

        $hours = floor($this->duration_hours);
        $minutes = ($this->duration_hours - $hours) * 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }

    /**
     * Scope: By stage type
     */
    public function scopeOfStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Scope: Completed stages
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('departed_at');
    }

    /**
     * Scope: In progress stages
     */
    public function scopeInProgress($query)
    {
        return $query->whereNull('departed_at');
    }
}
