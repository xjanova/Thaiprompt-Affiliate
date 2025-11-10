<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'period_type',
        'period_value',
        'request_count',
        'limit',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'limit' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    /**
     * Get the API key
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Increment request count
     */
    public function incrementCount()
    {
        $this->increment('request_count');
    }

    /**
     * Check if limit exceeded
     */
    public function isLimitExceeded()
    {
        if (!$this->limit) {
            return false;
        }

        return $this->request_count >= $this->limit;
    }

    /**
     * Get or create quota for period
     */
    public static function getOrCreateForPeriod($apiKeyId, $periodType, $limit = null)
    {
        $periodValue = static::getPeriodValue($periodType);
        $periodDates = static::getPeriodDates($periodType);

        return static::firstOrCreate(
            [
                'api_key_id' => $apiKeyId,
                'period_type' => $periodType,
                'period_value' => $periodValue,
            ],
            [
                'request_count' => 0,
                'limit' => $limit,
                'period_start' => $periodDates['start'],
                'period_end' => $periodDates['end'],
            ]
        );
    }

    /**
     * Get period value based on type
     */
    protected static function getPeriodValue($periodType)
    {
        $now = now();

        return match ($periodType) {
            'minute' => $now->format('Y-m-d-H-i'),
            'hour' => $now->format('Y-m-d-H'),
            'day' => $now->format('Y-m-d'),
            'month' => $now->format('Y-m'),
            default => $now->format('Y-m-d'),
        };
    }

    /**
     * Get period start and end dates
     */
    protected static function getPeriodDates($periodType)
    {
        $now = now();

        return match ($periodType) {
            'minute' => [
                'start' => $now->copy()->startOfMinute(),
                'end' => $now->copy()->endOfMinute(),
            ],
            'hour' => [
                'start' => $now->copy()->startOfHour(),
                'end' => $now->copy()->endOfHour(),
            ],
            'day' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Clean up old quota records
     */
    public static function cleanup($days = 30)
    {
        static::where('period_end', '<', now()->subDays($days))->delete();
    }

    /**
     * Get remaining requests
     */
    public function getRemainingAttribute()
    {
        if (!$this->limit) {
            return null;
        }

        return max(0, $this->limit - $this->request_count);
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute()
    {
        if (!$this->limit) {
            return 0;
        }

        return min(100, round(($this->request_count / $this->limit) * 100, 2));
    }
}
