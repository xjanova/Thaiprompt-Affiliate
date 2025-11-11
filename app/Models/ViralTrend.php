<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViralTrend extends Model
{
    use HasFactory;

    protected $fillable = [
        'trend_keyword_id',
        'title',
        'description',
        'viral_score',
        'total_mentions',
        'total_engagement',
        'velocity',
        'status',
        'peak_at',
        'started_at',
        'sources',
        'analytics',
        'generated_content',
    ];

    protected $casts = [
        'sources' => 'array',
        'analytics' => 'array',
        'generated_content' => 'array',
        'peak_at' => 'datetime',
        'started_at' => 'datetime',
        'viral_score' => 'decimal:2',
        'velocity' => 'decimal:2',
    ];

    /**
     * Get the keyword for this viral trend
     */
    public function keyword()
    {
        return $this->belongsTo(TrendKeyword::class, 'trend_keyword_id');
    }

    /**
     * Get analytics snapshots
     */
    public function analyticsSnapshots()
    {
        return $this->hasMany(TrendAnalytic::class);
    }

    /**
     * Calculate velocity (mentions per hour)
     */
    public function calculateVelocity(): float
    {
        $hoursSinceStart = max(1, now()->diffInHours($this->started_at));
        return round($this->total_mentions / $hoursSinceStart, 2);
    }

    /**
     * Update trend status based on metrics
     */
    public function updateStatus(): void
    {
        $currentVelocity = $this->calculateVelocity();

        // Get previous velocity from analytics
        $previousSnapshot = $this->analyticsSnapshots()
            ->orderBy('snapshot_at', 'desc')
            ->first();

        if (!$previousSnapshot) {
            $status = 'rising';
        } else {
            $previousVelocity = $previousSnapshot->mention_count /
                max(1, now()->diffInHours($previousSnapshot->snapshot_at));

            if ($currentVelocity > $previousVelocity * 1.2) {
                $status = 'rising';
            } elseif ($currentVelocity < $previousVelocity * 0.8) {
                $status = 'declining';
            } elseif (!$this->peak_at && $previousVelocity > $currentVelocity) {
                $status = 'peaked';
                $this->peak_at = now();
            } else {
                $status = 'stable';
            }
        }

        $this->update([
            'status' => $status,
            'velocity' => $currentVelocity,
            'peak_at' => $this->peak_at,
        ]);
    }

    /**
     * Add analytics snapshot
     */
    public function addSnapshot(array $data): TrendAnalytic
    {
        return $this->analyticsSnapshots()->create([
            'snapshot_at' => now(),
            'mention_count' => $data['mention_count'] ?? 0,
            'engagement_count' => $data['engagement_count'] ?? 0,
            'sentiment_score' => $data['sentiment_score'] ?? 0,
            'top_sources' => $data['top_sources'] ?? null,
            'demographics' => $data['demographics'] ?? null,
            'time_distribution' => $data['time_distribution'] ?? null,
        ]);
    }

    /**
     * Store AI generated content
     */
    public function storeGeneratedContent(array $content): void
    {
        $this->update([
            'generated_content' => array_merge(
                $this->generated_content ?? [],
                [$content]
            ),
        ]);
    }

    /**
     * Scope for active trends
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['rising', 'peaked', 'stable'])
            ->orderBy('viral_score', 'desc');
    }

    /**
     * Scope for rising trends
     */
    public function scopeRising($query)
    {
        return $query->where('status', 'rising')
            ->orderBy('velocity', 'desc');
    }

    /**
     * Scope for recent trends
     */
    public function scopeRecent($query, $hours = 48)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Get trend age in hours
     */
    public function getAgeInHoursAttribute(): int
    {
        return now()->diffInHours($this->started_at);
    }

    /**
     * Check if trend is fresh (< 24 hours)
     */
    public function isFresh(): bool
    {
        return $this->age_in_hours < 24;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'rising' => 'green',
            'peaked' => 'yellow',
            'stable' => 'blue',
            'declining' => 'red',
            default => 'gray',
        };
    }
}
