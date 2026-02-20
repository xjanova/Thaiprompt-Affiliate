<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LINE Gatekeeper Service
 *
 * คุมทราฟฟิคภาพรวมทั้งระบบ — ป้องกัน LINE API rate limit + AI API overload
 *
 * ทำงาน 2 ระดับ:
 * 1. Global rate limit: จำกัด LINE push messages ทั้งระบบ (ทุก user รวมกัน)
 * 2. Global AI limit: จำกัด AI API calls ทั้งระบบ
 *
 * ถ้าเกิน limit → return false → caller ส่งข้อความ "รอสักครู่" แทน
 */
class LineGatekeeperService
{
    // ✅ LINE Push API safe limits (ต่ำกว่า official limit เพื่อความปลอดภัย)
    // LINE official: ~60 requests/min per channel (แต่ enforcement varies)
    const LINE_PUSH_MAX_PER_MINUTE = 40; // push messages ทั้งระบบ / นาที
    const LINE_PUSH_MAX_PER_SECOND = 5;  // push messages ทั้งระบบ / วินาที

    // ✅ AI API safe limits (Groq free tier)
    const AI_MAX_PER_MINUTE = 25;  // AI calls ทั้งระบบ / นาที

    // ✅ Cache keys
    const KEY_LINE_PUSH_MINUTE = 'gatekeeper:line_push:minute';
    const KEY_LINE_PUSH_SECOND = 'gatekeeper:line_push:second';
    const KEY_AI_MINUTE = 'gatekeeper:ai:minute';
    const KEY_THROTTLED_UNTIL = 'gatekeeper:throttled_until';

    /**
     * ตรวจสอบว่าสามารถส่ง LINE push message ได้หรือไม่
     *
     * @return bool true = ส่งได้, false = เกิน limit
     */
    public static function canPushLine(): bool
    {
        // เช็ค per-second limit
        $perSecond = (int) Cache::get(self::KEY_LINE_PUSH_SECOND, 0);
        if ($perSecond >= self::LINE_PUSH_MAX_PER_SECOND) {
            Log::warning('Gatekeeper: LINE push ถูก throttle (per-second)', [
                'current' => $perSecond,
                'limit' => self::LINE_PUSH_MAX_PER_SECOND,
            ]);

            return false;
        }

        // เช็ค per-minute limit
        $perMinute = (int) Cache::get(self::KEY_LINE_PUSH_MINUTE, 0);
        if ($perMinute >= self::LINE_PUSH_MAX_PER_MINUTE) {
            Log::warning('Gatekeeper: LINE push ถูก throttle (per-minute)', [
                'current' => $perMinute,
                'limit' => self::LINE_PUSH_MAX_PER_MINUTE,
            ]);

            // บันทึกเวลา throttle เพื่อให้ webhook ใช้เช็ค
            Cache::put(self::KEY_THROTTLED_UNTIL, now()->addSeconds(15)->timestamp, 15);

            return false;
        }

        return true;
    }

    /**
     * บันทึกว่าส่ง LINE push message แล้ว 1 ครั้ง
     */
    public static function recordLinePush(): void
    {
        // Increment per-second counter (TTL 1 วินาที)
        $secondKey = self::KEY_LINE_PUSH_SECOND;
        if (Cache::has($secondKey)) {
            Cache::increment($secondKey);
        } else {
            Cache::put($secondKey, 1, 1); // TTL 1 วินาที
        }

        // Increment per-minute counter (TTL 60 วินาที)
        $minuteKey = self::KEY_LINE_PUSH_MINUTE;
        if (Cache::has($minuteKey)) {
            Cache::increment($minuteKey);
        } else {
            Cache::put($minuteKey, 1, 60); // TTL 60 วินาที
        }
    }

    /**
     * ตรวจสอบว่าสามารถเรียก AI API ได้หรือไม่
     *
     * @return bool true = เรียกได้, false = เกิน limit
     */
    public static function canCallAI(): bool
    {
        $perMinute = (int) Cache::get(self::KEY_AI_MINUTE, 0);
        if ($perMinute >= self::AI_MAX_PER_MINUTE) {
            Log::warning('Gatekeeper: AI API ถูก throttle', [
                'current' => $perMinute,
                'limit' => self::AI_MAX_PER_MINUTE,
            ]);

            return false;
        }

        return true;
    }

    /**
     * บันทึกว่าเรียก AI API แล้ว 1 ครั้ง
     */
    public static function recordAICall(): void
    {
        $key = self::KEY_AI_MINUTE;
        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, 60);
        }
    }

    /**
     * ตรวจสอบว่าระบบกำลังถูก throttle อยู่หรือไม่ (สำหรับ webhook ใช้เช็คก่อน process)
     *
     * @return bool true = ระบบกำลังถูก throttle
     */
    public static function isSystemThrottled(): bool
    {
        $throttledUntil = Cache::get(self::KEY_THROTTLED_UNTIL);
        if ($throttledUntil && now()->timestamp < $throttledUntil) {
            return true;
        }

        return false;
    }

    /**
     * ดึงสถิติ gatekeeper ปัจจุบัน (สำหรับ debug/monitor)
     */
    public static function getStats(): array
    {
        return [
            'line_push_per_second' => (int) Cache::get(self::KEY_LINE_PUSH_SECOND, 0),
            'line_push_per_minute' => (int) Cache::get(self::KEY_LINE_PUSH_MINUTE, 0),
            'ai_per_minute' => (int) Cache::get(self::KEY_AI_MINUTE, 0),
            'is_throttled' => self::isSystemThrottled(),
            'limits' => [
                'line_push_per_second' => self::LINE_PUSH_MAX_PER_SECOND,
                'line_push_per_minute' => self::LINE_PUSH_MAX_PER_MINUTE,
                'ai_per_minute' => self::AI_MAX_PER_MINUTE,
            ],
        ];
    }
}
