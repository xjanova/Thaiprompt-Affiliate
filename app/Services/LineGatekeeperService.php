<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LINE Gatekeeper Service (V2 — Adaptive Retry)
 *
 * เปลี่ยนจาก pre-emptive blocking → adaptive retry
 *
 * หลักการ:
 * 1. LINE Push: ไม่ block ล่วงหน้า → ส่งเลย → ถ้าโดน 429 จะ retry ด้วย backoff
 * 2. AI API: ยังคง rate limit เพราะควบคุมค่าใช้จ่าย (ไม่ใช่แค่ API limit)
 * 3. บันทึก 429 จาก LINE จริง → ใช้ backoff ตาม retry-after header
 *
 * ข้อดีเทียบ V1:
 * - ไม่ block ข้อความสำคัญ (แจ้งเตือนคำทำนาย, ชำระเงิน)
 * - ใช้ข้อมูลจาก LINE จริง (429 + retry-after) แทนการเดา
 * - Retry อัตโนมัติ 3 ครั้ง ด้วย exponential backoff
 */
class LineGatekeeperService
{
    // ✅ AI API limits แยกตามบอท (ควบคุมค่าใช้จ่าย)
    const AI_LIMITS = [
        'fortune' => 25,       // ดูดวง: 25 calls/นาที
        'fresh_market' => 15,  // ตลาดสด: 15 calls/นาที
        'default' => 20,       // อื่นๆ
    ];

    // ✅ Retry config สำหรับ LINE push
    const MAX_RETRIES = 3;

    const BASE_BACKOFF_MS = 500;  // 500ms, 1s, 2s

    // ✅ Cache keys
    const KEY_LINE_PUSH_MINUTE = 'gatekeeper:line_push:minute';

    const KEY_LINE_PUSH_SECOND = 'gatekeeper:line_push:second';

    const KEY_LINE_BACKOFF_UNTIL = 'gatekeeper:line:backoff_until';

    // 🚫 (2026-08-26) โควต้า push รายเดือนหมด — คนละเรื่องกับ rate limit ชั่วคราว
    //   LINE ตอบ 429 เหมือนกันทั้งสองกรณี แต่ "โควต้าหมด" จะไม่หายจนกว่าจะขึ้นเดือนใหม่
    //   ⚠️ ห้ามเอาธงนี้ไปผูกกับ backoff/isSystemThrottled — reply ยังฟรีและใช้ได้ปกติ
    //      (พิสูจน์ 2026-08-25: push 429 47 ครั้ง แต่ reply error แค่ 1 ครั้งและเป็น invalid token)
    const KEY_LINE_QUOTA_EXHAUSTED = 'gatekeeper:line:quota_exhausted';

    // ✅ Backward compatibility constants
    const LINE_PUSH_MAX_PER_MINUTE = 40;

    const LINE_PUSH_MAX_PER_SECOND = 5;

    const AI_MAX_PER_MINUTE = 25;

    const KEY_AI_MINUTE = 'gatekeeper:ai:minute';

    const KEY_THROTTLED_UNTIL = 'gatekeeper:throttled_until';

    /**
     * ตรวจสอบว่าควรรอก่อนส่ง LINE push หรือไม่
     *
     * V2: ไม่ block อีกต่อไป — แค่แนะนำว่าควรรอหรือเปล่า
     * ถ้า LINE เคยตอบ 429 และยังอยู่ใน backoff period → return false
     * นอกนั้น → return true เสมอ
     *
     * @return bool true = ส่งได้เลย, false = อยู่ใน backoff period (แต่ caller ตัดสินใจเอง)
     */
    public static function canPushLine(): bool
    {
        // เช็คว่าอยู่ใน backoff period จาก 429 จริงหรือไม่
        $backoffUntil = Cache::get(self::KEY_LINE_BACKOFF_UNTIL);
        if ($backoffUntil && now()->timestamp < $backoffUntil) {
            $waitSeconds = $backoffUntil - now()->timestamp;
            Log::info('Gatekeeper: อยู่ใน backoff period จาก LINE 429', [
                'wait_seconds' => $waitSeconds,
            ]);

            return false;
        }

        return true;
    }

    /**
     * บันทึกว่าส่ง LINE push message แล้ว 1 ครั้ง (สำหรับ monitoring)
     */
    public static function recordLinePush(): void
    {
        // Increment per-second counter (TTL 1 วินาที)
        $secondKey = self::KEY_LINE_PUSH_SECOND;
        if (Cache::has($secondKey)) {
            Cache::increment($secondKey);
        } else {
            Cache::put($secondKey, 1, 1);
        }

        // Increment per-minute counter (TTL 60 วินาที)
        $minuteKey = self::KEY_LINE_PUSH_MINUTE;
        if (Cache::has($minuteKey)) {
            Cache::increment($minuteKey);
        } else {
            Cache::put($minuteKey, 1, 60);
        }
    }

