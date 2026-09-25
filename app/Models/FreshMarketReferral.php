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
     * ลิงก์แนะนำหลักของร้าน (ใช้ซ้ำได้ไม่จำกัด ไม่หมดอายุ)
     *
     * แถวหลักไม่ผูกกับใคร — ทุกคนที่เข้ามาทางลิงก์นี้จะได้แถวลูกของตัวเองผ่าน claim()
     */
    public static function masterForSeller(FreshMarketSeller $seller): self
    {
        $master = self::where('referrer_seller_id', $seller->id)
            ->where('status', 'pending')
            ->whereNull('referred_line_user_id')
            ->whereNull('referred_user_id')
            ->whereNull('expires_at')
            ->oldest('id')
            ->first();

        return $master ?? self::create([
            'referrer_user_id' => $seller->user_id,
            'referrer_seller_id' => $seller->id,
            'referral_token' => self::generateToken(),
            'status' => 'pending',
            'expires_at' => null,
        ]);
    }

    /**
     * ผูกผู้ถูกแนะนำกับลิงก์ (LINE หรือเว็บ) — คนหนึ่งถูกแนะนำได้ครั้งเดียว ห้ามแนะนำตัวเอง
     *
     * @return self|null แถว referral ของผู้ถูกแนะนำ (null = token ใช้ไม่ได้/แนะนำตัวเอง)
     */
    public static function claim(string $token, ?string $lineUserId = null, ?User $user = null): ?self
    {
        $source = self::where('referral_token', $token)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $source || (! $lineUserId && ! $user)) {
            return null;
        }

        // ห้ามแนะนำตัวเอง
        if ($user && (int) $source->referrer_user_id === (int) $user->id) {
            return null;
        }

        // เคยถูกแนะนำแล้ว → ใช้แถวเดิม (ไม่เปลี่ยนผู้แนะนำ)
        $existing = self::when($user, fn ($q) => $q->where('referred_user_id', $user->id))
            ->when(! $user && $lineUserId, fn ($q) => $q->where('referred_line_user_id', $lineUserId))
            ->first();

        if (! $existing && $user && $lineUserId) {
            $existing = self::where('referred_line_user_id', $lineUserId)->first();
        }

        if ($existing) {
            if ($user && ! $existing->referred_user_id) {
                $existing->update(['referred_user_id' => $user->id]);
            }

            return $existing;
        }

        // ลิงก์เดี่ยวแบบเดิม (ยังไม่มีใครใช้) → ใช้แถวนั้นเลย
        if ($source->expires_at !== null && ! $source->referred_line_user_id && ! $source->referred_user_id) {
            $source->update([
                'referred_line_user_id' => $lineUserId,
                'referred_user_id' => $user?->id,
                'status' => 'followed',
            ]);

            return $source;
        }

        return self::create([
            'referrer_user_id' => $source->referrer_user_id,
            'referrer_seller_id' => $source->referrer_seller_id,
            'referred_line_user_id' => $lineUserId,
            'referred_user_id' => $user?->id,
            'referral_token' => self::generateToken(),
            'status' => 'followed',
            'expires_at' => null,
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
