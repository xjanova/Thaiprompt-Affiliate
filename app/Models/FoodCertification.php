<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_product_id',
        'certification_type',
        'certification_name',
        'certifying_body',
        'certificate_number',
        'issued_date',
        'expiry_date',
        'status',
        'scope',
        'standards_version',
        'certificate_url',
        'audit_report_url',
        'verification_code',
        'qr_code_url',
        'blockchain_hash',
        'ipfs_hash',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the food product this certification belongs to
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Check if certification is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expiry_date > now();
    }

    /**
     * Check if certification is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date <= now();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * Check if expiring soon (within 30 days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->daysUntilExpiry <= $days && $this->daysUntilExpiry > 0;
    }

    /**
     * Get public verification URL
     */
    public function getVerificationUrlAttribute(): string
    {
        return route('food-passport.certifications.verify', $this->certificate_number);
    }

    /**
     * Scope: Active certifications
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('certification_type', $type);
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->where('status', 'active');
    }
}