    /**
     * บันทึกว่า LINE ตอบ 429 — ตั้ง backoff period
     *
     * @param  int|null  $retryAfterSeconds  ค่าจาก retry-after header (ถ้ามี)
     * @param  int  $attempt  ครั้งที่ retry (ใช้คำนวณ exponential backoff)
     */
    public static function recordLineRateLimit(?int $retryAfterSeconds = null, int $attempt = 1): void
    {
        // ใช้ retry-after จาก LINE ก่อน ถ้าไม่มีใช้ exponential backoff
        if ($retryAfterSeconds && $retryAfterSeconds > 0) {
            $backoffSeconds = $retryAfterSeconds;
        } else {
            // Exponential backoff: 1s, 2s, 4s (minimum)
            $backoffSeconds = (int) pow(2, $attempt);
        }

        // จำกัด backoff สูงสุด 30 วินาที
        $backoffSeconds = min($backoffSeconds, 30);

        Cache::put(
            self::KEY_LINE_BACKOFF_UNTIL,
            now()->addSeconds($backoffSeconds)->timestamp,
            $backoffSeconds
        );

        // อัพเดท throttled_until สำหรับ backward compatibility
        Cache::put(self::KEY_THROTTLED_UNTIL, now()->addSeconds($backoffSeconds)->timestamp, $backoffSeconds);

        Log::warning('Gatekeeper: LINE 429 → ตั้ง backoff', [
            'backoff_seconds' => $backoffSeconds,
            'retry_after_from_line' => $retryAfterSeconds,
            'attempt' => $attempt,
        ]);
    }

    /**
     * ล้าง backoff (เรียกเมื่อ push สำเร็จ)
     */
    public static function clearLineBackoff(): void
    {
        Cache::forget(self::KEY_LINE_BACKOFF_UNTIL);
        Cache::forget(self::KEY_THROTTLED_UNTIL);
    }

    /**
     * บันทึกว่าโควต้า push รายเดือนหมดแล้ว (429 + body บอก monthly limit)
     *
     * ⚠️ จงใจ "ไม่" ตั้ง backoff — เพราะ backoff ไปทำให้ isSystemThrottled() เป็น true
     * แล้ว webhook ขาเข้าจะทิ้งคำถามลูกค้าทั้งที่ตอบผ่าน reply ได้ฟรี (เหตุการณ์ 2026-08-25/26)
     *
     * @param  string|null  $rawBody  body ที่ LINE ตอบมา (เก็บไว้ debug)
     */
    public static function markQuotaExhausted(?string $rawBody = null): void
    {
        $ttl = self::secondsUntilQuotaReset();

        // ถ้าเคยตั้งไว้แล้ว ไม่ต้อง log ซ้ำทุก push (กัน log ท่วม)
        $alreadyMarked = Cache::has(self::KEY_LINE_QUOTA_EXHAUSTED);

        Cache::put(self::KEY_LINE_QUOTA_EXHAUSTED, [
            'at' => now()->toIso8601String(),
            'body' => $rawBody ? mb_substr($rawBody, 0, 200) : null,
        ], $ttl);

        if (! $alreadyMarked) {
            Log::critical('Gatekeeper: LINE push quota รายเดือนหมด — สลับไปพึ่ง reply อย่างเดียว', [
                'reset_in_seconds' => $ttl,
                'reset_in_hours' => round($ttl / 3600, 1),
                'body' => $rawBody ? mb_substr($rawBody, 0, 200) : null,
            ]);
        }
    }

    /**
     * โควต้า push รายเดือนหมดอยู่หรือไม่
     *
     * ใช้ตัดสินว่า "ควรเสีย API call ไปกับ push ไหม" — ไม่ใช่ตัวปิดกั้นทางขาเข้า
     */
    public static function isQuotaExhausted(): bool
    {
        return Cache::has(self::KEY_LINE_QUOTA_EXHAUSTED);
    }

    /**
     * ล้างธงโควต้าหมด (เรียกเมื่อ push สำเร็จ = ขึ้นเดือนใหม่ หรืออัปแพลนแล้ว)
     */
    public static function clearQuotaExhausted(): void
    {
        if (Cache::has(self::KEY_LINE_QUOTA_EXHAUSTED)) {
            Cache::forget(self::KEY_LINE_QUOTA_EXHAUSTED);
            Log::info('Gatekeeper: LINE push quota กลับมาใช้ได้แล้ว — ล้างธงโควต้าหมด');
        }
    }

    /**
     * จำนวนวินาทีจนถึงตอนที่โควต้า LINE รีเซ็ต (ต้นเดือนถัดไป)
     *
     * บวกกันชน 1 ชม. เผื่อ timezone ฝั่ง LINE ไม่ตรงกับเซิร์ฟเวอร์
     */
    public static function secondsUntilQuotaReset(): int
    {
        // Carbon 3 คืน float (มีเครื่องหมาย) — cast เป็น int ก่อนเทียบเสมอ
        $seconds = (int) abs(now()->diffInSeconds(now()->addMonthNoOverflow()->startOfMonth())) + 3600;

        // กันค่าเพี้ยน (ต่ำสุด 5 นาที, สูงสุด 32 วัน)
        return (int) max(300, min($seconds, 32 * 24 * 3600));
    }

