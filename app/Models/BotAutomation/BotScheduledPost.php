<?php

namespace App\Models\BotAutomation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotScheduledPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'automation_id',
        'platform_connection_id',
        'status',
        'content',
        'media_urls',
        'hashtags',
        'mentions',
        'location',
        'scheduled_for',
        'published_at',
        'platform_post_id',
        'platform_post_url',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'error_message',
        'retry_count',
        'next_retry_at',
        'platform_response',
        'analytics_data',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'platform_response' => 'array',
        'analytics_data' => 'array',
    ];

    /**
     * Get the automation
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(BotAutomation::class);
    }

    /**
     * Get the platform connection
     */
    public function platformConnection(): BelongsTo
    {
        return $this->belongsTo(BotPlatformConnection::class);
    }

    /**
     * Scope: Pending posts
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Due for publishing
     */
    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope: Published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Failed posts
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark as published
     */
    public function markAsPublished(string $postId, string $postUrl, array $response = []): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'platform_post_id' => $postId,
            'platform_post_url' => $postUrl,
            'platform_response' => $response,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Schedule retry
     */
    public function scheduleRetry(int $delayMinutes = 30): void
    {
        $this->update([
            'status' => 'pending',
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    /**
     * Update analytics
     */
    public function updateAnalytics(array $data): void
    {
        $this->update([
            'likes_count' => $data['likes'] ?? $this->likes_count,
            'comments_count' => $data['comments'] ?? $this->comments_count,
            'shares_count' => $data['shares'] ?? $this->shares_count,
            'views_count' => $data['views'] ?? $this->views_count,
            'analytics_data' => array_merge($this->analytics_data ?? [], $data),
        ]);
    }

    /**
     * Get engagement rate
     */
    public function getEngagementRate(): float
    {
        if ($this->views_count === 0) {
            return 0;
        }

        $totalEngagement = $this->likes_count + $this->comments_count + $this->shares_count;
        return ($totalEngagement / $this->views_count) * 100;
    }
}
