<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPIXReferralUse extends Model
{
    use HasFactory;

    protected $table = 'tpix_referral_uses';

    protected $fillable = [
        'referral_code_id',
        'referrer_id',
        'referee_id',
        'token_id',
        'verification_code',
        'verification_method',
        'is_verified',
        'verified_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function referralCode()
    {
        return $this->belongsTo(TPIXReferralCode::class, 'referral_code_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function rewards()
    {
        return $this->hasMany(TPIXReferralReward::class, 'referral_use_id');
    }

    /**
     * Scopes
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeByReferrer($query, $userId)
    {
        return $query->where('referrer_id', $userId);
    }

    public function scopeByReferee($query, $userId)
    {
        return $query->where('referee_id', $userId);
    }

    /**
     * Helpers
     */
    public function verify(string $code): bool
    {
        if ($this->is_verified) {
            return false;
        }

        if ($this->verification_code !== $code) {
            return false;
        }

        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return true;
    }

    public function isExpired(): bool
    {
        // Verification codes expire after 24 hours
        return $this->created_at->addHours(24) < now();
    }
}
