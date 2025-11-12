<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotKeywordResponse extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'keywords',
        'match_type',
        'case_sensitive',
        'response_text',
        'response_media',
        'quick_replies',
        'response_variations',
        'priority',
        'conditions',
        'usage_count',
        'last_used_at',
        'is_active',
        'order',
    ];

    protected $casts = [
        'keywords' => 'array',
        'response_media' => 'array',
        'quick_replies' => 'array',
        'response_variations' => 'array',
        'conditions' => 'array',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the bot profile
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Scope: Only active responses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('order', 'asc');
    }

    /**
     * Check if message matches this keyword response
     */
    public function matches(string $message): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check conditions first (time, day, user_type, etc.)
        if (!$this->checkConditions()) {
            return false;
        }

        $searchMessage = $this->case_sensitive ? $message : mb_strtolower($message);

        foreach ($this->keywords as $keyword) {
            $searchKeyword = $this->case_sensitive ? $keyword : mb_strtolower($keyword);

            $isMatch = match ($this->match_type) {
                'exact' => $searchMessage === $searchKeyword,
                'partial' => str_contains($searchMessage, $searchKeyword),
                'regex' => @preg_match($searchKeyword, $searchMessage) === 1,
                default => false,
            };

            if ($isMatch) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if conditions are met
     */
    protected function checkConditions(): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Check time condition
        if (isset($this->conditions['time_range'])) {
            $now = now()->format('H:i');
            $start = $this->conditions['time_range']['start'] ?? '00:00';
            $end = $this->conditions['time_range']['end'] ?? '23:59';

            if ($now < $start || $now > $end) {
                return false;
            }
        }

        // Check day of week condition
        if (isset($this->conditions['days'])) {
            $today = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
            if (!in_array($today, $this->conditions['days'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get response text (with variations support)
     */
    public function getResponse(): string
    {
        // If has variations, randomly pick one
        if (!empty($this->response_variations) && count($this->response_variations) > 0) {
            return $this->response_variations[array_rand($this->response_variations)];
        }

        return $this->response_text;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get full response data including media and quick replies
     */
    public function getFullResponse(): array
    {
        return [
            'text' => $this->getResponse(),
            'media' => $this->response_media,
            'quick_replies' => $this->quick_replies,
            'type' => 'keyword_match',
        ];
    }
}
