<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineSignupSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_user_id',
        'session_token',
        'current_step',
        'collected_data',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'status',
        'language',
        'user_id',
        // Note: affiliate_id field removed - no longer used after migrating to MLM system
        'started_at',
        'completed_at',
        'last_activity_at',
    ];

    protected $casts = [
        'collected_data' => 'array',
        'otp_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Signup steps in order
     */
    public const STEPS = [
        'welcome',
        'name',
        'email',
        'phone',
        'otp',
        'password',
        'referral',
        'confirmation',
        'completed',
    ];

    /**
     * Session statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Get the user associated with this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the MLM member associated with this session's user
     *
     * Note: This is a helper method that returns the user's MLM member
     */
    public function mlmMember()
    {
        return $this->user?->mlmMembers()->first();
    }

    /**
     * Get step logs for this session
     */
    public function stepLogs(): HasMany
    {
        return $this->hasMany(LineSignupStepLog::class, 'session_id');
    }

    /**
     * Get conversations for this session
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(LineSignupConversation::class, 'session_id');
    }

    /**
     * Get rewards for this session
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(LineSignupReward::class, 'session_id');
    }

    /**
     * Check if OTP is valid
     */
    public function isOtpValid(string $code): bool
    {
        if (!$this->otp_code || !$this->otp_expires_at) {
            return false;
        }

        if ($this->otp_expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->otp_code, $code);
    }

    /**
     * Move to next step
     */
    public function moveToNextStep(): void
    {
        $currentIndex = array_search($this->current_step, self::STEPS);

        if ($currentIndex !== false && $currentIndex < count(self::STEPS) - 1) {
            $this->current_step = self::STEPS[$currentIndex + 1];
            $this->last_activity_at = now();
            $this->save();
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        $currentIndex = array_search($this->current_step, self::STEPS);

        if ($currentIndex === false) {
            return 0;
        }

        return round(($currentIndex / (count(self::STEPS) - 1)) * 100, 2);
    }

    /**
     * Update collected data
     */
    public function updateCollectedData(string $key, $value): void
    {
        $data = $this->collected_data ?? [];
        $data[$key] = $value;
        $this->collected_data = $data;
        $this->last_activity_at = now();
        $this->save();
    }

    /**
     * Get collected data value
     */
    public function getCollectedData(string $key, $default = null)
    {
        return $this->collected_data[$key] ?? $default;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->current_step = 'completed';
        $this->save();
    }

    /**
     * Mark as abandoned
     */
    public function markAsAbandoned(): void
    {
        $this->status = self::STATUS_ABANDONED;
        $this->save();
    }

    /**
     * Check if session is expired (inactive for 24 hours)
     */
    public function isExpired(): bool
    {
        return $this->last_activity_at->diffInHours(now()) >= 24;
    }

    /**
     * Increment OTP attempts
     */
    public function incrementOtpAttempts(): void
    {
        $this->otp_attempts++;
        $this->save();
    }

    /**
     * Reset OTP attempts
     */
    public function resetOtpAttempts(): void
    {
        $this->otp_attempts = 0;
        $this->save();
    }

    /**
     * Get remaining OTP attempts
     */
    public function getRemainingOtpAttempts(): int
    {
        return max(0, 5 - $this->otp_attempts);
    }
}
