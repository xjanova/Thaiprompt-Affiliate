<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * FreshMarketReferral - ระบบแนะนำ/MLM ตลาดสด
 *
 * @property int $id
 * @property int $referrer_user_id
 * @property int|null $referrer_seller_id
 * @property string|null $referred_line_user_id
 * @property int|null $referred_user_id
 * @property string $referral_token
 * @property string $status pending|followed|converted
 * @property float $commission_earned
 * @property \Carbon\Carbon|null $expires_at
 */
class FreshMarketReferral extends Model
{
    protected $table = 'fresh_market_referrals';

    protected $fillable = [
        'referrer_user_id',
        'referrer_seller_id',
        'referred_line_user_id',
        'referred_user_id',
        'referral_token',
        'status',
        'commission_earned',
        'expires_at',
    ];

    protected $casts = [
        'commission_earned' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * ผู้แนะนำ (User)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * ผู้แนะนำ (Seller)
     */
    public function referrerSeller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'referrer_seller_id');
    }

    /**
     * ผู้ที่ถูกแนะนำ (User)
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    // ===== Static Methods =====

    /**
     * สร้าง token ไม่ซ้ำ
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('referral_token', $token)->exists());

        return $token;
    }

    /**
     * สร้าง referral ใหม่สำหรับ seller
     */
    public static function createForSeller(FreshMarketSeller $seller): self
    {
        return self::create([
            'referrer_user_id' => $seller->user_id,
            'referrer_seller_id' => $seller->id,
            'referral_token' => self::generateToken(),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * หา referral ที่ยังใช้งานได้จาก token
     */
    public static function findActiveByToken(string $token): ?self
    {
        return self::where('referral_token', $token)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * หา referral จาก LINE User ID ที่ถูกแนะนำ
     */
    public static function findByReferredLineUser(string $lineUserId): ?self
    {
        return self::where('referred_line_user_id', $lineUserId)
            ->whereIn('status', ['pending', 'followed'])
            ->first();
    }

    // ===== Status Methods =====

    /**
     * ทำเครื่องหมายว่า follow แล้ว
     */
    public function markAsFollowed(string $lineUserId): void
    {
        $this->update([
            'referred_line_user_id' => $lineUserId,
            'status' => 'followed',
        ]);
    }

    /**
     * ทำเครื่องหมายว่าสมัครแล้ว
     */
    public function markAsConverted(User $user): void
    {
        $this->update([
            'referred_user_id' => $user->id,
            'status' => 'converted',
        ]);
    }

    /**
     * หมดอายุหรือยัง
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * สร้าง LINE deep link
     */
    public function generateDeepLink(string $botBasicId): string
    {
        return "https://line.me/R/oaMessage/%40{$botBasicId}/?ref_{$this->referral_token}";
    }
}
