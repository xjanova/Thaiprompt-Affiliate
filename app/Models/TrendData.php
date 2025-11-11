<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendData extends Model
{
    use HasFactory;

    protected $table = 'trend_data';

    protected $fillable = [
        'trend_source_id',
        'title',
        'content',
        'url',
        'author',
        'engagement_count',
        'comment_count',
        'share_count',
        'view_count',
        'viral_score',
        'raw_data',
        'extracted_keywords',
        'published_at',
        'scraped_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'extracted_keywords' => 'array',
        'published_at' => 'datetime',
        'scraped_at' => 'datetime',
        'viral_score' => 'decimal:2',
    ];

    /**
     * Get the source of this trend data
     */
    public function source()
    {
        return $this->belongsTo(TrendSource::class, 'trend_source_id');
    }

    /**
     * Get keywords associated with this trend data
     */
    public function keywords()
    {
        return $this->belongsToMany(TrendKeyword::class, 'trend_data_keyword')
            ->withPivot('weight')
            ->withTimestamps();
    }

    /**
     * Calculate viral score based on engagement metrics
     */
    public function calculateViralScore(): float
    {
        // Weighted formula for viral score
        $engagementWeight = 0.3;
        $commentWeight = 0.25;
        $shareWeight = 0.35;
        $viewWeight = 0.1;

        $score = (
            ($this->engagement_count * $engagementWeight) +
            ($this->comment_count * $commentWeight) +
            ($this->share_count * $shareWeight) +
            ($this->view_count * $viewWeight)
        );

        // Normalize by time factor (newer content gets boost)
        if ($this->published_at) {
            $hoursSincePublished = now()->diffInHours($this->published_at);
            $timeFactor = max(1, 24 / max(1, $hoursSincePublished));
            $score *= $timeFactor;
        }

        return round($score, 2);
    }

    /**
     * Update viral score
     */
    public function updateViralScore(): void
    {
        $this->update(['viral_score' => $this->calculateViralScore()]);
    }

    /**
     * Scope for high viral score
     */
    public function scopeViral($query, $minScore = 100)
    {
        return $query->where('viral_score', '>=', $minScore);
    }

    /**
     * Scope for recent data
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('scraped_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('published_at', [$start, $end]);
    }

    /**
     * Get total engagement
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->engagement_count +
               $this->comment_count +
               $this->share_count;
    }
}
