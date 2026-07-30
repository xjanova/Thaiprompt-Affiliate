<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;

/**
 * 💳 FortuneBillContextDetector (2026-05-07)
 *
 * ตรวจจับว่าลูกค้ามีบิลค้างหรือไม่ + ดึง context สำหรับ Bill Psychology prompt
 *
 * Triggers:
 *   - มี FortuneReading ที่ is_paid=false + conversation_status ∈ PENDING_PAYMENT_STATUSES
 *   - อยู่ภายใน window (default 24h)
 *
 * Anti-spam guard:
 *   - cache count: bot mention บิลกี่ครั้งแล้วใน session
 *   - เกิน max → ห้าม mention อีก (ป้องกัน "ทวงรัวๆ" — ดูเหมือนสแกม)
 *
 * Context สำหรับ AI:
 *   - bill_ref, package, amount
 *   - hours_since_created (ความเร่งด่วน)
 *   - mention_count (กัน bot ทวงซ้ำ)
 */
class FortuneBillContextDetector
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจว่า user มีบิลค้างไหม
     *
     * @param  string  $platform  'facebook' / 'line'
     * @param  string  $platformUserId  PSID / LINE userId
     * @return array|null null ถ้าไม่มีบิลค้าง / array context ถ้ามี
     */
    public function detect(string $platform, string $platformUserId): ?array
    {
        if (! ($this->settings->bill_psychology_enabled ?? true)) {
            return null;
        }

        $windowHours = (int) ($this->settings->bill_psychology_window_hours ?? 24);
        $cutoff = now()->subHours($windowHours);

        // ดึง reading ที่ pending payment ใน window
        // 🩹 (2026-05-09 audit fix A6) Filter UPA expired/cancelled — AI ห้ามแนะให้ลูกค้า
        //    จ่ายบิลที่ matcher ไม่รับแล้ว (status≠reserved หรือ expires_at <= now)
        //    เคสเดิม: bill_psychology_window=24h ครอบคลุม UPA expired (default ~60 นาที)
        //            → AI urge "โอน 39.47 บาทตามบิล" → ลูกค้าโอน → SMS matcher ปฏิเสธ → ติด support
        $query = FortuneReading::whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
            ->where('is_paid', false)
            ->where('updated_at', '>=', $cutoff)
            ->whereHas('uniquePaymentAmount', function ($q) {
                $q->where('status', 'reserved')
                    ->where('expires_at', '>', now());
            })
            ->with('uniquePaymentAmount')
            ->orderByDesc('updated_at');

        // 🩹 (2026-05-08) fortune_readings ใช้ platform_user_id สำหรับ LINE
        //   ไม่มี column 'line_user_id' — query เก่า throw SQLSTATE[42S22]
        if ($platform === 'facebook') {
            $query->where('facebook_user_id', $platformUserId);
        } else {
            $query->where('platform_user_id', $platformUserId);
        }

        $reading = $query->first();
        if (! $reading) {
            return null;
        }

        $packageLabel = match ($reading->reading_type ?? null) {
            'celtic_cross' => 'ไพ่ Celtic Cross 10 ใบ',
            'deep' => 'ทำนายเชิงลึก Deep Reading',
            default => 'การทำนาย',
        };

        // 💰 (2026-07-30) คอลัมน์จริงคือ `unique_amount` — ของเดิมเขียน `->amount` ที่ไม่มีจริง
        //   ทำให้ตกไปใช้ amount_paid เสมอ → เคสที่ยอดสองตัวไม่ตรงกัน (เช่น 99.00 vs 99.03)
        //   AI จะบอกยอดผิด ลูกค้าโอนไม่ตรงทศนิยม → ระบบจับคู่สลิปอัตโนมัติไม่ได้
        $amount = $reading->uniquePaymentAmount->unique_amount ?? $reading->amount_paid ?? 0;

        // 🔖 (2026-07-30) bill_reference อยู่บน fortune_readings ไม่ใช่ unique_payment_amounts
        //   ของเดิมอ่านผิดตาราง → bill_ref เป็น null ตลอด (AI ไม่เคยรู้เลขบิลจริง)
        $billRef = $reading->bill_reference ?? null;

        $hoursSince = $reading->updated_at ? $reading->updated_at->diffInHours(now()) : 0;
        $minutesSince = $reading->updated_at ? $reading->updated_at->diffInMinutes(now()) : 0;

        return [
            'reading_id' => $reading->id,
            'package' => $packageLabel,
            'amount' => (float) $amount,
            'bill_ref' => $billRef,
            'hours_since' => $hoursSince,
            'minutes_since' => $minutesSince,
            'reading_type' => $reading->reading_type,
            'mention_count' => $this->getMentionCount($platform, $platformUserId),
            'max_mentions' => (int) ($this->settings->bill_max_mentions_per_session ?? 2),
        ];
    }

    /**
     * เพิ่ม count การ mention บิล
     */
    public function incrementMention(string $platform, string $platformUserId): int
    {
        $key = $this->cacheKey($platform, $platformUserId);
        $current = (int) Cache::get($key, 0);
        $next = $current + 1;

        // TTL 6 ชั่วโมง — สั้นกว่า window 24h เพราะ session ใหม่ก็ควร reset
        Cache::put($key, $next, 6 * 3600);

        return $next;
    }

    /**
     * ดึง mention count (ไม่ increment)
     */
    public function getMentionCount(string $platform, string $platformUserId): int
    {
        return (int) Cache::get($this->cacheKey($platform, $platformUserId), 0);
    }

    /**
     * เช็คว่าถึง limit แล้วไหม (Bot ห้าม mention บิลอีก)
     */
    public function reachedMentionLimit(string $platform, string $platformUserId): bool
    {
        $max = (int) ($this->settings->bill_max_mentions_per_session ?? 2);
        $current = $this->getMentionCount($platform, $platformUserId);

        return $current >= $max;
    }

    /**
     * Reset เมื่อลูกค้าโอนสำเร็จ
     */
    public function resetMentions(string $platform, string $platformUserId): void
    {
        Cache::forget($this->cacheKey($platform, $platformUserId));
    }

    protected function cacheKey(string $platform, string $platformUserId): string
    {
        return "fortune:bill_mention:{$platform}:{$platformUserId}";
    }
}
