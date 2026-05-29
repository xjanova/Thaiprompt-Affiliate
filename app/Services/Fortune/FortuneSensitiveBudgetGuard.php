<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ FortuneSensitiveBudgetGuard (2026-05-07)
 *
 * กันใช้ Pro model เกิน budget
 *
 * Cap 2 ชั้น:
 *   1. Per-user daily count — กัน abuse ลูกค้าคนเดียว spam
 *   2. Total daily THB — กัน budget bleed รวมทั้งระบบ
 *
 * ใช้ Cache (Redis/file/db ตาม APP env) — TTL จนสิ้นวัน
 */
class FortuneSensitiveBudgetGuard
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * 🩹 (2026-05-07 review M4) — atomic increments + satang-based THB storage
     *
     * เก็บ THB เป็น satang (THB × 100) ใน Cache::increment เพื่อ atomic
     * (Cache::put + Cache::get มี race condition ใน webhook concurrent)
     */
    protected function userCountKey(string $platform, string $platformUserId, string $today): string
    {
        return "fortune:sensitive:user:{$platform}:{$platformUserId}:{$today}";
    }

    protected function totalSatangKey(string $today): string
    {
        return "fortune:sensitive:total_satang:{$today}";
    }

    /**
     * เช็คว่า user/ระบบ ยังใช้ Pro model ได้ไหม
     *
     * @return array ['allowed' => bool, 'reason' => string|null, 'user_count' => int, 'daily_thb' => float]
     */
    public function canUse(string $platform, string $platformUserId): array
    {
        $today = now()->toDateString();
        $maxPerUser = (int) ($this->settings->sensitive_max_per_user_daily ?? 5);
        $maxTotalThb = (float) ($this->settings->sensitive_max_total_daily_thb ?? 200.00);

        // 1. Per-user count
        $userCount = (int) Cache::get($this->userCountKey($platform, $platformUserId, $today), 0);

        if ($userCount >= $maxPerUser) {
            return [
                'allowed' => false,
                'reason' => 'user_daily_cap',
                'user_count' => $userCount,
                'daily_thb' => 0,
            ];
        }

        // 2. Total daily THB (อ่านจาก satang counter)
        $totalSatang = (int) Cache::get($this->totalSatangKey($today), 0);
        $dailyThb = $totalSatang / 100;

        if ($dailyThb >= $maxTotalThb) {
            return [
                'allowed' => false,
                'reason' => 'system_daily_cap',
                'user_count' => $userCount,
                'daily_thb' => $dailyThb,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'user_count' => $userCount,
            'daily_thb' => $dailyThb,
        ];
    }

    /**
     * บันทึกการใช้ Pro model — atomic increment counter
     *
     * @param  float  $costThb  ประมาณการต้นทุน (THB) จาก tokens
     */
    public function recordUse(string $platform, string $platformUserId, float $costThb = 0): void
    {
        $today = now()->toDateString();
        $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now()));

        // 🩹 atomic per-user count
        $userKey = $this->userCountKey($platform, $platformUserId, $today);
        Cache::add($userKey, 0, $secondsUntilMidnight);
        $newUserCount = Cache::increment($userKey, 1);

        // 🩹 atomic total satang (THB × 100)
        if ($costThb > 0) {
            $satang = (int) round($costThb * 100);
            $totalKey = $this->totalSatangKey($today);
            Cache::add($totalKey, 0, $secondsUntilMidnight);
            Cache::increment($totalKey, $satang);
        }

        Log::debug('Sensitive budget recordUse (atomic)', [
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
            'user_count' => $newUserCount,
            'cost_thb' => $costThb,
        ]);
    }

    /**
     * ดึงสถิติของวันนี้ (สำหรับ admin dashboard)
     */
    public function getDailyStats(): array
    {
        $today = now()->toDateString();
        $totalSatang = (int) Cache::get($this->totalSatangKey($today), 0);

        return [
            'daily_thb' => $totalSatang / 100,
            'max_thb' => (float) ($this->settings->sensitive_max_total_daily_thb ?? 200.00),
        ];
    }

    /**
     * ประมาณการต้นทุนจาก tokens (rough estimate)
     *
     * Gemini 3.1 Pro: $1.25 input + $10 output / M tokens (assume 50/50 = $5.625/M avg)
     * Gemini 2.5 Pro: $1.25/M (similar)
     * GPT-5+: ~$5/M (estimate)
     *
     * 1 USD = 36 THB (approx)
     */
    public static function estimateCostThb(int $tokens, string $model = 'gemini-3.1-pro-preview'): float
    {
        $usdPerMillion = match (true) {
            str_contains($model, 'opus') => 15.0,
            // 🆕 (2026-05-29) แยก nano/mini ก่อน gpt-5 — ไม่งั้น gpt-5.4-mini/nano
            //   โดนคิด $5/M เกินจริง → budget guard trip เร็วเกินเหตุ
            str_contains($model, 'nano') => 1.0,    // gpt-5.4-nano ~$0.20/$1.25
            str_contains($model, 'mini') => 3.0,    // gpt-5.4-mini ~$0.75/$4.50 (output-heavy avg)
            str_contains($model, 'gpt-5') => 5.0,   // gpt-5.5 ~$5/$30
            str_contains($model, 'gpt-4o') => 5.0,
            str_contains($model, 'pro') => 5.0,
            default => 2.0,
        };

        $usd = ($tokens / 1_000_000) * $usdPerMillion;

        return round($usd * 36, 4);
    }
}
