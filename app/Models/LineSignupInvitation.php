<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LineSignupInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_user_id',
        'invitation_token',
        'line_user_id',
        'referral_code',
        'max_uses',
        'uses_count',
        'status',
        'metadata',
        'expires_at',
        'first_used_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'first_used_at' => 'datetime',
    ];

    /**
     * Invitation statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->invitation_token)) {
                $invitation->invitation_token = Str::random(32);
            }
        });
    }

    /**
     * Get the inviter user
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /**
     * Check if invitation can be used
     */
    public function canBeUsed(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUses(): void
    {
        $this->uses_count++;

        if ($this->uses_count === 1) {
            $this->first_used_at = now();
        }

        if ($this->uses_count >= $this->max_uses) {
            $this->status = self::STATUS_USED;
        }

        $this->save();
    }

    /**
     * Get invitation URL
     */
    public function getUrl(): string
    {
        return url("/line/signup/invitation/{$this->invitation_token}");
    }
}
