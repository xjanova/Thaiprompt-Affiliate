<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🚫 (2026-05-20 Phase 3b) Image Spam Guard
 *
 * ป้องกันลูกค้าส่งรูปรัว ๆ — กันเปลือง quota Gemini classifier + กัน OpenAI Celtic vision spam
 *
 * User spec 2026-05-20: "ถ้าส่งรูปรัวๆ จะถือว่าสแปม"
 *
 * Rate limit (2 ชั้น):
 *   1. Short burst: 3 รูป / 10 วินาที → cooldown 60 วินาที (silent ignore)
 *   2. Sustained: 5 รูป / 60 วินาที → cooldown 5 นาที + alert admin (FCM/LINE OA)
 *
 * Storage: Laravel Cache (Redis/file) — per-platform per-user
 */
class ImageSpamGuard
{
    /** Cache key prefix */
    protected const KEY_PREFIX = 'image_spam';

    /** Short burst: 3 รูป / 10s → cooldown 60s */
    protected const SHORT_BURST_MAX = 3;

    protected const SHORT_BURST_WINDOW = 10;

    protected const SHORT_COOLDOWN_SEC = 60;

    /** Sustained: 5 รูป / 60s → cooldown 5 นาที */
    protected const SUSTAINED_MAX = 5;

    protected const SUSTAINED_WINDOW = 60;

    protected const SUSTAINED_COOLDOWN_SEC = 300;

    /**
     * ตรวจว่าผู้ใช้ติด spam cooldown หรือยัง
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $userId  user id ของ platform
     * @return array{blocked: bool, reason: ?string, cooldown_until: ?int, level: ?string}
     */
    public function check(string $platform, string $userId): array
    {
        $cooldownKey = $this->cooldownKey($platform, $userId);
        $cooldownUntil = Cache::get($cooldownKey);

        if ($cooldownUntil !== null && $cooldownUntil > time()) {
            return [
                'blocked' => true,
                'reason' => 'cooldown active',
                'cooldown_until' => $cooldownUntil,
                'level' => Cache::get($cooldownKey.':level'),
            ];
        }

        return [
            'blocked' => false,
            'reason' => null,
            'cooldown_until' => null,
            'level' => null,
        ];
    }

    /**
     * บันทึกว่าผู้ใช้ส่งรูป + ตรวจ trigger cooldown
     *
     * @return array{triggered: bool, level: ?string, count: int}
     */
    public function record(string $platform, string $userId): array
    {
        $now = time();

        // 1️⃣ Short burst tracker — array ของ timestamp ใน 10s ล่าสุด
        $shortKey = $this->burstKey($platform, $userId, 'short');
        $shortTimestamps = Cache::get($shortKey, []);
        $shortTimestamps = array_filter($shortTimestamps, fn ($t) => ($now - $t) < self::SHORT_BURST_WINDOW);
        $shortTimestamps[] = $now;
        Cache::put($shortKey, array_values($shortTimestamps), self::SHORT_BURST_WINDOW + 5);

        // 2️⃣ Sustained tracker — array ของ timestamp ใน 60s ล่าสุด
        $sustainedKey = $this->burstKey($platform, $userId, 'sustained');
        $sustainedTimestamps = Cache::get($sustainedKey, []);
        $sustainedTimestamps = array_filter($sustainedTimestamps, fn ($t) => ($now - $t) < self::SUSTAINED_WINDOW);
        $sustainedTimestamps[] = $now;
        Cache::put($sustainedKey, array_values($sustainedTimestamps), self::SUSTAINED_WINDOW + 10);

        $shortCount = count($shortTimestamps);
        $sustainedCount = count($sustainedTimestamps);

        // 3️⃣ Trigger cooldown — sustained ก่อน (rule แรงกว่า)
        $cooldownKey = $this->cooldownKey($platform, $userId);

        if ($sustainedCount >= self::SUSTAINED_MAX) {
            $until = $now + self::SUSTAINED_COOLDOWN_SEC;
            Cache::put($cooldownKey, $until, self::SUSTAINED_COOLDOWN_SEC);
            Cache::put($cooldownKey.':level', 'sustained', self::SUSTAINED_COOLDOWN_SEC);

            Log::warning('ImageSpamGuard: sustained spam triggered', [
                'platform' => $platform,
                'user_id' => $userId,
                'count_in_60s' => $sustainedCount,
                'cooldown_sec' => self::SUSTAINED_COOLDOWN_SEC,
            ]);

            $this->maybeAlertAdmin($platform, $userId, $sustainedCount, 'sustained');

            return ['triggered' => true, 'level' => 'sustained', 'count' => $sustainedCount];
        }

        if ($shortCount >= self::SHORT_BURST_MAX) {
            $until = $now + self::SHORT_COOLDOWN_SEC;
            Cache::put($cooldownKey, $until, self::SHORT_COOLDOWN_SEC);
            Cache::put($cooldownKey.':level', 'short', self::SHORT_COOLDOWN_SEC);

            Log::info('ImageSpamGuard: short burst triggered', [
                'platform' => $platform,
                'user_id' => $userId,
                'count_in_10s' => $shortCount,
                'cooldown_sec' => self::SHORT_COOLDOWN_SEC,
            ]);

            return ['triggered' => true, 'level' => 'short', 'count' => $shortCount];
        }

        return ['triggered' => false, 'level' => null, 'count' => $shortCount];
    }

    /**
     * Reset cooldown (manual — admin override)
     */
    public function reset(string $platform, string $userId): void
    {
        Cache::forget($this->cooldownKey($platform, $userId));
        Cache::forget($this->cooldownKey($platform, $userId).':level');
        Cache::forget($this->burstKey($platform, $userId, 'short'));
        Cache::forget($this->burstKey($platform, $userId, 'sustained'));
    }

    /**
     * Alert admin เมื่อ sustained spam (FCM/LINE OA — non-blocking)
     */
    protected function maybeAlertAdmin(string $platform, string $userId, int $count, string $level): void
    {
        // Throttle alert ต่อ user — กัน flood admin (alert ทุก 30 นาที พอ)
        $alertKey = "image_spam_alert:{$platform}:{$userId}";
        if (Cache::has($alertKey)) {
            return;
        }
        Cache::put($alertKey, true, 1800); // 30 min

        try {
            // ใช้ LineAlertService ถ้ามี — ไม่ throw ถ้า service ไม่พร้อม
            if (class_exists(\App\Services\LineAlertService::class)) {
                app(\App\Services\LineAlertService::class)->alertSystemError(
                    "🚫 Image spam detected: {$platform}/{$userId} ส่งรูป {$count} ครั้งใน 1 นาที ({$level} cooldown)"
                );
            }
        } catch (\Throwable $e) {
            Log::debug('ImageSpamGuard: alert admin fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function cooldownKey(string $platform, string $userId): string
    {
        return self::KEY_PREFIX.":cooldown:{$platform}:{$userId}";
    }

    protected function burstKey(string $platform, string $userId, string $bucket): string
    {
        return self::KEY_PREFIX.":burst:{$bucket}:{$platform}:{$userId}";
    }
}
