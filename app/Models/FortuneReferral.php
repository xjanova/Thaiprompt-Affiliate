<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * FortuneReferral Model
 *
 * ติดตามว่าใครเชิญใครมาใช้บริการดูดวง
 * เมื่อเพื่อนที่ถูกเชิญจ่ายค่าดูดวง → ต่อสายงาน MLM ใต้คนเชิญ
 *
 * @property int $id
 * @property string $referral_token Token สำหรับลิงก์เชิญ
 * @property int $referrer_user_id คนเชิญ (User ID)
 * @property int|null $referrer_mlm_member_id คนเชิญ (MlmMember ID)
 * @property string|null $referred_line_user_id LINE user ID ของเพื่อนที่ถูกเชิญ
 * @property int|null $referred_user_id User ID ของเพื่อนหลังสมัคร
 * @property string $status pending|followed|converted|expired
 * @property \Carbon\Carbon|null $followed_at เวลาที่เพื่อนแอด LINE OA
 * @property \Carbon\Carbon|null $converted_at เวลาที่เพื่อนจ่ายเงิน+สมัคร
 * @property string|null $ip_address IP ตอนกดลิงก์
 * @property string|null $user_agent Browser info
 * @property \Carbon\Carbon|null $expires_at หมดอายุ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneReferral extends Model
{
    protected $table = 'fortune_referrals';

    use \App\Models\Concerns\BelongsToFortunePage;   // 🏬 (2026-08-10) ระบบสาขา

    protected $fillable = [
        'fortune_page_id',   // 🏬 สาขา/เพจต้นทาง
        'referral_token',
        'referrer_user_id',
        'referrer_mlm_member_id',
        'mlm_prospect_id',
        'referred_line_user_id',
        'referred_user_id',
        'status',
        'followed_at',
        'converted_at',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    protected $casts = [
        'followed_at' => 'datetime',
        'converted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ============================
    // Constants
    // ============================

    public const STATUS_PENDING = 'pending';

    public const STATUS_FOLLOWED = 'followed';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_EXPIRED = 'expired';

    // ============================
    // Relationships
    // ============================

    /**
     * คนเชิญ (User)
     */
    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * คนเชิญ (MlmMember)
     */
    public function referrerMlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'referrer_mlm_member_id');
    }

    /**
     * เพื่อนที่ถูกเชิญ (User หลังสมัคร)
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * MlmProspect ที่เชื่อมกัน (แสดงในหน้าผู้มุ่งหวัง)
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(MlmProspect::class, 'mlm_prospect_id');
    }

    // ============================
    // Scopes
    // ============================

    /**
     * เฉพาะ referrals ที่ยังไม่หมดอายุ
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })->where('status', '!=', self::STATUS_EXPIRED);
    }

    /**
     * เฉพาะ referrals ที่รอเพื่อนกดลิงก์
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * เฉพาะ referrals ที่เพื่อนแอด LINE OA แล้ว (รอจ่ายเงิน)
     */
    public function scopeFollowed($query)
    {
        return $query->where('status', self::STATUS_FOLLOWED);
    }

    // ============================
    // Methods
    // ============================

    /**
     * สร้าง token ไม่ซ้ำ 32 ตัวอักษร
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('referral_token', $token)->exists());

        return $token;
    }

    /**
     * หา referral ที่ active จาก LINE user ID ของเพื่อน
     *
     * ใช้ตอนสร้าง MlmMember — เพื่อหาว่าใครเชิญคนนี้มา
     */
    public static function findActiveByLineUserId(string $lineUserId): ?self
    {
        return self::where('referred_line_user_id', $lineUserId)
            ->whereIn('status', [self::STATUS_FOLLOWED, self::STATUS_PENDING])
            ->active()
            ->latest()
            ->first();
    }

    /**
     * หา referral ที่ active จาก platform user ID (LINE / Facebook / อื่นๆ)
     *
     * ค้นหาจาก referred_line_user_id (ใช้เก็บ platform user ID ทุก platform)
     */
    public static function findActiveByPlatformUserId(string $platformUserId): ?self
    {
        return self::where('referred_line_user_id', $platformUserId)
            ->whereIn('status', [self::STATUS_FOLLOWED, self::STATUS_PENDING])
            ->active()
            ->latest()
            ->first();
    }

    /**
     * เมื่อเพื่อนแอด LINE OA — บันทึก LINE user ID
     */
    public function markAsFollowed(string $lineUserId): void
    {
        $this->update([
            'referred_line_user_id' => $lineUserId,
            'status' => self::STATUS_FOLLOWED,
            'followed_at' => now(),
        ]);
    }

    /**
     * เมื่อเพื่อนจ่ายเงิน + สมัครสมาชิกแล้ว
     * อัพเดททั้ง FortuneReferral และ MlmProspect ที่เชื่อมกัน
     */
    public function markAsConverted(User $user): void
    {
        $this->update([
            'referred_user_id' => $user->id,
            'status' => self::STATUS_CONVERTED,
            'converted_at' => now(),
        ]);

        // อัพเดท MlmProspect ที่เชื่อมกันให้เป็น completed ด้วย
        if ($this->mlm_prospect_id) {
            $prospect = MlmProspect::find($this->mlm_prospect_id);
            if ($prospect) {
                $prospect->markAsRegistered($user);
            }
        }
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือไม่
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => self::STATUS_EXPIRED]);

            return true;
        }

        return false;
    }

    /**
     * สร้าง referral record สำหรับคนเชิญ
     *
     * หมดอายุ 24 ชม. (1 วัน) เพื่อจำกัดเวลารับเชิญ
     */
    public static function createForUser(User $user, ?MlmMember $member = null, ?int $prospectId = null): self
    {
        return self::create([
            'referral_token' => self::generateToken(),
            'referrer_user_id' => $user->id,
            'referrer_mlm_member_id' => $member?->id,
            'mlm_prospect_id' => $prospectId,
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * สร้าง LINE deep link URL ที่ฝัง referral token ลงในข้อความ
     *
     * เมื่อเพื่อนกด → LINE เปิดแชท OA พร้อม pre-fill "ref_{token}"
     * เพื่อนกดส่ง → webhook รับ message → parse token → จับคู่ 100%
     */
    public function generateDeepLink(string $botBasicId): string
    {
        // ลบ @ นำหน้าแล้วใส่ %40 แทน (URL encode)
        $basicId = ltrim($botBasicId, '@');

        return 'https://line.me/R/oaMessage/%40'.rawurlencode($basicId).'/?ref_'.$this->referral_token;
    }
}
