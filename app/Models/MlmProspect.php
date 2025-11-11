<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MlmProspect extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sponsor_mlm_member_id',
        'sponsor_user_id',
        'line_user_id',
        'line_display_name',
        'line_picture_url',
        'referral_token',
        'invitation_url',
        'status',
        'clicked_at',
        'locked_until',
        'is_locked',
        'registered_user_id',
        'registered_at',
        'visit_count',
        'last_visited_at',
        'conversation_step',
        'conversation_data',
        'ip_address',
        'user_agent',
        'utm_params',
        'notes',
        // Conversation tracking
        'conversation_started_at',
        'conversation_updated_at',
        'conversation_timeout_at',
        'conversation_expired',
        'conversation_message_count',
        'conversation_retry_count',
        'conversation_progress_percent',
        'conversation_total_steps',
        'conversation_completed_steps',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'locked_until' => 'datetime',
            'registered_at' => 'datetime',
            'last_visited_at' => 'datetime',
            'is_locked' => 'boolean',
            'conversation_data' => 'array',
            'utm_params' => 'array',
            // Conversation tracking
            'conversation_started_at' => 'datetime',
            'conversation_updated_at' => 'datetime',
            'conversation_timeout_at' => 'datetime',
            'conversation_expired' => 'boolean',
            'conversation_message_count' => 'integer',
            'conversation_retry_count' => 'integer',
            'conversation_progress_percent' => 'integer',
            'conversation_total_steps' => 'integer',
            'conversation_completed_steps' => 'integer',
        ];
    }

    /**
     * Get the sponsor MLM member
     */
    public function sponsorMember()
    {
        return $this->belongsTo(MlmMember::class, 'sponsor_mlm_member_id');
    }

    /**
     * Get the sponsor user
     */
    public function sponsorUser()
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    /**
     * Get the registered user (if completed)
     */
    public function registeredUser()
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    /**
     * Generate unique referral token
     */
    public static function generateReferralToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('referral_token', $token)->exists());

        return $token;
    }

    /**
     * Check if prospect is locked
     */
    public function isLocked(): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        // Check if lock has expired
        if ($this->locked_until && Carbon::now()->isAfter($this->locked_until)) {
            $this->update([
                'is_locked' => false,
                'status' => 'expired',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if prospect is still pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->isLocked();
    }

    /**
     * Check if prospect is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->locked_until && Carbon::now()->isAfter($this->locked_until));
    }

    /**
     * Mark as clicked (first visit)
     */
    public function markAsClicked(?int $lockDurationHours = null): void
    {
        $globalSettings = MlmGlobalSetting::first();
        $lockDuration = $lockDurationHours ?? $globalSettings->prospect_lock_duration_hours ?? 24;

        $this->update([
            'clicked_at' => Carbon::now(),
            'locked_until' => Carbon::now()->addHours($lockDuration),
            'is_locked' => true,
            'visit_count' => $this->visit_count + 1,
            'last_visited_at' => Carbon::now(),
        ]);
    }

    /**
     * Update visit count
     */
    public function recordVisit(): void
    {
        $this->increment('visit_count');
        $this->update(['last_visited_at' => Carbon::now()]);
    }

    /**
     * Update conversation step
     */
    public function updateConversationStep(string $step, ?array $data = null): void
    {
        $updateData = ['conversation_step' => $step];

        if ($data !== null) {
            $currentData = $this->conversation_data ?? [];
            $updateData['conversation_data'] = array_merge($currentData, $data);
        }

        $this->update($updateData);
    }

    /**
     * Get conversation data value
     */
    public function getConversationDataValue(string $key, $default = null)
    {
        return data_get($this->conversation_data, $key, $default);
    }

    /**
     * Mark as registered
     */
    public function markAsRegistered(User $user): void
    {
        $this->update([
            'status' => 'completed',
            'registered_user_id' => $user->id,
            'registered_at' => Carbon::now(),
            'is_locked' => false,
        ]);
    }

    /**
     * Mark as in progress
     */
    public function markAsInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    /**
     * Scope for pending prospects
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for locked prospects
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true)
            ->where('locked_until', '>', Carbon::now());
    }

    /**
     * Scope for expired prospects
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(function ($q2) {
                    $q2->where('is_locked', true)
                        ->where('locked_until', '<=', Carbon::now());
                });
        });
    }

    /**
     * Scope for completed prospects
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for specific sponsor
     */
    public function scopeForSponsor($query, int $sponsorMemberId)
    {
        return $query->where('sponsor_mlm_member_id', $sponsorMemberId);
    }

    /**
     * Get formatted lock remaining time
     */
    public function getLockRemainingTimeAttribute(): ?string
    {
        if (!$this->is_locked || !$this->locked_until) {
            return null;
        }

        if (Carbon::now()->isAfter($this->locked_until)) {
            return 'หมดอายุ';
        }

        return Carbon::now()->diffForHumans($this->locked_until, [
            'parts' => 2,
            'short' => false,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
        ]);
    }
}
