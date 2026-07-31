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
 * โหมด `daily`    = (2026-07-31) DM ชวนลูกค้าบอกวันเกิด "รับทำนายวันนี้ฟรี"
 *                   ลูกค้าตอบ → ส่งดวงรายวันของวันเกิดนั้น → เก็บวันเกิด → ชวนเชิงลึกต่อ
 *                   ข้อดีเชิงเทคนิค: ลูกค้า "ตอบก่อน" = เปิดหน้าต่าง 24 ชม. ของ FB เอง
 *                   จึงส่งคำทำนายเต็มได้ ไม่ติด error 10/2018278 เหมือนการยิง DM หาคนเงียบ
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

    /** 🌙 (2026-07-31) โหมด DM ดูดวงรายวัน — ชวนบอกวันเกิดแลกคำทำนายวันนี้ฟรี */
    public const MODE_DAILY = 'daily';

    /** โหมดทั้งหมดที่ระบบรู้จัก — ค่าอื่นทั้งหมดตกเป็น classic (fail-safe) */
    public const MODES = [
        self::MODE_CLASSIC,
        self::MODE_TRANSFER,
        self::MODE_DAILY,
    ];

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

        // ⚠️ (2026-07-31) เดิมเขียนเป็น ternary เทียบกับ transfer ตัวเดียว —
        //    เพิ่มโหมดใหม่แล้วลืมแก้ตรงนี้ = ค่าใหม่ถูกกลืนเป็น classic เงียบ ๆ
        //    (แอดมินกดบันทึกสำเร็จแต่ไม่มีอะไรเกิดขึ้น = บั๊กที่หาสาเหตุยากที่สุดแบบหนึ่ง)
        //    เปลี่ยนเป็น whitelist เพื่อให้เพิ่มโหมดถัดไปแล้วทำงานทันที
        return in_array($mode, self::MODES, true) ? $mode : self::MODE_CLASSIC;
    }

    public function isTransfer(): bool
    {
        return $this->mode() === self::MODE_TRANSFER;
    }

    /**
     * 🌙 อยู่ในโหมด DM ดูดวงรายวันหรือไม่
     */
    public function isDaily(): bool
    {
        return $this->mode() === self::MODE_DAILY;
    }

    /**
     * ⏰ (2026-07-31) โหมด daily "พร้อมเสิร์ฟ" ไหม — ใช้กับ **DM ขาออก** เท่านั้น
     *
     * owner: "หลังเที่ยงคืน ต้องสลับกลับไปเป็น DM แบบเก่า จนกว่าจะ 6 โมง"
     *
     * โหมดเปิดอยู่ก็จริง แต่ถ้ายังไม่มีบทความของวันนี้ (ก่อน 06:00 หรือ job พัง)
     * การชวนลูกค้า "บอกวันเกิดรับคำทำนายฟรี" = สัญญาแล้วส่งของไม่ได้
     * → ช่วงนั้นให้ DM กลับไปใช้ชุดข้อความ/ปุ่มแบบ classic ก่อน
     *
     * ⚠️ **ห้ามใช้ตัวนี้กับด่านขาเข้า** — ลูกค้าที่ตอบวันเกิดมาแล้วต้องได้คำตอบเสมอ
     *    (buildDailyBoxForDayIndex ย้อนหลังได้ 2 วันพร้อมบอกตามตรง / ไม่มีจริง ๆ
     *     ก็ยังมี buildDailyUnavailableMessage) — ห้ามเงียบใส่คนที่ตอบเรามาแล้ว
     */
    public function isDailyServing(): bool
    {
        if (! $this->isDaily()) {
            return false;
        }

        try {
            return app(FortuneGreetingService::class)->dailyArticlesReadyToday();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 🌙 ลูกค้าคนนี้อยู่ในขอบเขตของโหมดดูดวงรายวันไหม
     *
     * ⚠️ ต้องเช็ค platform ทุกครั้ง — LINE ใช้ FortuneConversationService::processMessage
     *    ตัวเดียวกับ Facebook ถ้าไม่กันไว้ ด่านดักวันเกิดจะไปแย่งข้อความลูกค้า LINE
     *    ที่พิมพ์วันเกิดในบริบทอื่น (โหมดนี้ไม่มี DM ชวนฝั่ง LINE เลย)
     *
     * @param  string  $platform  'facebook' | 'line'
     */
    public function dailyAppliesTo(string $platform, ?string $platformUserId): bool
    {
        if (! $this->isDaily()) {
            return false;
        }

        if ($platform !== self::INTERCEPT_PLATFORM) {
            return false;
        }

        return ! empty($platformUserId);
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
     * 🔘 (2026-07-28) ควร "ชวนไปเว็บ/LINE" กับลูกค้าคนนี้ตอนนี้ไหม
     *
     * ใช้กับสิ่งที่ **แทรกเข้าไปในบทสนทนาที่กำลังดำเนินอยู่** — ปุ่มท้ายข้อความ AI
     * และ directive ที่ฉีดให้ AI — ซึ่งต่างจากตัวดักหน้า (`maybeTransferIntercept`)
     * ตรงที่ตัวดักถูกเรียกหลังผ่านกำแพง activeReading มาแล้ว แต่สองอย่างนี้ไม่ผ่าน
     *
     * 🚨 กติกาที่ห้ามพลาด: ลูกค้าที่ **มีบิล/จ่ายเงินแล้ว/กำลังคุยหลังคำทำนาย** ห้ามโดนแตะ
     *    ไม่งั้นคนที่จ่าย 99 ไปแล้วถามต่อใน Pro Session จะโดน AI ตอบว่า
     *    "แม่หมอไม่ทำนายในแชทนี้แล้ว ไปที่เว็บนะคะ" = ลูกค้าเสียเงินแล้วไม่ได้ของ
     *    (rule_paid_customer_bypass_all_guards · feedback_never_interrupt_payment_to_prediction_flow)
     *
     * @param  string  $platform  'facebook' | 'line'
     */
    public function shouldNudgeToTransfer(string $platform, string $platformUserId): bool
    {
        if (! $this->appliesTo($platform, $platformUserId)) {
            return false;
        }

        // ลูกค้ายืนยันขอดูในแชทนี้แล้ว → เคารพการตัดสินใจ ห้ามตื๊อ
        if ($this->hasFbFallback($platform, $platformUserId)) {
            return false;
        }

        try {
            // ⚠️ ใช้ scope ตรง ๆ (read-only) ไม่ใช่ findActiveConversation()
            //    เพราะตัวนั้นเขียน DB (expireOldConversations) — ไม่ควรมี side effect ในเส้นส่งข้อความ
            if (FortuneReading::activeConversation($platformUserId)->exists()) {
                return false;
            }

            // 🚨 Pro Session (คุยต่อหลังคำทำนาย) — reading เป็น "completed" แล้ว
            //    จึงไม่ติดกำแพงข้างบน แต่ลูกค้ายังจ่ายเงินแล้วและกำลังถามต่ออยู่
            //    ถ้าไม่กันตรงนี้ คนที่จ่าย 99 ถามต่อจะโดน AI ตอบว่า "ไปดูที่เว็บนะคะ"
            $latest = FortuneReading::where('facebook_user_id', $platformUserId)
                ->latest('id')
                ->first(['id', 'is_paid', 'conversation_state', 'updated_at']);

            if ($latest && $latest->is_paid) {
                if ($latest->getConversationState('pro_session_active', false)) {
                    return false;
                }

                // เผื่อช่วงสั้น ๆ หลังส่งคำทำนายที่ธง pro session ยังไม่ถูกตั้ง
                if ($latest->updated_at && $latest->updated_at->gt(now()->subMinutes(30))) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // เช็คไม่ได้ → เลือกทางปลอดภัย ไม่แทรกอะไรเลย
            return false;
        }

        return true;
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
     * 🎁 คำทำนายฟรีของบอทเปิดให้ช่องทางนี้ไหม
     *
     * ⚠️ (2026-07-27) จุดที่เคยทำให้ "เปิดโหมดแล้วขา LINE ตายเงียบ":
     *   สวิตช์หลัก `enable_free_card_reading` เป็นคนละหมวดกับการ์ดโหมดใหม่ในหน้าแอดมิน
     *   เปิดโหมด transfer แต่ลืมเปิดตัวนี้ → กล่องบน FB โฆษณา "ดูดวงฟรี" + ปุ่มไป LINE
     *   → ลูกค้าแอด LINE แล้วไม่ได้อะไร (โฆษณาของที่ให้ไม่ได้ = เสียลูกค้าฟรี ๆ)
     *
     * → โหมด transfer ถือว่า "ฟรีบน LINE" เป็นหัวใจของโหมด จึงเปิดให้เองโดยไม่ต้องมีสวิตช์เพิ่ม
     *   (ยิ่งมีลูกบิดให้ลืม ยิ่งพัง — บทเรียนเดียวกับสวิตช์ตัวนี้)
     *   ส่วนโหมด classic ยังเคารพสวิตช์หลัก 100% เหมือนเดิม
     *
     * @param  string  $platform  'facebook' | 'line'
     */
    public function freeCardEnabledFor(string $platform): bool
    {
        if ((bool) ($this->settings->enable_free_card_reading ?? false)) {
            return true;
        }

        return $platform === 'line' && $this->isTransfer();
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
