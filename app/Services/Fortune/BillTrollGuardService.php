<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneBanService;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ (2026-06-12) Bill-Troll Guard — ตรวจจับ + แบนถาวรคนสร้างบิลเล่นๆ ไม่ยอมชำระ
 *
 * เจ้าของสั่ง:
 *   "ถ้าสร้างบิลโดยไม่ชำระ 2 บิลแล้ว บิลที่ 3 ก่อนสร้างให้เตือนว่ามีเจตนาก่อกวน
 *    ถ้ารอบที่ 3 สร้างบิลแล้วไม่ชำระให้เสร็จใน 3 ชม. ถือว่าก่อกวน แบนออกจากเพจถาวร
 *    รวมพวกที่สร้างแล้วยกเลิก สร้างใหม่ด้วย"
 *
 * กติกา:
 *   - strike = บิลที่จบโดยไม่จ่าย (หมดเวลา auto_expired / ลูกค้ายกเลิกเอง user_cancelled)
 *   - ⛔ ไม่นับ: ยกเลิกเพื่อเปลี่ยนแพคเกจ (package_switch) — ลูกค้าดีต้องไม่โดน
 *   - หน้าต่างนับ 3 วันย้อนหลัง (เจ้าของ: "ล้างภายใน 3 วัน เพราะบางคนสร้างแต่โอนไม่เป็น")
 *   - จ่ายสำเร็จเมื่อไหร่ → ล้าง strike ทันที (นับเฉพาะบิลหลังการจ่ายล่าสุด)
 *   - strike ครบ 2 + สร้างบิลที่ 3 → แนบคำเตือนชัดเจนไปกับบิล (troll_warning_shown)
 *   - บิลที่ 3 ไม่จ่าย (ครบ 3 strike + เคยเห็นคำเตือนบนบิลนั้น) → แบนถาวร:
 *       • bot-level: FortuneBanService (บอทเงียบใส่ทุก platform)
 *       • FB: blockPageUser — Graph API /{page-id}/blocked (ห้าม DM + คอมเมนต์จริง)
 *       • LINE: ไม่มี platform block API → bot-level อย่างเดียว
 *   - 🛟 Safety: เคยจ่ายจริงใน 30 วัน → ไม่ auto-ban (log แจ้งแอดมินแทน)
 *
 * Toggle: admin setting `enable_bill_troll_ban` (default เปิด)
 */
class BillTrollGuardService
{
    /**
     * หน้าต่างนับ strike (วัน)
     */
    public const STRIKE_WINDOW_DAYS = 3;

    /**
     * จำนวนบิลไม่ชำระที่ถือว่าก่อกวน (แบนเมื่อครบ)
     */
    public const MAX_STRIKES = 3;

    /**
     * Safety: เคยจ่ายจริงภายใน N วัน → ไม่ auto-ban
     */
    public const PAID_CUSTOMER_GRACE_DAYS = 30;

    /**
     * เปิดใช้งานระบบหรือไม่ (admin toggle — default เปิด)
     */
    public function isEnabled(): bool
    {
        try {
            return (bool) (FortuneTellingSetting::getSettings()->enable_bill_troll_ban ?? true);
        } catch (\Throwable $e) {
            return false; // setting อ่านไม่ได้ → fail-safe ปิดระบบแบน
        }
    }

    /**
     * นับ strike ของ user — บิลที่จบโดยไม่จ่ายใน 3 วันย้อนหลัง (หลังการจ่ายล่าสุด)
     *
     * @param  string  $userId  PSID / LINE userId (match ทั้ง facebook_user_id + platform_user_id)
     */
    public function strikeCount(string $userId): int
    {
        $since = now()->subDays(self::STRIKE_WINDOW_DAYS);

        // จ่ายสำเร็จล่าสุดเมื่อไหร่ — บิลก่อนหน้านั้นไม่นับ (จ่ายแล้ว = ล้าง strike)
        $lastPaidAt = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', true)
            ->max('created_at');

        $query = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('created_at', '>=', $since)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            // ⛔ ไม่นับยกเลิกเพื่อเปลี่ยนแพคเกจ — reason อื่น (รวม null = ปิดโดย sweep) นับหมด
            ->where(function ($q) {
                $q->whereNull('conversation_state->cancellation_reason')
                    ->orWhere('conversation_state->cancellation_reason', '!=', 'package_switch');
            });

        if ($lastPaidAt) {
            $query->where('created_at', '>', $lastPaidAt);
        }

        return $query->count();
    }

