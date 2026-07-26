<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🔀 FortuneBotMode — แหล่งความจริงเดียวของ "โหมดบอท"
 *
 * โหมด `classic`  = พฤติกรรมเดิมทุกอย่าง (default)
 * โหมด `transfer` = ลูกค้า Facebook ที่ทักเข้ามาจะถูก **ดักหน้า** แล้วได้กล่อง
 *                   "ดูดวงฟรี" ที่มีปุ่มไปเว็บจันทรา/LINE แทนการทำนายในแชท FB
 *
 * ที่มา (เจ้าของสั่ง 2026-07-25/26): FB แบนแอดมินมั่ว + สลิปเยอะโดนกล่าวหาว่า
 * หลอกลวง → ใช้ FB ตักปลาเหมือนเดิม แต่ย้ายการให้บริการไปช่องทางที่เราคุมได้
 *
 * ⚠️ กฎเหล็กของคลาสนี้ (จากบทเรียนที่เจ็บมาแล้ว):
 *
 * 1. **ห้ามเช็คโหมดกระจายตามไฟล์** — ทุกจุดต้องถามผ่านคลาสนี้เท่านั้น
 *    (guard ที่ลิสต์เงื่อนไขไม่ครบแล้วกระจายหลายที่ = เคส DEEP_ACTIVE_STATUSES
 *     ที่ตกหล่นจนบอทแย่งข้อความลูกค้า)
 *
 * 2. **สถานะรายลูกค้าเก็บลง DB ไม่ใช่ Cache** — deploy รัน cache:clear เกือบทุกวัน
 *    ถ้าเก็บ "ลูกค้ายืนยันขอดูบน FB 30 วัน" ใน Cache มันจะหายกลางทางแล้วบอท
 *    กลับไปดักคนที่เพิ่งบอกว่าทำไม่เป็น (บทเรียนเดียวกับ weekly-image dedup)
 *
 * 3. **fail-safe = classic** — ทุก error/ไม่แน่ใจ ต้องตกไปพฤติกรรมเดิม
 *    ห้ามให้ระบบใหม่ที่พังไปบล็อกลูกค้าที่กำลังจะจ่ายเงิน
 */
class FortuneBotMode
{
    public const MODE_CLASSIC = 'classic';

    public const MODE_TRANSFER = 'transfer';

    /** ช่องทางที่โหมดนี้ดัก — LINE ไม่ดักโดยเจตนา (เป็นปลายทางที่เราอยากให้ใช้) */
    public const INTERCEPT_PLATFORM = 'facebook';

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        // getSettings() มี TTL 5 วิ — แอดมินสลับโหมดแล้วมีผลเกือบทันทีทั้ง
        // webhook และ queue worker (ไม่ต้องรอ restart)
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * โหมดปัจจุบัน (ค่าที่อ่านไม่ออก = classic เสมอ)
     */
    public function mode(): string
    {
        $mode = trim((string) ($this->settings->fortune_bot_mode ?? self::MODE_CLASSIC));

        return $mode === self::MODE_TRANSFER ? self::MODE_TRANSFER : self::MODE_CLASSIC;
    }

    public function isTransfer(): bool
    {
        return $this->mode() === self::MODE_TRANSFER;
    }

    /**
     * ลูกค้าคนนี้อยู่ในโหมด transfer ไหม (รวมเงื่อนไขช่องทาง + rollout %)
     *
     * @param  string  $platform  'facebook' | 'line'
     */
    public function appliesTo(string $platform, ?string $platformUserId): bool
    {
        if (! $this->isTransfer()) {
            return false;
        }

        // LINE คือปลายทางที่เราอยากให้ลูกค้าไปใช้ — ไม่ดักที่นั่น
        if ($platform !== self::INTERCEPT_PLATFORM) {
            return false;
        }

        if (empty($platformUserId)) {
            return false;
        }

        return $this->inRollout($platformUserId);
    }

    /**
     * แฮชจาก psid → เปิดทีละกลุ่มได้ (100 = ทุกคน)
     *
     * ใช้ crc32 เพื่อให้ "คนเดิมได้ผลเดิมทุกครั้ง" — ถ้าสุ่มใหม่ทุก request
     * ลูกค้าคนหนึ่งจะเจอบอทสองบุคลิกสลับกันไปมา
     */
    public function inRollout(string $platformUserId): bool
    {
        $percent = (int) ($this->settings->transfer_rollout_percent ?? 100);

        if ($percent >= 100) {
            return true;
        }

        if ($percent <= 0) {
            return false;
        }

        return (crc32('tp-transfer|'.$platformUserId) % 100) < $percent;
    }

    // ============================================================
    // ค่าตั้งของโหมด
    // ============================================================

    public function boxCooldownHours(): int
    {
        return max(0, (int) ($this->settings->transfer_box_cooldown_hours ?? 24));
    }

    public function fallbackAttempts(): int
    {
        // 0 = ไม่ยอมให้ดูในแชท FB เลย (เจ้าของเลือกได้ แต่ default 3)
        return max(0, (int) ($this->settings->transfer_fallback_attempts ?? 3));
    }

    public function fallbackDays(): int
    {
        return max(1, (int) ($this->settings->transfer_fallback_days ?? 30));
    }

    /**
     * ความยาวคำทำนายฟรีของบอท (0 = ของเดิม 1,500-2,000 ตัวอักษร)
     */
    public function freeCardMaxChars(): int
    {
        $chars = (int) ($this->settings->free_card_max_chars ?? 0);

        return $chars > 0 ? max(200, min(2000, $chars)) : 0;
    }

