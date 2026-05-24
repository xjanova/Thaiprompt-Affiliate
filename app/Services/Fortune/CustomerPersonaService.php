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
    private const EXTRACTION_THROTTLE_TTL = 1800; // 30 นาที (default)
    private const EXTRACTION_THROTTLE_TTL_CRITICAL = 600; // 10 นาที (เคสวิกฤต)

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

        // 🚩 (2026-05-25) Critical keyword → throttle สั้นลง 30→10 min
        //   เคสจริง conv 4936: "เคยคิดฆ่าตัวตาย 3 ครั้ง" / "กินยานอนหลับจนดื้อยา"
        //   → ระบบต้อง flag เร็ว เพื่อปรับ tone ตอบ + แนะ 1323/1669
        $isCritical = $this->hasCriticalKeyword($messageText);
        $throttleTtl = $isCritical
            ? self::EXTRACTION_THROTTLE_TTL_CRITICAL
            : self::EXTRACTION_THROTTLE_TTL;

        // Throttle — กัน spam AI cost
        $throttleKey = $this->throttleCacheKey($platform, $userId);
        if (Cache::has($throttleKey) && ! $isCritical) {
            Log::debug('CustomerPersonaService: skip extraction — throttled', [
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            return false;
        }
        Cache::put($throttleKey, true, $throttleTtl);

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
     * 🛒 (2026-05-18) บันทึก "บอทเสนอขาย" — เรียกใน Hook A
     *
     * Throttle 5 นาที (ใน model) — เรียกซ้ำๆ ใน flow เดียวกันได้ปลอดภัย
     */
    public function recordPitch(string $platform, string $userId, ?string $displayName = null): bool
    {
        try {
            $persona = FortuneCustomerPersona::getOrCreate($platform, $userId, $displayName);
            $recorded = $persona->recordPitch();

            if ($recorded) {
                $this->invalidateCache($platform, $userId);

                Log::info('CustomerPersonaService: recorded sales pitch', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'total_pitches' => $persona->sales_pitch_count,
                ]);
            }

            return $recorded;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: recordPitch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🤐 (2026-05-18) ตรวจว่า bot ควรเงียบกับลูกค้าคนนี้หรือไม่
     *
     * ใช้ใน Hook B — เรียก lazy resume ถ้า cooldown หมด
     * Caller ต้องตรวจ bypass keyword เอง (FortuneCustomerPersona::shouldBypassSilence)
     */
    public function isChatSilenced(string $platform, string $userId): bool
    {
        try {
            $persona = $this->getCached($platform, $userId);
            if (! $persona) {
                return false;
            }

            $wasSilenced = $persona->chat_silenced_until !== null
                && $persona->chat_silenced_until->gt(now());

            $stillSilenced = $persona->isChatSilenced();

            // ถ้า lazy resume ทำงาน → invalidate cache (state เปลี่ยน)
            if ($wasSilenced && ! $stillSilenced) {
                $this->invalidateCache($platform, $userId);

                Log::info('CustomerPersonaService: chat resumed from cooldown', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'new_score' => $persona->time_waster_score,
                ]);
            }

            return $stillSilenced;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: isChatSilenced ล้มเหลว (default: not silenced)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 💬 (2026-05-18) บันทึก "ลูกค้าตอบฟุ้งหลังบอทเสนอ" — Hook C
     *
     * ตรวจ pre-conditions:
     *  - last_pitch_at อยู่ใน rolling 30min window
     *  - message ไม่ได้มี bypass keyword
     *  - caller ต้องตรวจ looksLikeMetaOrChitchat() เอง
     *
     * @return array{triggered: bool, goodbye_message: ?string}
     *   triggered = true → silence เริ่ม → caller ส่ง goodbye_message แทน AI response
     */
    public function recordChitchatAfterPitch(string $platform, string $userId, string $messageText): array
    {
        $result = ['triggered' => false, 'goodbye_message' => null];

        try {
            // Skip ถ้ามี bypass keyword (ลูกค้าพร้อมจ่าย/ดูดวงต่อ)
            if (FortuneCustomerPersona::shouldBypassSilence($messageText)) {
                return $result;
            }

            $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
            if (! $persona || ! $persona->last_pitch_at) {
                return $result;
            }

            // ต้องอยู่ใน rolling window
            $windowMinutes = FortuneCustomerPersona::PITCH_WINDOW_MINUTES;
            if ($persona->last_pitch_at->lt(now()->subMinutes($windowMinutes))) {
                return $result;
            }

            $reachedThreshold = $persona->recordChitchatAfterPitch($messageText);

            if ($reachedThreshold) {
                $reason = sprintf(
                    'failed %d pitches in %dmin window — example: %s',
                    $persona->sales_pitch_failed_count,
                    $windowMinutes,
                    mb_substr($messageText, 0, 100)
                );

                $persona->triggerSilence($reason);

                $result['triggered'] = true;
                $result['goodbye_message'] = $persona->buildGoodbyeMessage();

                Log::info('CustomerPersonaService: silence triggered (chitchat threshold reached)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'failed_count' => $persona->sales_pitch_failed_count,
                    'new_score' => $persona->time_waster_score,
                    'silenced_until' => $persona->chat_silenced_until?->toIso8601String(),
                ]);

                $this->invalidateCache($platform, $userId);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: recordChitchatAfterPitch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
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

    /**
     * 🚩 (2026-05-25) ตรวจ critical keyword → ลด throttle จาก 30→10 min
     *
     * Keywords ที่ถือเป็น mental crisis / abuse → ต้อง extract เร็ว
     * เพื่อ flag mental_fragile / abusive_tone / scam_victim ทันสถานการณ์
     */
    public function hasCriticalKeyword(string $text): bool
    {
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return false;
        }

        $critical = [
            // Mental crisis
            'ฆ่าตัวตาย', 'ฆ่าตัวเอง', 'อยากตาย', 'อยากจบ',
            'ทำร้ายตัวเอง', 'กรีดข้อมือ', 'กินยาตาย',
            'หมดหวัง', 'ไม่อยากอยู่', 'ไม่ไหวแล้ว',
            'กินยานอนหลับ', 'ดื้อยา',
            'ซึมเศร้า', 'แพนิค',
            // Abusive
            'ห่า', 'เหี้ย', 'ไอ้สัส', 'แม่ง', 'ควาย',
            'โง่', 'กาก', 'ห่วยแตก',
            // Scam victim
            'โดนหลอก', 'โดนโกง', 'มิจฉาชีพ', 'คอลเซ็นเตอร์',
        ];

        foreach ($critical as $kw) {
            if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}
