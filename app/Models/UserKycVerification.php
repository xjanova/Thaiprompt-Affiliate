<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKycVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'line_user_id',
        'line_display_name',
        'line_picture_url',
        'verification_status',
        'verification_method',
        'verified_at',
        'rejected_at',
        'rejected_reason',
        'verified_by',
        'verification_data',
        'verification_attempts',
        'last_attempt_at',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'verification_data' => 'array',
        'verification_attempts' => 'integer',
        'last_attempt_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified' && $this->verified_at !== null;
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->verification_status === 'expired';
    }

    /**
     * Verify the KYC
     */
    public function verify(?int $verifiedBy = null): bool
    {
        $this->verification_status = 'verified';
        $this->verified_at = now();
        $this->verified_by = $verifiedBy;

        // Update user's KYC status
        $this->user->update([
            'kyc_verified_at' => now(),
        ]);

        return $this->save();
    }

    /**
     * Reject the KYC
     */
    public function reject(string $reason, ?int $rejectedBy = null): bool
    {
        $this->verification_status = 'rejected';
        $this->rejected_at = now();
        $this->rejected_reason = $reason;
        $this->verified_by = $rejectedBy;

        return $this->save();
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): bool
    {
        $this->verification_status = 'expired';
        return $this->save();
    }

    /**
     * Increment verification attempts
     */
    public function incrementAttempts(): bool
    {
        $this->verification_attempts++;
        $this->last_attempt_at = now();
        return $this->save();
    }

    /**
     * Can retry verification
     */
    public function canRetry(int $maxAttempts = 5): bool
    {
        return $this->verification_attempts < $maxAttempts;
    }

    /**
     * Scope for verified
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope for pending
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Scope for rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    /**
     * Scope by LINE user ID
     */
    public function scopeByLineUserId($query, string $lineUserId)
    {
        return $query->where('line_user_id', $lineUserId);
    }
}
