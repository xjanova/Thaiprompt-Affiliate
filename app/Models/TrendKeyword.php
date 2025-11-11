<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'frequency',
        'trend_score',
        'growth_rate',
        'first_seen_at',
        'last_seen_at',
        'category',
        'related_keywords',
        'metadata',
    ];

    protected $casts = [
        'related_keywords' => 'array',
        'metadata' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'trend_score' => 'decimal:2',
        'growth_rate' => 'decimal:2',
    ];

    /**
     * Get trend data associated with this keyword
     */
    public function trendData()
    {
        return $this->belongsToMany(TrendData::class, 'trend_data_keyword')
            ->withPivot('weight')
            ->withTimestamps();
    }

    /**
     * Get viral trends based on this keyword
     */
    public function viralTrends()
    {
        return $this->hasMany(ViralTrend::class);
    }

    /**
     * Increment frequency
     */
    public function incrementFrequency(int $amount = 1): void
    {
        $this->increment('frequency', $amount);
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Calculate trend score based on frequency and growth
     */
    public function calculateTrendScore(): float
    {
        $hoursSinceFirst = max(1, now()->diffInHours($this->first_seen_at));
        $velocity = $this->frequency / $hoursSinceFirst;

        // Combine frequency, velocity, and growth rate
        $score = (
            ($this->frequency * 0.4) +
            ($velocity * 100 * 0.4) +
            ($this->growth_rate * 0.2)
        );

        return round($score, 2);
    }

    /**
     * Calculate growth rate
     */
    public function calculateGrowthRate(): float
    {
        $recentData = $this->trendData()
            ->where('scraped_at', '>=', now()->subHours(24))
            ->count();

        $olderData = $this->trendData()
            ->whereBetween('scraped_at', [
                now()->subHours(48),
                now()->subHours(24)
            ])
            ->count();

        if ($olderData == 0) {
            return $recentData > 0 ? 100 : 0;
        }

        return round((($recentData - $olderData) / $olderData) * 100, 2);
    }

    /**
     * Update trend metrics
     */
    public function updateTrendMetrics(): void
    {
        $growthRate = $this->calculateGrowthRate();
        $this->update([
            'growth_rate' => $growthRate,
            'trend_score' => $this->calculateTrendScore(),
        ]);
    }

    /**
     * Scope for trending keywords
     */
    public function scopeTrending($query, $minScore = 50)
    {
        return $query->where('trend_score', '>=', $minScore)
            ->orderBy('trend_score', 'desc');
    }

    /**
     * Scope for recent keywords
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('last_seen_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for growing keywords
     */
    public function scopeGrowing($query, $minGrowthRate = 10)
    {
        return $query->where('growth_rate', '>=', $minGrowthRate);
    }

    /**
     * Find or create keyword
     */
    public static function findOrCreateKeyword(string $keyword, ?string $category = null): self
    {
        return self::firstOrCreate(
            ['keyword' => strtolower(trim($keyword))],
            [
                'frequency' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'category' => $category,
            ]
        );
    }
}
