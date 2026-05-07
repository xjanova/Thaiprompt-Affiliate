<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;

/**
 * 🚧 FortuneOffTopicTracker (2026-05-07)
 *
 * นับครั้งที่ลูกค้าถาม off-topic ในแชทเดียวกัน
 *
 * เมื่อเกิน sensitive_offtopic_strikes (default 3) → action ตามที่ admin ตั้ง:
 *   - 'revert'  → กลับ default model + ส่งข้อความนำกลับเข้าเรื่องดูดวง
 *   - 'block'   → ส่งข้อความตัดบทสนทนา (sensitive_offtopic_block_message)
 *   - 'handoff' → ส่งให้แอดมินดูแล (FortuneTakeoverService)
 *
 * Reset counter:
 *   - เมื่อ user ถามเรื่องดูดวง (incrementBack)
 *   - หลังหมดวัน (TTL จนเที่ยงคืน)
 *   - หลัง action 'revert' (counter รีเซ็ต ให้โอกาสใหม่)
 */
class FortuneOffTopicTracker
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * เพิ่ม strike + คืนค่าจำนวน strikes ปัจจุบัน
     */
    public function incrementStrike(string $platform, string $platformUserId): int
    {
        $key = $this->cacheKey($platform, $platformUserId);
        $current = (int) Cache::get($key, 0);
        $next = $current + 1;

        $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now()));
        Cache::put($key, $next, $secondsUntilMidnight);

        return $next;
    }

    /**
     * ดึง strikes ปัจจุบัน (ไม่ increment)
     */
    public function getStrikes(string $platform, string $platformUserId): int
    {
        return (int) Cache::get($this->cacheKey($platform, $platformUserId), 0);
    }

    /**
     * รีเซ็ต strikes (เมื่อ user กลับมาถามเรื่องดูดวง หรือหลัง 'revert' action)
     */
    public function resetStrikes(string $platform, string $platformUserId): void
    {
        Cache::forget($this->cacheKey($platform, $platformUserId));
    }

    /**
     * เช็คว่าเกิน threshold หรือยัง
     */
    public function shouldTriggerAction(string $platform, string $platformUserId): bool
    {
        $threshold = (int) ($this->settings->sensitive_offtopic_strikes ?? 3);
        $strikes = $this->getStrikes($platform, $platformUserId);

        return $strikes >= $threshold;
    }

    /**
     * ดึง action ที่ admin ตั้งไว้ (revert / block / handoff)
     */
    public function getAction(): string
    {
        return $this->settings->sensitive_offtopic_action ?? 'revert';
    }

    /**
     * ดึงข้อความ block (admin custom ได้)
     */
    public function getBlockMessage(): string
    {
        return $this->settings->sensitive_offtopic_block_message
            ?? "ขออภัยค่ะ 🙏 แม่หมอจันทรารับเฉพาะคำถามเรื่องดูดวงเท่านั้นนะคะ\n\nหากต้องการดูดวง พิมพ์ \"ดูดวง\" ได้เลยค่ะ ✨";
    }

    /**
     * Cache key สำหรับ user
     */
    protected function cacheKey(string $platform, string $platformUserId): string
    {
        $today = now()->toDateString();

        return "fortune:offtopic:{$platform}:{$platformUserId}:{$today}";
    }
}
