<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoQuest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'type',
        'frequency',
        'target_value',
        'channel_id',
        'video_ids',
        'min_watch_percentage',
        'reward_coins',
        'reward_exp',
        'reward_money',
        'bonus_coins_per_extra',
        'min_level',
        'is_active',
        'priority',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'video_ids' => 'array',
        'min_watch_percentage' => 'integer',
        'reward_coins' => 'decimal:2',
        'reward_exp' => 'integer',
        'reward_money' => 'decimal:2',
        'bonus_coins_per_extra' => 'decimal:2',
        'min_level' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the channel (if specified)
     */
    public function channel()
    {
        return $this->belongsTo(VideoChannel::class, 'channel_id');
    }

    /**
     * Get user progress for this quest
     */
    public function userProgress()
    {
        return $this->hasMany(UserQuestProgress::class, 'quest_id');
    }

    /**
     * Get user's progress
     */
    public function getUserProgress($userId, $periodStart = null)
    {
        $query = $this->userProgress()->where('user_id', $userId);

        if ($periodStart) {
            $query->where('period_start', $periodStart);
        }

        return $query->first();
    }

    /**
     * Check if user is eligible for this quest
     */
    public function isUserEligible($user)
    {
        // Check level requirement
        $userLevel = UserVideoLevel::where('user_id', $user->id)->first();
        if (!$userLevel || $userLevel->currentLevel->level < $this->min_level) {
            return false;
        }

        // Check date range
        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && now()->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope for active quests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for quests by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for quests by frequency
     */
    public function scopeOfFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Get period start and end for this quest
     */
    public function getCurrentPeriod()
    {
        $now = now();

        switch ($this->frequency) {
            case 'daily':
                return [
                    'start' => $now->copy()->startOfDay()->toDateString(),
                    'end' => $now->copy()->endOfDay()->toDateString(),
                ];
            case 'weekly':
                return [
                    'start' => $now->copy()->startOfWeek()->toDateString(),
                    'end' => $now->copy()->endOfWeek()->toDateString(),
                ];
            case 'monthly':
                return [
                    'start' => $now->copy()->startOfMonth()->toDateString(),
                    'end' => $now->copy()->endOfMonth()->toDateString(),
                ];
            default:
                return [
                    'start' => null,
                    'end' => null,
                ];
        }
    }
}
