<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * SlipOk Account Model — 1 บัญชี SlipOK ใน Pool
 *
 * แต่ละบัญชี = branch_id + api_key + โควต้าฟรี ~100/เดือน
 * ระบบ pool (SlipOkAccountPool) หมุนเวียนหลายบัญชีกัน quota ตัน
 *
 * @property int $id
 * @property string|null $label ชื่อเรียกบัญชี
 * @property string $branch_id SlipOK Branch ID
 * @property string $api_key SlipOK API Key (encrypted ใน DB)
 * @property int $priority ลำดับใช้งาน (น้อย = ก่อน)
 * @property bool $is_active เปิด/ปิด
 * @property int $monthly_quota โควต้าฟรีต่อเดือน
 * @property int $used_this_month ใช้ไปแล้วเดือนนี้
 * @property int|null $quota_remaining โควต้าคงเหลือจริงจาก API
 * @property \Carbon\Carbon|null $quota_period_start เดือนที่เริ่มนับ
 * @property \Carbon\Carbon|null $last_checked_at
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $exhausted_until พักจนถึงเวลานี้
 * @property int $consecutive_errors
 * @property string|null $last_error
 * @property \Carbon\Carbon|null $last_error_at
 * @property string|null $notes
 */
class SlipOkAccount extends Model
{
    use SoftDeletes;

    /** โหมดหมุนบัญชีที่รองรับ */
    public const MODE_NEAR_EMPTY = 'near_empty';   // สลับเมื่อโควต้าใกล้หมด

    public const MODE_FAILOVER = 'failover';       // ใช้จนหมด/พัง แล้วสลับ

    public const MODE_BALANCE = 'balance';         // เฉลี่ย (เลือกตัวเหลือมากสุด)

    public const MODES = [
        self::MODE_NEAR_EMPTY => 'ใกล้หมดค่อยสลับ (near-empty)',
        self::MODE_FAILOVER => 'หมด/พังค่อยสลับ (failover)',
        self::MODE_BALANCE => 'เฉลี่ยทุกบัญชี (balance)',
    ];

    protected $table = 'slipok_accounts';

    protected $fillable = [
        'label',
        'branch_id',
        'api_key',
        'priority',
        'is_active',
        'monthly_quota',
        'used_this_month',
        'quota_remaining',
        'quota_period_start',
        'last_checked_at',
        'last_used_at',
        'exhausted_until',
        'consecutive_errors',
        'last_error',
        'last_error_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'monthly_quota' => 'integer',
        'used_this_month' => 'integer',
        'quota_remaining' => 'integer',
        'consecutive_errors' => 'integer',
        'quota_period_start' => 'date',
        'last_checked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'exhausted_until' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════
    // 🔐 API Key encryption (mirror AiApiKey)
    // ════════════════════════════════════════════════════════════

    /**
     * เข้ารหัส api_key ก่อนเก็บ DB
     */
    public function setApiKeyAttribute($value): void
    {
        // เก็บ encrypted เสมอ — กัน secret รั่วใน DB dump/log
        $this->attributes['api_key'] = ($value === null || $value === '')
            ? $value
            : Crypt::encryptString((string) $value);
    }

    /**
     * ถอดรหัส api_key เมื่ออ่าน (เผื่อ legacy plaintext → คืนตามเดิม)
     */
    public function getApiKeyAttribute($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // legacy plaintext ที่ยังไม่ encrypt → คืนตามเดิม
            return (string) $value;
        }
    }

    /**
     * api_key แบบ mask สำหรับโชว์ใน admin (ไม่เปิด secret)
     */
    public function getMaskedKeyAttribute(): string
    {
        $key = $this->api_key;
        $len = mb_strlen($key);
        if ($len <= 8) {
            return str_repeat('•', max(0, $len));
        }

        return mb_substr($key, 0, 4).str_repeat('•', 6).mb_substr($key, -4);
    }

    // ════════════════════════════════════════════════════════════
    // 🔎 Scopes
    // ════════════════════════════════════════════════════════════

