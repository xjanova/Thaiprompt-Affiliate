<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'viral_trend_id',
        'snapshot_at',
        'mention_count',
        'engagement_count',
        'sentiment_score',
        'top_sources',
        'demographics',
        'time_distribution',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'top_sources' => 'array',
        'demographics' => 'array',
        'time_distribution' => 'array',
        'sentiment_score' => 'decimal:2',
    ];

    /**
     * Get the viral trend
     */
    public function viralTrend()
    {
        return $this->belongsTo(ViralTrend::class);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('snapshot_at', [$start, $end]);
    }

    /**
     * Scope for recent snapshots
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('snapshot_at', '>=', now()->subHours($hours));
    }
}
