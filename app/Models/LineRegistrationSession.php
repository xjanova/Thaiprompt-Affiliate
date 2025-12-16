<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * LineRegistrationSession Model
 *
 * จัดการ session ของการสมัครสมาชิกผ่าน LINE OA
 * ใช้สำหรับ tracking สถานะจากหน้าเว็บ
 *
 * @property int $id
 * @property string $session_token
 * @property string|null $referral_code
 * @property int|null $sponsor_user_id
 * @property int|null $sponsor_mlm_member_id
 * @property string|null $line_user_id
 * @property string $status
 * @property int|null $user_id
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $followed_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LineRegistrationSession extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'line_registration_sessions';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'session_token',
        'referral_code',
        'sponsor_user_id',
        'sponsor_mlm_member_id',
        'line_user_id',
        'status',
        'user_id',
        'metadata',
        'ip_address',
        'user_agent',
        'expires_at',
        'followed_at',
        'completed_at',
    ];

    /**
     * Type casting สำหรับ attributes
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'followed_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * สถานะที่เป็นไปได้
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_FOLLOWED = 'followed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * ความสัมพันธ์กับ User (ผู้ที่สมัครเสร็จแล้ว)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ User (Sponsor)
     *
     * @return BelongsTo
     */
    public function sponsorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (Sponsor)
     *
     * @return BelongsTo
     */
    public function sponsorMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'sponsor_mlm_member_id');
    }

    /**
     * สร้าง session ใหม่
     *
     * ⚠️ IMPORTANT: ต้องมี sponsor เสมอ!
     * - ถ้ามี referral_code → ใช้ sponsor จาก code นั้น
     * - ถ้าไม่มี → ใช้ Super Admin (user_id = 1) เป็น default
     *
     * @param string|null $referralCode รหัสผู้แนะนำ (ถ้ามี)
     * @param string|null $ipAddress IP Address
     * @param string|null $userAgent User Agent
     * @return self
     */
    public static function createSession(
        ?string $referralCode = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        // หา sponsor จาก referral code (ถ้ามี)
        $sponsorUserId = null;
        $sponsorMlmMemberId = null;

        if ($referralCode) {
            // ค้นหาจาก MlmMember.member_code
            $member = MlmMember::where('member_code', $referralCode)
                ->where('status', 'active')
                ->first();

            if ($member) {
                $sponsorMlmMemberId = $member->id;
                $sponsorUserId = $member->user_id;
            } else {
                // ค้นหาจาก Affiliate.referral_code
                $affiliate = Affiliate::where('referral_code', $referralCode)->first();
                if ($affiliate) {
                    $sponsorUserId = $affiliate->user_id;
                    // ค้นหา MLM member ของ user นี้
                    $mlmMember = MlmMember::where('user_id', $sponsorUserId)->first();
                    if ($mlmMember) {
                        $sponsorMlmMemberId = $mlmMember->id;
                    }
                }
            }
        }

        // ✅ FALLBACK: ถ้าไม่มี sponsor → ใช้ Super Admin (user_id = 1) เป็น default
        // สำคัญ: ต้องมี sponsor เสมอเพื่อต่อสายงาน MLM
        if (!$sponsorUserId) {
            // ลองหา Super Admin (user_id = 1)
            $superAdmin = User::find(1);
            if ($superAdmin) {
                $sponsorUserId = $superAdmin->id;

                // หา MLM member ของ Super Admin
                $adminMlmMember = MlmMember::where('user_id', $sponsorUserId)
                    ->where('status', 'active')
                    ->first();

                if ($adminMlmMember) {
                    $sponsorMlmMemberId = $adminMlmMember->id;
                }

                \Illuminate\Support\Facades\Log::info('LineRegistrationSession: Using Super Admin as default sponsor', [
                    'sponsor_user_id' => $sponsorUserId,
                    'sponsor_mlm_member_id' => $sponsorMlmMemberId,
                    'ip_address' => $ipAddress,
                ]);
            }
        }

        return self::create([
            'session_token' => self::generateToken(),
            'referral_code' => $referralCode,
            'sponsor_user_id' => $sponsorUserId,
            'sponsor_mlm_member_id' => $sponsorMlmMemberId,
            'status' => self::STATUS_PENDING,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addHours(24), // หมดอายุใน 24 ชั่วโมง
        ]);
    }

    /**
     * สร้าง unique token
     *
     * @return string
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('session_token', $token)->exists());

        return $token;
    }

    /**
     * ค้นหา session จาก token
     *
     * @param string $token
     * @return self|null
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('session_token', $token)->first();
    }

    /**
     * ค้นหา session จาก LINE User ID
     *
     * @param string $lineUserId
     * @return self|null
     */
    public static function findByLineUserId(string $lineUserId): ?self
    {
        return self::where('line_user_id', $lineUserId)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_FOLLOWED,
                self::STATUS_IN_PROGRESS,
            ])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * ค้นหา session ที่ pending อยู่สำหรับ LINE User ID
     *
     * @param string $lineUserId
     * @return self|null
     */
    public static function findPendingByLineUserId(string $lineUserId): ?self
    {
        return self::where('line_user_id', $lineUserId)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * ตรวจสอบว่า session หมดอายุหรือไม่
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->markAsExpired();
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่า session พร้อมสำหรับ polling หรือไม่
     *
     * @return bool
     */
    public function isValidForPolling(): bool
    {
        return !$this->isExpired() && in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_FOLLOWED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Mark session เป็น followed (เพิ่มเพื่อน LINE OA แล้ว)
     *
     * @param string $lineUserId
     * @return void
     */
    public function markAsFollowed(string $lineUserId): void
    {
        $this->update([
            'line_user_id' => $lineUserId,
            'status' => self::STATUS_FOLLOWED,
            'followed_at' => now(),
        ]);
    }

    /**
     * Mark session เป็น in_progress (กำลังสมัคร)
     *
     * @param string|null $lineUserId
     * @return void
     */
    public function markAsInProgress(?string $lineUserId = null): void
    {
        $data = ['status' => self::STATUS_IN_PROGRESS];

        if ($lineUserId) {
            $data['line_user_id'] = $lineUserId;
        }

        $this->update($data);
    }

    /**
     * Mark session เป็น completed (สมัครเสร็จ)
     *
     * @param int $userId User ID ที่สมัครเสร็จ
     * @return void
     */
    public function markAsCompleted(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'user_id' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark session เป็น expired
     *
     * @return void
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Mark session เป็น cancelled
     *
     * @return void
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * อัพเดท metadata
     *
     * @param array $data
     * @return void
     */
    public function updateMetadata(array $data): void
    {
        $metadata = $this->metadata ?? [];
        $metadata = array_merge($metadata, $data);
        $this->update(['metadata' => $metadata]);
    }

    /**
     * ดึงข้อมูลสำหรับ API response
     *
     * @return array
     */
    public function toApiResponse(): array
    {
        $response = [
            'session_token' => $this->session_token,
            'status' => $this->status,
            'has_sponsor' => $this->sponsor_user_id !== null,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'followed_at' => $this->followed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];

        // ถ้ามี sponsor ให้แสดงชื่อ
        if ($this->sponsorUser) {
            $response['sponsor'] = [
                'name' => $this->sponsorUser->name,
                'avatar' => $this->sponsorUser->line_picture_url ?? $this->sponsorUser->avatar_url,
            ];
        }

        // ถ้าสมัครเสร็จแล้ว ให้รวม redirect URL
        if ($this->status === self::STATUS_COMPLETED && $this->user_id) {
            $response['redirect_url'] = route('user.profile.edit');
            $response['user'] = [
                'name' => $this->user?->name,
                'avatar' => $this->user?->line_picture_url ?? $this->user?->avatar_url,
            ];
        }

        return $response;
    }

    /**
     * Scope สำหรับ session ที่ยังไม่หมดอายุ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })->where('status', '!=', self::STATUS_EXPIRED);
    }

    /**
     * Scope สำหรับ session ที่ active อยู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_FOLLOWED,
            self::STATUS_IN_PROGRESS,
        ])->notExpired();
    }
}
