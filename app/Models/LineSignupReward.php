<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineSignupReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'reward_type',
        'reward_name',
        'reward_amount',
        'reward_description',
        'status',
        'granted_at',
        'claimed_at',
        'expires_at',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'granted_at' => 'datetime',
        'claimed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Reward types
     */
    public const TYPE_POINTS = 'points';
    public const TYPE_COINS = 'coins';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_COUPON = 'coupon';

    /**
     * Reward statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_GRANTED = 'granted';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Get the user that owns this reward
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session that owns this reward
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LineSignupSession::class, 'session_id');
    }

    /**
     * Grant the reward
     */
    public function grant(): void
    {
        $this->status = self::STATUS_GRANTED;
        $this->granted_at = now();
        $this->save();
    }

    /**
     * Claim the reward
     */
    public function claim(): void
    {
        $this->status = self::STATUS_CLAIMED;
        $this->claimed_at = now();
        $this->save();
    }

    /**
     * Check if reward is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