    /**
     * 🔊 (2026-06-26) นับ "บิลค้างไม่จ่าย" ตลอดประวัติ (all-time) — ใช้กับ consent audio-code gate
     *
     * ต่างจาก strikeCount(): ไม่จำกัดหน้าต่าง 3 วัน + ไม่ reset หลังการจ่าย = "ประวัติ" รวมทั้งหมด
     * นิยาม "บิลจริงที่ไม่จ่าย" เหมือน strikeCount: is_paid=false + มี unique_payment_amount_id (ออกบิล/QR จริง)
     *   + conversation_status=COMPLETED (จบรอบแล้ว) + ไม่ใช่ package_switch (เปลี่ยนแพคเกจ ไม่นับ)
     *
     * @param  string  $userId  PSID / LINE userId (match ทั้ง facebook_user_id + platform_user_id)
     */
    public function unpaidBillCountAllTime(string $userId): int
    {
        if ($userId === '') {
            return 0;
        }

        return FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->whereNull('conversation_state->cancellation_reason')
                    ->orWhere('conversation_state->cancellation_reason', '!=', 'package_switch');
            })
            ->count();
    }

    /**
     * 📢 คำเตือนแนบบิลใหม่ — คืน null ถ้ายังไม่เข้าเกณฑ์ (strike < 2)
     *
     * เรียกตอนสร้างบิล (Deep createPaymentBill / Celtic doStartCelticCrossFlow)
     * caller ต้อง setConversationState('troll_warning_shown', true) เมื่อได้ข้อความกลับ
     */
    public function warningForNewBill(FortuneReading $reading): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $userId = $reading->facebook_user_id ?: $reading->platform_user_id;
        if (empty($userId)) {
            return null;
        }

        // บิลใหม่ยังไม่ COMPLETED → strikeCount นับเฉพาะบิลเก่าที่ไม่จ่าย
        $strikes = $this->strikeCount((string) $userId);
        if ($strikes < self::MAX_STRIKES - 1) {
            return null;
        }

        $timeoutMinutes = FortuneReading::billTimeoutMinutes();
        $timeoutLabel = $timeoutMinutes >= 60
            ? intdiv($timeoutMinutes, 60).' ชั่วโมง'.($timeoutMinutes % 60 > 0 ? ' '.($timeoutMinutes % 60).' นาที' : '')
            : $timeoutMinutes.' นาที';

        Log::info('BillTrollGuard: แนบคำเตือนบิลที่ '.($strikes + 1), [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'user_id' => $userId,
            'strikes' => $strikes,
        ]);

        return "⚠️ *ประกาศจากระบบ*\n"
            ."เจ้าชะตาสร้างบิลโดยไม่ชำระมาแล้ว {$strikes} ครั้งภายใน ".self::STRIKE_WINDOW_DAYS." วัน\n"
            ."หากบิลนี้ไม่ชำระให้เสร็จสิ้นภายใน {$timeoutLabel} ระบบจะถือว่ามีเจตนาก่อกวน\n"
            ."และจะ*ระงับการใช้งานจากเพจถาวร*ค่ะ\n"
            .'(ถ้าโอนไม่เป็น/ติดขัด พิมพ์ "ช่วยหน่อย" แม่หมอช่วยได้ — หรือพิมพ์ "ยกเลิก" ก็ถือว่าไม่ชำระเช่นกันนะคะ) 🙏';
    }

    /**
     * 🔨 เช็คและแบนหลังบิลถูกยกเลิกโดยไม่จ่าย (เรียกจากทุกจุดที่ cancel บิล)
     *
     * เงื่อนไขแบน (ครบทุกข้อ):
     *   1. toggle เปิด + บิลนี้ไม่ได้จ่าย + ไม่ใช่ package_switch
     *   2. บิลนี้เคยแนบคำเตือน (troll_warning_shown) — ยุติธรรม: ไม่แบนคนที่ไม่เคยถูกเตือน
     *   3. strike รวมบิลนี้ ≥ 3
     *   4. ไม่ใช่ลูกค้าจริงที่เคยจ่ายใน 30 วัน (safety — log ให้แอดมินแทน)
     */
    public function maybeBanAfterUnpaidCancel(FortuneReading $reading): void
    {
        if (! $this->isEnabled() || $reading->is_paid) {
            return;
        }

        $reason = (string) ($reading->getConversationState('cancellation_reason') ?? '');
        if ($reason === 'package_switch') {
            return;
        }

        // ต้องเคยเห็นคำเตือนบนบิลใบนี้ก่อน — กันแบนย้อนหลังคนที่ไม่เคยถูกเตือน
        if (! (bool) $reading->getConversationState('troll_warning_shown')) {
            return;
        }

        $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id);
        if ($userId === '') {
            return;
        }

        $platform = $reading->platform
            ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

        // บิลใบนี้ถูก mark COMPLETED แล้วก่อนเรียก → strikeCount รวมใบนี้ด้วย
        $strikes = $this->strikeCount($userId);
        if ($strikes < self::MAX_STRIKES) {
            return;
        }

        // 🛟 Safety: ลูกค้าจริงที่เคยจ่ายใน 30 วัน → ไม่ auto-ban (แจ้งแอดมินแทน)
        $recentlyPaid = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', true)
            ->where('created_at', '>=', now()->subDays(self::PAID_CUSTOMER_GRACE_DAYS))
            ->exists();

        if ($recentlyPaid) {
            Log::warning('BillTrollGuard: เข้าเกณฑ์แบนแต่เป็นลูกค้าเคยจ่ายใน 30 วัน → ข้าม auto-ban (แอดมินรีวิว)', [
                'reading_id' => $reading->id,
                'user_id' => $userId,
                'platform' => $platform,
                'strikes' => $strikes,
            ]);

            return;
        }

        $banService = app(FortuneBanService::class);
        if ($banService->isBanned($platform, $userId)) {
            return; // แบนอยู่แล้ว — ไม่ทำซ้ำ
        }

        // 1) ส่งข้อความแจ้งครั้งสุดท้าย (best-effort — ต้องส่งก่อน FB block)
        try {
            $channelManager = app(\App\Services\FortuneChannelManager::class);
            $platformService = $channelManager->getPlatform($platform);
            if ($platformService) {
                $platformService->sendMessage($userId,
                    "🚫 *ระบบระงับการใช้งานถาวร*\n"
                    ."═══════════════════════\n"
                    .'เจ้าชะตาสร้างบิลโดยไม่ชำระครบ '.self::MAX_STRIKES.' ครั้งภายใน '.self::STRIKE_WINDOW_DAYS." วัน\n"
                    ."หลังจากระบบได้แจ้งเตือนแล้ว — ถือว่ามีเจตนาก่อกวน\n"
                    ."═══════════════════════\n"
                    .'หากเป็นความเข้าใจผิด กรุณาติดต่อแอดมินเพจค่ะ 🙏'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('BillTrollGuard: ส่งข้อความแจ้งแบนล้มเหลว (best-effort)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Bot-level ban ถาวร (ทุก platform)
        $banService->ban(
            $platform,
            $userId,
            null, // null = ถาวร
            'bill_troll: สร้างบิลไม่ชำระ '.$strikes.' ครั้งใน '.self::STRIKE_WINDOW_DAYS.' วัน (เตือนบิลที่ 3 แล้ว)',
            null,
            $reading->facebook_user_name
        );

        // 3) FB page block จริง — ห้ามทั้ง DM + คอมเมนต์บนเพจ (LINE ไม่มี API เทียบเท่า)
        if ($platform === 'facebook') {
            try {
                app(\App\Services\FacebookWebhookService::class)->blockPageUser($userId);
            } catch (\Throwable $e) {
                Log::error('BillTrollGuard: FB blockPageUser ล้มเหลว (bot-ban ยังทำงาน)', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::critical('🔨 BillTrollGuard: แบนถาวร — สร้างบิลไม่ชำระครบเกณฑ์ก่อกวน', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'user_id' => $userId,
            'platform' => $platform,
            'display_name' => $reading->facebook_user_name,
            'strikes' => $strikes,
            'fb_page_blocked' => $platform === 'facebook',
        ]);
    }
}
