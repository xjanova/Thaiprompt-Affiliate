<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSlaPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'first_response_time',
        'resolution_time',
        'category_id',
        'priority',
        'business_hours_only',
        'business_hours',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'business_hours_only' => 'boolean',
        'is_active' => 'boolean',
        'first_response_time' => 'integer',
        'resolution_time' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category this policy applies to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Scope: Active policies only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Find matching SLA policy for a ticket
     */
    public static function findMatchingPolicy($categoryId, $priority)
    {
        return self::active()
            ->ordered()
            ->where(function ($query) use ($categoryId, $priority) {
                $query->where(function ($q) use ($categoryId, $priority) {
                    // Exact match: category + priority
                    $q->where('category_id', $categoryId)
                      ->where('priority', $priority);
                })
                ->orWhere(function ($q) use ($categoryId) {
                    // Category only
                    $q->where('category_id', $categoryId)
                      ->whereNull('priority');
                })
                ->orWhere(function ($q) use ($priority) {
                    // Priority only
                    $q->whereNull('category_id')
                      ->where('priority', $priority);
                })
                ->orWhere(function ($q) {
                    // Default policy
                    $q->whereNull('category_id')
                      ->whereNull('priority');
                });
            })
            ->first();
    }

    /**
     * Calculate due date based on SLA time
     */
    public function calculateDueDate($type = 'first_response_time')
    {
        $minutes = $this->{$type};

        if ($this->business_hours_only && $this->business_hours) {
            return $this->calculateBusinessHoursDueDate($minutes);
        }

        return now()->addMinutes($minutes);
    }

    /**
     * Calculate due date considering business hours only
     */
    private function calculateBusinessHoursDueDate($minutes)
    {
        // Default business hours: Mon-Fri, 9:00-18:00
        $businessHours = $this->business_hours ?? [
            'start' => '09:00',
            'end' => '18:00',
            'days' => [1, 2, 3, 4, 5], // Monday to Friday
        ];

        $currentTime = now();
        $remainingMinutes = $minutes;
        $dueDate = $currentTime->copy();

        while ($remainingMinutes > 0) {
            // Move to next business day if needed
            while (!in_array($dueDate->dayOfWeek, $businessHours['days'])) {
                $dueDate->addDay()->setTimeFromTimeString($businessHours['start']);
            }

            // Check if current time is within business hours
            $startTime = $dueDate->copy()->setTimeFromTimeString($businessHours['start']);
            $endTime = $dueDate->copy()->setTimeFromTimeString($businessHours['end']);

            if ($dueDate->lt($startTime)) {
                $dueDate = $startTime;
            }

            if ($dueDate->gte($endTime)) {
                $dueDate->addDay()->setTimeFromTimeString($businessHours['start']);
                continue;
            }

            // Calculate available minutes in this business day
            $availableMinutes = $dueDate->diffInMinutes($endTime);

            if ($remainingMinutes <= $availableMinutes) {
                $dueDate->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutes;
                $dueDate->addDay()->setTimeFromTimeString($businessHours['start']);
            }
        }

        return $dueDate;
    }

    /**
     * Get formatted first response time
     */
    public function getFormattedFirstResponseTimeAttribute()
    {
        return $this->formatMinutes($this->first_response_time);
    }

    /**
     * Get formatted resolution time
     */
    public function getFormattedResolutionTimeAttribute()
    {
        return $this->formatMinutes($this->resolution_time);
    }

    /**
     * Format minutes to human readable format
     */
    private function formatMinutes($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' นาที';
        } elseif ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . ' ชั่วโมง' . ($mins > 0 ? ' ' . $mins . ' นาที' : '');
        } else {
            $days = floor($minutes / 1440);
            $hours = floor(($minutes % 1440) / 60);
            return $days . ' วัน' . ($hours > 0 ? ' ' . $hours . ' ชั่วโมง' : '');
        }
    }
}
