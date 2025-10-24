<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'status',
        'business_registration_number',
        'business_registration_document',
        'tax_certificate',
        'business_license',
        'id_card_number',
        'id_card_front',
        'id_card_back',
        'selfie_with_id',
        'business_type',
        'business_address',
        'business_phone',
        'business_email',
        'website',
        'bank_statement',
        'bank_book_photo',
        'additional_documents',
        'notes',
        'admin_notes',
        'rejection_reason',
        'submitted_at',
        'verified_at',
        'rejected_at',
        'verified_by',
        'verification_badge',
        'verification_badge_icon',
        'verification_badge_color',
    ];

    protected $casts = [
        'additional_documents' => 'array',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Badge configurations
    public static $badges = [
        'bronze' => [
            'name' => 'Bronze Verified',
            'color' => '#CD7F32',
            'icon' => '🥉',
            'requirements' => 'Basic verification',
        ],
        'silver' => [
            'name' => 'Silver Verified',
            'color' => '#C0C0C0',
            'icon' => '🥈',
            'requirements' => 'Full document verification',
        ],
        'gold' => [
            'name' => 'Gold Verified',
            'color' => '#FFD700',
            'icon' => '🥇',
            'requirements' => 'Premium verification with bank statement',
        ],
        'platinum' => [
            'name' => 'Platinum Verified',
            'color' => '#E5E4E2',
            'icon' => '💎',
            'requirements' => 'Full verification with business license',
        ],
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $admin, string $badge = 'bronze'): bool
    {
        $this->status = 'approved';
        $this->verified_at = now();
        $this->verified_by = $admin->id;
        $this->verification_badge = $badge;

        if (isset(self::$badges[$badge])) {
            $this->verification_badge_icon = self::$badges[$badge]['icon'];
            $this->verification_badge_color = self::$badges[$badge]['color'];
        }

        $saved = $this->save();

        if ($saved) {
            // Update vendor verification status
            $this->vendor->update([
                'is_verified' => true,
                'verification_badge' => $badge,
                'verified_at' => now(),
            ]);
        }

        return $saved;
    }

    public function reject(User $admin, string $reason): bool
    {
        $this->status = 'rejected';
        $this->rejected_at = now();
        $this->verified_by = $admin->id;
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function getBadgeInfo(): ?array
    {
        if (!$this->verification_badge) {
            return null;
        }

        return self::$badges[$this->verification_badge] ?? null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