    /** บัญชีที่เปิดใช้ เรียงตาม priority */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('priority')->orderBy('id');
    }

    /** บัญชีที่พร้อมยิงจริง (เปิด + ไม่พัก + ยังไม่หมดโควต้าเดือนนี้) */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('exhausted_until')->orWhere('exhausted_until', '<=', now());
            })
            ->orderBy('priority')
            ->orderBy('id');
    }

    // ════════════════════════════════════════════════════════════
    // 📊 Quota helpers
    // ════════════════════════════════════════════════════════════

    /**
     * reset ตัวนับ used เมื่อข้ามเดือน (โควต้าฟรี SlipOK reset รายเดือน)
     */
    public function resetMonthlyIfNeeded(): void
    {
        $startOfMonth = now()->startOfMonth();
        if ($this->quota_period_start === null
            || $this->quota_period_start->lt($startOfMonth)) {
            $this->forceFill([
                'used_this_month' => 0,
                'quota_period_start' => $startOfMonth->toDateString(),
                // ข้ามเดือน → ปลด exhausted ที่มาจากโควต้าหมด
                'exhausted_until' => null,
            ])->save();
        }
    }

    /** โควต้าคงเหลือโดยประมาณ (ใช้ค่าจริงจาก API ถ้ามี ไม่งั้นคำนวณจาก quota - used) */
    public function remainingQuota(): int
    {
        if ($this->quota_remaining !== null) {
            return max(0, (int) $this->quota_remaining);
        }

        return max(0, (int) $this->monthly_quota - (int) $this->used_this_month);
    }

    /** กำลังพักอยู่ไหม (โควต้าหมด/พังชั่วคราว) */
    public function isExhausted(): bool
    {
        return $this->exhausted_until !== null && $this->exhausted_until->isFuture();
    }

    /** ยิงได้อีกไหม (active + ไม่พัก + เหลือโควต้า) */
    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExhausted() && $this->remainingQuota() > 0;
    }

    // ════════════════════════════════════════════════════════════
    // 🧮 Mark usage / health
    // ════════════════════════════════════════════════════════════

    /** ยิง API สำเร็จ/นับโควต้า 1 ครั้ง */
    public function markUsed(): void
    {
        $this->resetMonthlyIfNeeded();
        $this->increment('used_this_month');
        $updates = ['last_used_at' => now()];
        if ($this->quota_remaining !== null) {
            $updates['quota_remaining'] = max(0, (int) $this->quota_remaining - 1);
        }
        $this->forceFill($updates)->save();
    }

    /** ยิงสำเร็จ (definitive) → เคลียร์ error ติดต่อกัน */
    public function markSuccess(): void
    {
        if ($this->consecutive_errors > 0 || $this->last_error !== null) {
            $this->forceFill([
                'consecutive_errors' => 0,
                'last_error' => null,
            ])->save();
        }
    }

    /** โควต้าหมด → พักจนสิ้นเดือน (โควต้า reset) */
    public function markQuotaExhausted(?string $reason = null): void
    {
        $this->forceFill([
            'used_this_month' => max($this->used_this_month, $this->monthly_quota),
            'quota_remaining' => 0,
            'exhausted_until' => now()->endOfMonth(),
            'last_error' => $reason ?? 'quota exhausted',
            'last_error_at' => now(),
        ])->save();

        Log::warning('SlipOkAccount: โควต้าหมด → พักถึงสิ้นเดือน', [
            'account_id' => $this->id,
            'label' => $this->label,
            'reason' => $reason,
        ]);
    }

    /** error (key พัง/เน็ตล่ม) → พักสั้นๆ + นับ error */
    public function markError(?string $reason = null, int $cooldownMinutes = 10): void
    {
        $this->increment('consecutive_errors');
        $this->forceFill([
            'last_error' => $reason ?? 'error',
            'last_error_at' => now(),
            // error สะสมเยอะ → พักนานขึ้น
            'exhausted_until' => now()->addMinutes($cooldownMinutes),
        ])->save();
    }

    /** sync โควต้าจริงจาก API /quota */
    public function syncQuota(int $remaining, ?string $endDate = null): void
    {
        $this->forceFill([
            'quota_remaining' => max(0, $remaining),
            'last_checked_at' => now(),
            // ถ้า API บอกเหลือ → ปลด exhausted ที่มาจากโควต้า
            'exhausted_until' => $remaining > 0 ? null : $this->exhausted_until,
        ])->save();
    }
}