    /**
     * คำนวณเวลารอก่อน retry (milliseconds)
     *
     * @param  int  $attempt  ครั้งที่ retry (1-based)
     * @return int milliseconds ที่ต้องรอ
     */
    public static function getRetryDelayMs(int $attempt): int
    {
        // Exponential backoff: 500ms, 1000ms, 2000ms
        return self::BASE_BACKOFF_MS * (int) pow(2, $attempt - 1);
    }

    /**
     * ตรวจสอบว่าสามารถเรียก AI API ได้หรือไม่ (แยกตามบอท)
     *
     * ⚠️ AI limit ยังคงใช้ pre-emptive blocking เพราะเป็นการควบคุมค่าใช้จ่าย
     *
     * @param  string  $botName  ชื่อบอท: 'fortune', 'fresh_market', etc.
     * @return bool true = เรียกได้, false = เกิน limit
     */
    public static function canCallAI(string $botName = 'default'): bool
    {
        $limit = self::AI_LIMITS[$botName] ?? self::AI_LIMITS['default'];
        $cacheKey = "gatekeeper:ai:{$botName}:minute";

        $perMinute = (int) Cache::get($cacheKey, 0);
        if ($perMinute >= $limit) {
            Log::warning("Gatekeeper: AI API ถูก throttle ({$botName})", [
                'bot' => $botName,
                'current' => $perMinute,
                'limit' => $limit,
            ]);

            return false;
        }

        return true;
    }

    /**
     * บันทึกว่าเรียก AI API แล้ว 1 ครั้ง (แยกตามบอท)
     *
     * @param  string  $botName  ชื่อบอท: 'fortune', 'fresh_market', etc.
     */
    public static function recordAICall(string $botName = 'default'): void
    {
        $cacheKey = "gatekeeper:ai:{$botName}:minute";
        if (Cache::has($cacheKey)) {
            Cache::increment($cacheKey);
        } else {
            Cache::put($cacheKey, 1, 60);
        }
    }

    /**
     * ตรวจสอบว่าระบบกำลังถูก throttle อยู่หรือไม่
     *
     * V2: ใช้ backoff จาก LINE 429 จริง แทนการเดา
     *
     * ⚠️ (2026-08-26) สะท้อนเฉพาะ "rate limit ชั่วคราว" เท่านั้น
     * โควต้ารายเดือนหมด **ไม่** ทำให้ค่านี้เป็น true — เพราะ reply ยังฟรีและตอบลูกค้าได้
     * ถ้าเอาโควต้าหมดมาผูกตรงนี้ webhook จะทิ้งคำถามลูกค้าทั้งเดือน (ดู markQuotaExhausted)
     *
     * @return bool true = ระบบกำลังอยู่ใน backoff period จาก rate limit จริง
     */
    public static function isSystemThrottled(): bool
    {
        return ! self::canPushLine();
    }

    /**
     * ดึงสถิติ gatekeeper ปัจจุบัน (สำหรับ debug/monitor)
     */
    public static function getStats(): array
    {
        $aiStats = [];
        foreach (array_keys(self::AI_LIMITS) as $bot) {
            $aiStats[$bot] = [
                'current' => (int) Cache::get("gatekeeper:ai:{$bot}:minute", 0),
                'limit' => self::AI_LIMITS[$bot],
            ];
        }

        $backoffUntil = Cache::get(self::KEY_LINE_BACKOFF_UNTIL);
        $backoffRemaining = $backoffUntil ? max(0, $backoffUntil - now()->timestamp) : 0;

        return [
            'line_push_per_second' => (int) Cache::get(self::KEY_LINE_PUSH_SECOND, 0),
            'line_push_per_minute' => (int) Cache::get(self::KEY_LINE_PUSH_MINUTE, 0),
            'line_backoff_remaining_seconds' => $backoffRemaining,
            'ai_per_bot' => $aiStats,
            'is_throttled' => self::isSystemThrottled(),
            // 🚫 (2026-08-26) แยกให้เห็นชัดว่า "โควต้าหมด" ≠ "ถูก throttle"
            'quota_exhausted' => self::isQuotaExhausted(),
            'quota_reset_in_seconds' => self::isQuotaExhausted() ? self::secondsUntilQuotaReset() : 0,
            'limits' => [
                'max_retries' => self::MAX_RETRIES,
                'base_backoff_ms' => self::BASE_BACKOFF_MS,
                'ai_per_bot' => self::AI_LIMITS,
            ],
        ];
    }
}
