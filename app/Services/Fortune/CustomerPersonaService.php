<?php

namespace App\Services\Fortune;

use App\Jobs\ExtractCustomerPersonaJob;
use App\Models\FortuneCustomerPersona;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 👤 (2026-05-14) Customer Persona Service — บริการจัดการ persona ลูกค้า
 *
 * Responsibilities:
 *  1. getOrCreate() — load/create persona record
 *  2. inject() — สร้าง context block สำหรับ AI prompt
 *  3. dispatchExtraction() — เรียก AI วิเคราะห์ข้อความ user (async)
 *  4. toObsidianMarkdown() — export สำหรับ ObsidianX
 *
 * Cache layer:
 *  - In-request cache (per-request) → กัน N+1 query
 *  - Daily cache (24hr) สำหรับ inject — เร็วกว่า DB hit ทุก message
 *
 * Cost throttle:
 *  - ไม่ dispatch extraction ถ้าเพิ่งสำเร็จไปไม่ถึง 30 นาที (cache flag)
 *  - ไม่ dispatch ถ้าข้อความสั้นเกินไป (< 20 chars — ไม่มีอะไรให้วิเคราะห์)
 */
class CustomerPersonaService
{
    /** Cache TTL สำหรับ inject context (วินาที) */
    private const INJECT_CACHE_TTL = 86400; // 24 ชม

    /** Cache TTL สำหรับ extraction throttle (วินาที) */
    private const EXTRACTION_THROTTLE_TTL = 1800; // 30 นาที

    /** ข้อความที่สั้นกว่านี้จะไม่ extract (ไม่มีอะไรให้วิเคราะห์) */
    private const MIN_MESSAGE_LENGTH = 20;

    /**
     * 🔍 ดึง persona (cached 24hr) — ใช้ตอน inject ลง AI prompt
     */
    public function getCached(string $platform, string $userId): ?FortuneCustomerPersona
    {
        $cacheKey = $this->injectCacheKey($platform, $userId);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            // cache hit (string "MISSING" หรือ serialized persona)
            if ($cached === 'MISSING') {
                return null;
            }

            return $cached;
        }

        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        Cache::put($cacheKey, $persona ?? 'MISSING', self::INJECT_CACHE_TTL);

        return $persona;
    }

    /**
     * 🎯 สร้าง context block สำหรับ inject ใน AI system message
     *
     * @return string  block พร้อม inject (อาจเป็น empty string ถ้าไม่มี persona ที่มีข้อมูล)
     */
    public function buildInjectBlock(string $platform, string $userId): string
    {
        $persona = $this->getCached($platform, $userId);
        if (! $persona) {
            return '';
        }

        return $persona->toAiContextBlock();
    }

    /**
     * 🚀 Dispatch async extraction (วิเคราะห์ message ลูกค้า → update persona)
     *
     * Throttled — ไม่ run บ่อยกว่า 30 นาที/user
     */
    public function dispatchExtraction(
        string $platform,
        string $userId,
        string $messageText,
        ?string $displayName = null
    ): bool {
        // Skip ถ้าข้อความสั้นเกินไป
        if (mb_strlen(trim($messageText)) < self::MIN_MESSAGE_LENGTH) {
            return false;
        }

        // Throttle — กัน spam AI cost
        $throttleKey = $this->throttleCacheKey($platform, $userId);
        if (Cache::has($throttleKey)) {
            Log::debug('CustomerPersonaService: skip extraction — throttled', [
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            return false;
        }
        Cache::put($throttleKey, true, self::EXTRACTION_THROTTLE_TTL);

        // 🎯 (2026-05-17) ใช้ dispatchAfterResponse — ทำงานได้ทั้ง sync และ async driver
        //   เดิม: ถ้า QUEUE_CONNECTION=sync → skip dispatch → persona table ว่างเปล่า
        //   ใหม่: dispatchAfterResponse() รันหลัง webhook ส่ง response แล้ว → ไม่ block ลูกค้า
        //         + ทำงานได้แม้ไม่มี queue worker (รันใน same PHP process)
        //   ถ้า queue driver ≠ sync → ใช้ async dispatch ปกติ (delay 5s ให้ message setdown)
        $driver = config('queue.default', 'sync');

        try {
            if ($driver === 'sync') {
                // Sync driver → ใช้ dispatchAfterResponse (รันหลัง webhook response)
                ExtractCustomerPersonaJob::dispatchAfterResponse(
                    $platform,
                    $userId,
                    $messageText,
                    $displayName
                );

                Log::info('CustomerPersonaService: dispatched extraction (after response)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($messageText),
                    'mode' => 'sync_after_response',
                ]);
            } else {
                // Async driver → ใช้ queue normal
                ExtractCustomerPersonaJob::dispatch(
                    $platform,
                    $userId,
                    $messageText,
                    $displayName
                )->delay(now()->addSeconds(5));

                Log::info('CustomerPersonaService: dispatched extraction (queue)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($messageText),
                    'mode' => 'async_queue',
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: dispatch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🔥 Invalidate cache หลัง update persona (จาก job)
     */
    public function invalidateCache(string $platform, string $userId): void
    {
        Cache::forget($this->injectCacheKey($platform, $userId));
    }

    /**
     * 📝 Export persona เป็น Obsidian markdown
     */
    public function toObsidianMarkdown(FortuneCustomerPersona $persona): string
    {
        return $persona->toObsidianMarkdown();
    }

    private function injectCacheKey(string $platform, string $userId): string
    {
        return "fortune:persona:inject:{$platform}:{$userId}";
    }

    private function throttleCacheKey(string $platform, string $userId): string
    {
        return "fortune:persona:extract_throttle:{$platform}:{$userId}";
    }
}