    /**
     * รอบแจกสิทธิ์ฟรีใหม่ — สิทธิ์ที่ใช้ก่อนเวลานี้ไม่นับ (แจกใหม่ทุกคน)
     */
    public function freeCardRegrantAt(): ?Carbon
    {
        $at = $this->settings->free_card_regrant_at ?? null;

        if (empty($at)) {
            return null;
        }

        try {
            return $at instanceof Carbon ? $at : Carbon::parse($at);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ลูกค้าคนนี้มีสิทธิ์ดูฟรี (รอบปัจจุบัน) หรือยัง
     *
     * ต่างจาก FortuneReading::hasUsedFreeCard() ตรงที่นับ "รอบแจก" ด้วย —
     * เลื่อน free_card_regrant_at = ทุกคนได้สิทธิ์ใหม่ทันทีโดยไม่ต้องแตะข้อมูลลูกค้า
     */
    public function freeCardAvailable(string $platform, string $platformUserId): bool
    {
        try {
            if (! FortuneReading::hasUsedFreeCard($platform, $platformUserId)) {
                return true;
            }

            $regrantAt = $this->freeCardRegrantAt();
            if ($regrantAt === null) {
                return false;
            }

            // เคยใช้ แต่ใช้ก่อนรอบแจกใหม่ → ถือว่ายังไม่ได้ใช้ในรอบนี้
            $lastFree = FortuneReading::where('platform', $platform)
                ->where(function ($q) use ($platformUserId) {
                    $q->where('platform_user_id', $platformUserId)
                        ->orWhere('facebook_user_id', $platformUserId);
                })
                ->where('reading_type', FortuneReading::READING_TYPE_FREE_CARD)
                ->whereNotNull('responded_at')
                ->max('responded_at');

            if (empty($lastFree)) {
                return true;
            }

            return Carbon::parse($lastFree)->lt($regrantAt);
        } catch (\Throwable $e) {
            Log::warning('FortuneBotMode::freeCardAvailable ล้มเหลว — ถือว่าใช้สิทธิ์แล้ว', [
                'platform' => $platform,
                'err' => $e->getMessage(),
            ]);

            // fail-safe: ไม่แจกซ้ำดีกว่าแจกรัว
            return false;
        }
    }

    // ============================================================
    // สถานะรายลูกค้า (เก็บใน fortune_user_credits — คงอยู่ข้าม deploy)
    // ============================================================

    /**
     * ลูกค้ายืนยันแล้วว่าขอดูดวงในแชท FB (ทำเว็บ/ไลน์ไม่ได้จริง ๆ)
     *
     * ยืนยันครั้งเดียวแล้วจำไว้ตามจำนวนวันที่ตั้ง — ไม่ต้องถามซ้ำทุกครั้ง
     * เพราะกลุ่มนี้คือคนที่ทำไม่เป็น ถามซ้ำ = ไล่ลูกค้าออกจากร้าน
     */
    public function hasFbFallback(string $platform, string $platformUserId): bool
    {
        $row = $this->creditRow($platform, $platformUserId);

        if (! $row || empty($row->fb_fallback_granted_at)) {
            return false;
        }

        try {
            return Carbon::parse($row->fb_fallback_granted_at)
                ->gt(now()->subDays($this->fallbackDays()));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * บันทึกว่าลูกค้าเลือกดูในแชท FB + รีเซ็ตตัวนับความพยายาม
     */
    public function grantFbFallback(string $platform, string $platformUserId): void
    {
        try {
            FortuneUserCredit::getOrCreate($platformUserId, $platform)
                ->forceFill([
                    'fb_fallback_granted_at' => now(),
                    'transfer_attempts' => 0,
                ])->save();

            Log::info('Transfer: ลูกค้ายืนยันขอดูดวงในแชท FB', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'days' => $this->fallbackDays(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Transfer: บันทึก fb_fallback ไม่สำเร็จ', ['err' => $e->getMessage()]);
        }
    }

    /**
     * จำนวนครั้งที่พยายามพาลูกค้าคนนี้ไปเว็บ/LINE แล้ว
     */
    public function attempts(string $platform, string $platformUserId): int
    {
        return (int) ($this->creditRow($platform, $platformUserId)->transfer_attempts ?? 0);
    }

    /**
     * ถึงเวลายอมให้ดูในแชท FB แล้วหรือยัง (พยายามครบตามที่ตั้งไว้)
     */
    public function attemptsExhausted(string $platform, string $platformUserId): bool
    {
        $limit = $this->fallbackAttempts();

        return $limit > 0 && $this->attempts($platform, $platformUserId) >= $limit;
    }

    /**
     * ส่งกล่องพาไปได้ไหม (ผ่าน cooldown แล้ว)
     */
    public function canSendBox(string $platform, string $platformUserId): bool
    {
        $hours = $this->boxCooldownHours();

        if ($hours <= 0) {
            return true;
        }

        $row = $this->creditRow($platform, $platformUserId);

        if (! $row || empty($row->transfer_box_sent_at)) {
            return true;
        }

        try {
            return Carbon::parse($row->transfer_box_sent_at)->lte(now()->subHours($hours));
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * บันทึกว่าส่งกล่องไปแล้ว + นับความพยายามเพิ่ม 1
     */
    public function markBoxSent(string $platform, string $platformUserId): void
    {
        try {
            $row = FortuneUserCredit::getOrCreate($platformUserId, $platform);
            $row->forceFill([
                'transfer_box_sent_at' => now(),
                'transfer_attempts' => (int) ($row->transfer_attempts ?? 0) + 1,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Transfer: บันทึก markBoxSent ไม่สำเร็จ', ['err' => $e->getMessage()]);
        }
    }

    protected function creditRow(string $platform, string $platformUserId): ?FortuneUserCredit
    {
        try {
            return FortuneUserCredit::findByUser($platformUserId, $platform);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
