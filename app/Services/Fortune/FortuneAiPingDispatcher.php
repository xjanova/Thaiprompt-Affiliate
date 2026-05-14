<?php

namespace App\Services\Fortune;

use App\Jobs\FortuneAiPingJob;
use App\Jobs\FortuneAiStuckAlertJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🔔 (2026-05-14) Dispatcher สำหรับ AI Ping + Stuck Alert
 *
 * ใช้ pattern: session-based cancellation
 *   - start() — สร้าง session ID, cache ไว้, dispatch jobs ทั้งหมด (delay 10/30/60s + alert 60s)
 *   - complete() — forget cache → jobs ที่ยังไม่ run จะ skip (เห็น session ไม่ตรง)
 *
 * Queue: 'default' (ใช้ queue หลักที่มี worker อยู่แล้ว)
 *   - ถ้า queue:work ไม่รัน → ping ไม่ทำงาน (graceful degradation)
 *   - ระบบยังทำงานปกติ — แค่ไม่มีข้อความ "กำลังคิด..." เพิ่มเติม
 *
 * Cache TTL: 10 นาที (กัน session ค้างถ้า complete() ไม่ถูกเรียก เช่น exception)
 *
 * Purpose values: deep, celtic-opening, celtic-question, chat
 *   - ใช้ใน log + admin alert (ระบุว่า AI ที่ค้าง คืออะไร)
 */
class FortuneAiPingDispatcher
{
    /**
     * เริ่ม AI session — dispatch ping jobs + admin alert job
     *
     * @param  int     $readingId  FortuneReading ID
     * @param  string  $platform   'facebook' หรือ 'line'
     * @param  string  $userId     Platform user ID
     * @param  string  $purpose    AI purpose (deep/celtic-opening/celtic-question/chat)
     * @return string  Session ID (เก็บไว้เผื่อ complete() ตอนสำเร็จ)
     */
    public static function start(
        int $readingId,
        string $platform,
        string $userId,
        string $purpose
    ): string {
        $sessionId = (string) Str::uuid();
        $cacheKey = self::getSessionCacheKey($readingId);

        // เก็บ session ID ใน cache — ping jobs จะตรวจก่อนส่ง
        Cache::put($cacheKey, $sessionId, 600); // 10 นาที (safety net)

        // 🛡️ ตรวจ queue driver — sync driver ไม่รองรับ delay → ปิงทุกตัวจะวิ่งทันที = bad UX
        //   ถ้า driver=sync → skip dispatch (graceful degradation)
        //   ถ้า driver=database/redis → dispatch ปกติ
        $driver = config('queue.default', 'sync');
        if ($driver === 'sync') {
            Log::debug('FortuneAiPingDispatcher: skip — sync driver ไม่รองรับ delay', [
                'reading_id' => $readingId,
                'driver' => $driver,
            ]);

            return $sessionId; // session cache ยังอยู่ — ไม่กระทบ complete()
        }

        // 📨 Ping jobs ที่ 10s / 30s / 60s — ไม่ระบุ onQueue → ใช้ default queue
        try {
            foreach ([10, 30, 60] as $stage) {
                FortuneAiPingJob::dispatch($readingId, $platform, $userId, $stage, $sessionId)
                    ->delay(now()->addSeconds($stage));
            }
        } catch (\Throwable $e) {
            Log::warning('FortuneAiPingDispatcher: dispatch ping jobs ล้มเหลว (non-blocking)', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }

        // 🚨 Admin alert ที่ 60s — ไม่ระบุ onQueue → ใช้ default queue
        try {
            FortuneAiStuckAlertJob::dispatch($readingId, $platform, $userId, $purpose, $sessionId)
                ->delay(now()->addSeconds(60));
        } catch (\Throwable $e) {
            Log::warning('FortuneAiPingDispatcher: dispatch stuck alert ล้มเหลว (non-blocking)', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('FortuneAiPingDispatcher: เริ่ม AI session', [
            'reading_id' => $readingId,
            'session_id' => $sessionId,
            'platform' => $platform,
            'purpose' => $purpose,
        ]);

        return $sessionId;
    }

    /**
     * ปิด AI session — ping jobs ที่ยังไม่ run จะเห็น session ไม่ตรง → skip
     */
    public static function complete(int $readingId): void
    {
        $cacheKey = self::getSessionCacheKey($readingId);
        Cache::forget($cacheKey);

        Log::debug('FortuneAiPingDispatcher: ปิด AI session', [
            'reading_id' => $readingId,
        ]);
    }

    private static function getSessionCacheKey(int $readingId): string
    {
        return "fortune:ai_session:{$readingId}";
    }
}
