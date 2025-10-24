<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'name',
        'rule_type',
        'start_date',
        'end_date',
        'applicable_days',
        'price_adjustment',
        'adjustment_type',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applicable_days' => 'array',
        'price_adjustment' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the room type
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Check if rule applies to a specific date
     */
    public function appliesTo($date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $date = \Carbon\Carbon::parse($date);

        if ($this->rule_type === 'date_range') {
            return $date->between($this->start_date, $this->end_date);
        }

        if ($this->rule_type === 'day_of_week') {
            return in_array($date->dayOfWeek, $this->applicable_days ?? []);
        }

        if ($this->rule_type === 'seasonal') {
            return $date->between($this->start_date, $this->end_date);
        }

        if ($this->rule_type === 'special_event') {
            return $date->between($this->start_date, $this->end_date);
        }

        return false;
    }

    /**
     * Scope: Active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }
}
