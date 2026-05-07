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
     * เช็คว่า user/ระบบ ยังใช้ Pro model ได้ไหม
     *
     * @param  string  $platform  'facebook' / 'line'
     * @param  string  $platformUserId  PSID / LINE userId
     * @return array ['allowed' => bool, 'reason' => string|null, 'user_count' => int, 'daily_thb' => float]
     */
    public function canUse(string $platform, string $platformUserId): array
    {
        $today = now()->toDateString();
        $maxPerUser = (int) ($this->settings->sensitive_max_per_user_daily ?? 5);
        $maxTotalThb = (float) ($this->settings->sensitive_max_total_daily_thb ?? 200.00);

        // 1. Per-user count
        $userKey = "fortune:sensitive:user:{$platform}:{$platformUserId}:{$today}";
        $userCount = (int) Cache::get($userKey, 0);

        if ($userCount >= $maxPerUser) {
            return [
                'allowed' => false,
                'reason' => 'user_daily_cap',
                'user_count' => $userCount,
                'daily_thb' => 0,
            ];
        }

        // 2. Total daily THB
        $totalKey = "fortune:sensitive:total_thb:{$today}";
        $dailyThb = (float) Cache::get($totalKey, 0);

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
     * บันทึกการใช้ Pro model — เพิ่ม counter
     *
     * @param  string  $platform  'facebook' / 'line'
     * @param  string  $platformUserId  PSID / LINE userId
     * @param  float  $costThb  ประมาณการต้นทุน (THB) จาก tokens
     */
    public function recordUse(string $platform, string $platformUserId, float $costThb = 0): void
    {
        $today = now()->toDateString();
        $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now()));

        // Increment user count
        $userKey = "fortune:sensitive:user:{$platform}:{$platformUserId}:{$today}";
        $userCount = (int) Cache::get($userKey, 0);
        Cache::put($userKey, $userCount + 1, $secondsUntilMidnight);

        // Increment total THB
        if ($costThb > 0) {
            $totalKey = "fortune:sensitive:total_thb:{$today}";
            $current = (float) Cache::get($totalKey, 0);
            Cache::put($totalKey, $current + $costThb, $secondsUntilMidnight);
        }

        Log::debug('Sensitive budget recordUse', [
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
            'user_count' => $userCount + 1,
            'cost_thb' => $costThb,
        ]);
    }

    /**
     * ดึงสถิติของวันนี้ (สำหรับ admin dashboard)
     *
     * @return array ['daily_thb' => float, 'max_thb' => float]
     */
    public function getDailyStats(): array
    {
        $today = now()->toDateString();
        $totalKey = "fortune:sensitive:total_thb:{$today}";

        return [
            'daily_thb' => (float) Cache::get($totalKey, 0),
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
            str_contains($model, 'gpt-5') => 5.0,
            str_contains($model, 'gpt-4o') => 5.0,
            str_contains($model, 'pro') => 5.0,
            default => 2.0,
        };

        $usd = ($tokens / 1_000_000) * $usdPerMillion;

        return round($usd * 36, 4);
    }
}
