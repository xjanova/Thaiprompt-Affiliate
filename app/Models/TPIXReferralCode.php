<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPIXReferralCode extends Model
{
    use HasFactory;

    protected $table = 'tpix_referral_codes';

    protected $fillable = [
        'user_id',
        'code',
        'reward_percentage',
        'referrer_percentage',
        'max_uses',
        'current_uses',
        'expires_at',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referralUses()
    {
        return $this->hasMany(TPIXReferralUse::class, 'referral_code_id');
    }

    public function rewards()
    {
        return $this->hasMany(TPIXReferralReward::class, 'referral_code_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function($q) {
                $q->whereNull('max_uses')
                  ->orWhereRaw('current_uses < max_uses');
            });
    }

    /**
     * Helpers
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function canBeUsed(): bool
    {
        return $this->isValid();
    }

    public function incrementUses()
    {
        $this->increment('current_uses');
    }
}
