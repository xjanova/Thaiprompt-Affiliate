<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotAutoContentPost extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'topic',
        'content_prompt',
        'content_guidelines',
        'generated_content',
        'generated_media',
        'hashtags',
        'scheduled_at',
        'frequency',
        'post_days',
        'post_time',
        'target_platforms',
        'platform_specific_config',
        'status',
        'posted_at',
        'post_results',
        'error_message',
        'total_views',
        'total_likes',
        'total_comments',
        'total_shares',
        'auto_regenerate',
    ];

    protected $casts = [
        'content_guidelines' => 'array',
        'generated_media' => 'array',
        'hashtags' => 'array',
        'scheduled_at' => 'datetime',
        'post_days' => 'array',
        'target_platforms' => 'array',
        'platform_specific_config' => 'array',
        'posted_at' => 'datetime',
        'post_results' => 'array',
        'auto_regenerate' => 'boolean',
    ];

    /**
     * Get the bot profile
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Scope: Only scheduled posts
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope: Ready to post
     */
    public function scopeReadyToPost($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Posted
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope: Failed
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark as posted
     */
    public function markAsPosted(array $results = []): void
    {
        $this->update([
            'status' => 'posted',
            'posted_at' => now(),
            'post_results' => $results,
            'error_message' => null,
        ]);

        // Schedule next post if recurring
        if ($this->frequency !== 'once' && $this->auto_regenerate) {
            $this->scheduleNext();
        }
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Schedule next post (for recurring posts)
     */
    protected function scheduleNext(): void
    {
        $nextScheduledAt = match ($this->frequency) {
            'daily' => now()->addDay()->setTimeFromTimeString($this->post_time ?? '09:00'),
            'weekly' => now()->addWeek()->setTimeFromTimeString($this->post_time ?? '09:00'),
            'monthly' => now()->addMonth()->setTimeFromTimeString($this->post_time ?? '09:00'),
            default => null,
        };

        if ($nextScheduledAt) {
            // Create new post for next schedule
            self::create([
                'bot_profile_id' => $this->bot_profile_id,
                'topic' => $this->topic,
                'content_prompt' => $this->content_prompt,
                'content_guidelines' => $this->content_guidelines,
                'scheduled_at' => $nextScheduledAt,
                'frequency' => $this->frequency,
                'post_days' => $this->post_days,
                'post_time' => $this->post_time,
                'target_platforms' => $this->target_platforms,
                'platform_specific_config' => $this->platform_specific_config,
                'auto_regenerate' => $this->auto_regenerate,
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * Update engagement stats
     */
    public function updateEngagementStats(array $stats): void
    {
        $this->update([
            'total_views' => $stats['views'] ?? $this->total_views,
            'total_likes' => $stats['likes'] ?? $this->total_likes,
            'total_comments' => $stats['comments'] ?? $this->total_comments,
            'total_shares' => $stats['shares'] ?? $this->total_shares,
        ]);
    }

    /**
     * Get total engagement
     */
    public function getTotalEngagement(): int
    {
        return $this->total_likes + $this->total_comments + $this->total_shares;
    }

    /**
     * Get engagement rate
     */
    public function getEngagementRate(): float
    {
        if ($this->total_views === 0) {
            return 0;
        }

        return ($this->getTotalEngagement() / $this->total_views) * 100;
    }
}
