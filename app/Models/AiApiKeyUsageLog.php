<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI API Key Usage Log Model
 *
 * เก็บ log การใช้งาน API Keys
 *
 * @property int $id
 * @property int $ai_api_key_id
 * @property string|null $model Model ที่ใช้
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $total_tokens
 * @property int|null $response_time_ms เวลาตอบกลับ (ms)
 * @property bool $is_success สำเร็จหรือไม่
 * @property string|null $error_message Error message
 * @property string|null $request_type ประเภท request
 * @property int|null $reading_id fortune_readings.id (deep-link warroom /workers → /chat)
 * @property int|null $user_id users.id ของลูกค้า (เมื่อทราบ)
 * @property string|null $fb_user_id Facebook PSID ของลูกค้า (เมื่อทราบ)
 * @property string|null $customer_name display name snapshot ตอน call (สำหรับ render เร็ว)
 */
class AiApiKeyUsageLog extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'ai_api_key_usage_logs';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'ai_api_key_id',
        'model',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'response_time_ms',
        'is_success',
        'error_message',
        'request_type',
        'reading_id',
        'user_id',
        'fb_user_id',
        'customer_name',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'ai_api_key_id' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'response_time_ms' => 'integer',
        'is_success' => 'boolean',
        'reading_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'input_tokens' => 0,
        'output_tokens' => 0,
        'total_tokens' => 0,
        'is_success' => true,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * ความสัมพันธ์กับ AiApiKey
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(AiApiKey::class, 'ai_api_key_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Scope: เฉพาะที่สำเร็จ
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    /**
     * Scope: เฉพาะที่ล้มเหลว
     */
    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    /**
     * Scope: เฉพาะวันนี้
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: เฉพาะเดือนนี้
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * 🪪 (2026-08-17) เติม reading_id ย้อนหลังให้ log ที่เขียนไปแล้ว
     *
     * ใช้กับเส้นทางที่ "ยิง AI ก่อน แล้วค่อยสร้างใบดูดวง" (FB webhook sync +
     * ProcessFortuneTelling job) — ตอน call ยังไม่รู้ reading_id จึงผูกได้แค่
     * fb_user_id ต้องมาเติมทีหลัง
     *
     * ⚠️ การจำกัดขอบเขต 3 ชั้น (ห้ามตัดออก):
     *   1. fb_user_id ตรงกัน
     *   2. reading_id ยังว่าง — ไม่ทับใบที่ผูกไว้แล้ว
     *   3. created_at >= $since — $since ต้องเก็บ "ก่อน" ยิง AI
     *      ถ้าไม่มีชั้นนี้ ลูกค้าคนเดิมขอดูดวง 2 ครั้งติดกัน ใบที่สองจะกวาด
     *      log ของใบแรกไปเป็นของตัวเอง → ต้นทุนใบแรกกลายเป็น 0
     *
     * ปลอดภัยเมื่อยังไม่ migrate คอลัมน์ (คืน 0 ไม่ throw)
     *
     * @param  string|null  $fbUserId  Facebook PSID ของลูกค้า
     * @param  int  $readingId  fortune_readings.id ที่เพิ่งสร้าง
     * @param  \Carbon\Carbon|\DateTimeInterface  $since  เวลาก่อนเริ่มยิง AI
     * @return int จำนวนแถวที่เติมสำเร็จ
     */
    public static function backfillReadingId(?string $fbUserId, int $readingId, $since): int
    {
        if (empty($fbUserId) || $readingId <= 0) {
            return 0;
        }

        try {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('ai_api_key_usage_logs', 'reading_id')) {
                return 0;
            }

            return (int) static::query()
                ->where('fb_user_id', $fbUserId)
                ->whereNull('reading_id')
                ->where('created_at', '>=', $since)
                ->update(['reading_id' => $readingId]);
        } catch (\Throwable $e) {
            // non-blocking เสมอ — เติม log ไม่สำเร็จ ห้ามทำให้คำทำนายล้ม
            \Illuminate\Support\Facades\Log::debug('AiApiKeyUsageLog: backfill reading_id ไม่สำเร็จ', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
