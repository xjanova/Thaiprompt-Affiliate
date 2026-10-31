<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneUserBan Model
 *
 * เก็บข้อมูลผู้ถูกแบนและผลการบล็อกจากเพจ Facebook จริง
 *
 * @property int $id
 * @property string $platform 'facebook' หรือ 'line'
 * @property string $platform_user_id PSID หรือ LINE userId
 * @property string|null $display_name
 * @property string|null $reason
 * @property Carbon|null $banned_until NULL = ถาวร
 * @property int|null $banned_by user_id ของแอดมิน
 * @property Carbon|null $last_notified_at
 * @property int $notify_count
 * @property int $attempt_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class FortuneUserBan extends Model
{
    /** ข้อความใน page_block_error เมื่อ Facebook จำกัดการบล็อกของเพจ (code 368) */
    public const PAGE_LIMITED_ERROR = 'code=368 ';

    /** พักกี่ชั่วโมงเมื่อโดน code 368 — ยิงซ้ำถี่จะยิ่งถูกมองว่าเป็นการใช้งานผิดปกติ */
    public const PAGE_LIMITED_RETRY_HOURS = 24;

    protected $table = 'fortune_user_bans';

    use \App\Models\Concerns\BelongsToFortunePage;   // 🏬 (2026-08-10) ระบบสาขา

    protected $fillable = [
        'fortune_page_id',   // 🏬 สาขาที่สั่งแบน (เฟสนี้แบนแยกรายสาขา)
        'platform',
        'platform_user_id',
        'display_name',
        'reason',
        'banned_until',
        'banned_by',
        'last_notified_at',
        'notify_count',
        'attempt_count',
        'page_block_status',
        'page_block_error',
        'page_block_attempted_at',
    ];

    protected $casts = [
        'banned_until' => 'datetime',
        'last_notified_at' => 'datetime',
        'notify_count' => 'integer',
        'attempt_count' => 'integer',
        'page_block_attempted_at' => 'datetime',
    ];

    /** ตรวจว่าแบนครบแล้ว รวมการบล็อกบนเพจสำหรับ Facebook */
    public function isFullyEnforced(): bool
    {
        return $this->platform !== 'facebook' || $this->page_block_status === 'blocked';
    }

    /** ตรวจว่าการบล็อกครั้งล่าสุดล้มเพราะ Facebook จำกัดเพจ ไม่ใช่ปัญหาของคนนี้ */
    public function isPageBlockLimited(): bool
    {
        return str_contains((string) $this->page_block_error, self::PAGE_LIMITED_ERROR);
    }

    /**
     * แอดมินที่กดแบน
     */
    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * ตรวจว่าเป็นแบนถาวรหรือไม่
     */
    public function isPermanent(): bool
    {
        return $this->banned_until === null;
    }

    /**
     * ตรวจว่ายัง active อยู่หรือไม่ (ถาวร หรือ ยังไม่ถึงเวลาหมด)
     */
    public function isActive(): bool
    {
        if ($this->isPermanent()) {
            return true;
        }

        return $this->banned_until->isFuture();
    }

    /**
     * ตรวจว่าหมดอายุแล้วหรือไม่
     */
    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    /**
     * คืนข้อความระยะเวลาคงเหลือ (สำหรับแสดงให้ user)
     *
     * @return string เช่น "ถาวร" หรือ "อีก 3 วัน 5 ชั่วโมง"
     */
    public function remainingHumanReadable(): string
    {
        if ($this->isPermanent()) {
            return 'ถาวร';
        }

        if ($this->isExpired()) {
            return 'หมดอายุแล้ว';
        }

        $now = Carbon::now();
        $diff = $now->diff($this->banned_until);

        $parts = [];
        if ($diff->days > 0) {
            $parts[] = $diff->days.' วัน';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.' ชั่วโมง';
        }
        if (empty($parts) && $diff->i > 0) {
            $parts[] = $diff->i.' นาที';
        }
        if (empty($parts)) {
            return 'น้อยกว่า 1 นาที';
        }

        return 'อีก '.implode(' ', $parts);
    }

    /**
     * Scope: เฉพาะที่ยัง active (ยังไม่หมดอายุ)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('banned_until')
                ->orWhere('banned_until', '>', now());
        });
    }

    /**
     * Scope: หมดอายุแล้ว (สำหรับ cleanup)
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('banned_until')
            ->where('banned_until', '<=', now());
    }

    /**
     * Scope: filter ตาม platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
