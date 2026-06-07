<?php

namespace App\Services\Fortune;

use App\Models\FortuneConsentImage;
use App\Models\FortuneReading;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 📜 Trait: Consent Gate — กล่องกติกาก่อนจองคิว (2026-06-06)
 *
 * ใช้กับ FortuneConversationService — แทรกกล่องกติกา (รูป + คำเตือน + ปุ่มยืนยัน)
 * ก่อนสร้างบิลค่าครู "ทุกบิลเสียเงิน" (Celtic 99 + Deep 39)
 *
 * Flow:
 *   1. ลูกค้าเลือกแพ็กเกจ → doStartCelticCrossFlow / startDeepReadingFlow เรียก consentGateOrNull()
 *   2. ถ้ายังไม่ยอมรับ → ส่งกล่องกติกา (action 'consent_gate') + ตั้ง Cache pending
 *   3. ลูกค้ากด "พร้อมโอนค่าครู" → processMessage detect → handleConsentAcceptIfPending()
 *        → ตั้ง flag consent_ok → re-dispatch start flow (รอบนี้ผ่าน gate → ออก QR)
 *   4. ลูกค้ากด "ยังไม่พร้อม/ยกเลิก" → flow เดิมจับ → closeAllActiveConversations()
 *        → sendCancelConsentOrWakeup() เตือนสติ (แยกเจตนาเบี้ยว vs เหตุสุดวิสัย)
 *
 * "เด้งทุกครั้ง": Cache::pull() กิน flag consent_ok ทิ้งหลังผ่าน 1 ครั้ง → บิลถัดไปต้องยอมรับใหม่
 */
trait FortuneConsentGateTrait
{
    /** Cache key prefix — รออ่านกติกา (เก็บ tier) */
    protected const CONSENT_PENDING_PREFIX = 'fortune:consent_pending:';

    /** Cache key prefix — ยอมรับแล้ว (one-shot) */
    protected const CONSENT_OK_PREFIX = 'fortune:consent_ok:';

    /** TTL (วินาที) ของ flag consent */
    protected const CONSENT_TTL = 600;

    /**
     * 🔔 ตรวจ consent gate — เรียกจากจุดสร้างบิล (Celtic/Deep) ก่อน UPA generate
     *
     * @param  string  $tier  'celtic' | 'deep'
     * @return array|null consent gate response (ถ้ายังไม่ยอมรับ) / null (ผ่าน → สร้างบิลต่อ)
     */
    protected function consentGateOrNull(string $uid, string $tier, ?FortuneReading $reading = null): ?array
    {
        // ปิดทั้งระบบ → ไม่ขวาง flow เดิม
        if (! $this->settings->isConsentEnabled()) {
            return null;
        }

        if (empty($uid)) {
            return null;
        }

        // ลูกค้าจ่ายแล้ว / กำลังทำนาย → ห้ามเด้งกติกาคั่น (เคารพ paid customer)
        if (method_exists($this, 'hasPaidActiveReading') && $this->hasPaidActiveReading($uid)) {
            return null;
        }

        // เพิ่งกดยอมรับ (flag one-shot) → กิน flag + ผ่าน
        if (Cache::pull(self::CONSENT_OK_PREFIX.$uid)) {
            Log::info('Fortune: consent gate ผ่าน (เพิ่งยอมรับ)', [
                'user_id' => $uid,
                'tier' => $tier,
            ]);

            return null;
        }

        // ยังไม่ยอมรับ → จำ tier + ส่งกล่องกติกา
        Cache::put(self::CONSENT_PENDING_PREFIX.$uid, $tier, self::CONSENT_TTL);

        Log::info('Fortune: แสดงกล่องกติกาก่อนสร้างบิล', [
            'user_id' => $uid,
            'tier' => $tier,
            'reading_id' => $reading?->id,
        ]);

        return $this->buildConsentGateResponse($tier, $reading);
    }

    /**
     * 📜 สร้าง response กล่องกติกา (รูปสุ่ม + คำเตือน + 2 ปุ่ม)
     *
     * @param  string  $tier  'celtic' | 'deep'
     */
    protected function buildConsentGateResponse(string $tier, ?FortuneReading $reading = null, bool $reshow = false): array
    {
        // ราคาค่าครูตาม tier (ดึงจริง ไม่ฮาร์ดโค้ด)
        $price = $tier === 'celtic'
            ? (int) app(CelticCrossService::class)->getPrice()
            : (int) $this->getDeepReadingPrice();

        // รูปสุ่ม scope=consent (อาจไม่มี → ส่งแค่ text)
        // 🩹 (2026-06-07) $reshow=true → เด้งกล่องซ้ำเพราะลูกค้าพิมพ์ข้อความไม่ชัดที่กล่องกติกา
        //   → ไม่ส่งรูปซ้ำ (กัน spam + ประหยัดโทเค็น) ส่งแค่ข้อความย้ำ + ปุ่ม
        $imageUrl = null;
        if (! $reshow) {
            try {
                $img = FortuneConsentImage::pickByStrategy(
                    $this->settings->fortune_consent_pick_strategy ?? 'random',
                    FortuneConsentImage::SCOPE_CONSENT
                );
                if ($img) {
                    $imageUrl = $img->image_url;
                    $img->recordSend();
                }
            } catch (\Throwable $e) {
                Log::warning('Fortune: consent image pick failed (non-blocking)', ['error' => $e->getMessage()]);
            }
        }

        // 🩹 (2026-06-07) reshow → ข้อความสั้นย้ำให้กดปุ่ม (ลูกค้าเห็นกติกาเต็มไปแล้วรอบแรก)
        $message = $reshow
            ? "🙏 ก่อนเริ่มดูดวง รบกวนกดปุ่มยืนยันด้านล่างก่อนนะคะ — \"💎 พร้อมโอนค่าครู {$price}฿\" หรือ \"🙏 ยังไม่พร้อม\""
            : $this->settings->getConsentText();

        return [
            'action' => 'consent_gate',
            'message' => $message,
            'consent_image_url' => $imageUrl,
            'quick_replies' => [
                // 🛡️ ต้องมีทั้ง 'text' (LINE ใช้) และ 'payload' (FB ใช้ payload ?? title — ไม่อ่าน text)
                ['content_type' => 'text', 'title' => "💎 พร้อมโอนค่าครู {$price}฿", 'text' => 'พร้อมโอนค่าครูแล้ว', 'payload' => 'พร้อมโอนค่าครูแล้ว'],
                ['content_type' => 'text', 'title' => '🙏 ยังไม่พร้อม', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก'],
            ],
            'show_quick_replies' => true,
            'block_followups' => true,
            'reading' => $reading,
        ];
    }

    /**
     * ✅ ตรวจ + จัดการเมื่อลูกค้ากด "พร้อมโอนค่าครู" จากกล่องกติกา
     *
     * เรียกจาก processMessage ต้นทาง (หลัง in-prediction guard) — มี Cache pending guard
     * กัน over-match (ทำงานเฉพาะตอนมีกล่องกติกาค้างอยู่)
     *
     * @return array|null start-flow response (ถ้า accept) / null (ไม่เกี่ยว → flow ปกติ)
     */
    protected function handleConsentAcceptIfPending(string $uid, string $messageText, ?array $userProfile = null): ?array
    {
        $pendingTier = Cache::get(self::CONSENT_PENDING_PREFIX.$uid);
        if (empty($pendingTier)) {
            return null; // ไม่มีกล่องกติกาค้าง → ไม่เกี่ยว
        }

        if (! $this->matchesConsentAccept($messageText)) {
            // 🩹 (2026-06-07) Consent-gate fall-through guard — กันบั๊ก "บอทขอวันเกิด"
            //   ราก: startDeepReadingFlow สร้าง reading ด้วย default status=collecting_birthdate
            //   (FortuneConversationService:5427) + doStartCelticCrossFlow → consentGateOrNull
            //   return ก่อน update status เป็น celtic (CelticCrossConversationTrait:708)
            //   → reading ค้างที่ collecting_birthdate. ถ้าลูกค้าพิมพ์ข้อความที่ไม่ใช่ยอมรับ/
            //   ไม่ใช่ยกเลิก ที่กล่องกติกา → router ตามสถานะ → handleBirthdateInput → ขอวันเกิด
            //   (ผิด! Celtic = card-first ไม่ใช้วันเกิด) — เคสจริง: ลูกค้าอามร reading 5204
            //   พิมพ์ "งั้นรอคนโอนออกมาก่อนนะคะ" → บอทขอวันเกิด
            //
            //   - cancel/ปฏิเสธ (ยกเลิก/ไม่เอา/เลิก/หยุด) → return null ให้ isCancelRequest
            //     (processMessage:1989) จัดการ cancel + คำเตือนตามเดิม
            //   - ข้อความอื่น → เด้งกล่องกติกาซ้ำ (ย้ำให้กดปุ่ม) ไม่ปล่อย leak ไปขอวันเกิด
            if ($this->isCancelRequest($messageText)) {
                return null;
            }

            Log::info('Fortune: consent gate ค้าง + ข้อความไม่ชัด → เด้งกล่องกติกาซ้ำ (กัน leak ไปขอวันเกิด)', [
                'user_id' => $uid,
                'tier' => $pendingTier,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            return $this->buildConsentGateResponse($pendingTier, null, true);
        }

        // ตั้ง flag one-shot + เคลียร์ pending
        Cache::put(self::CONSENT_OK_PREFIX.$uid, true, self::CONSENT_TTL);
        Cache::forget(self::CONSENT_PENDING_PREFIX.$uid);

        Log::info('Fortune: ลูกค้ายอมรับกติกา → ดำเนินการสร้างบิล', [
            'user_id' => $uid,
            'tier' => $pendingTier,
        ]);

        // re-dispatch — startDeepReadingFlow route ตาม tier (celtic/deep)
        //   รอบนี้ consentGateOrNull จะ Cache::pull(consent_ok) เจอ → ผ่าน → ออก QR
        return $this->startDeepReadingFlow($uid, $userProfile, $pendingTier, null);
    }

    /**
     * ตรวจว่าข้อความ = การกดยอมรับกติกาหรือไม่
     */
    protected function matchesConsentAccept(string $messageText): bool
    {
        $t = mb_strtolower(trim($messageText));
        if ($t === '') {
            return false;
        }

        // 🛡️ กัน over-match: "ยังไม่พร้อม(โอน)" / "ไม่พร้อม" / "ยกเลิก" = ปฏิเสธ ไม่ใช่ยอมรับ
        //   (keyword 'พร้อมโอน' เป็น substring ของ 'ยังไม่พร้อมโอน' → ต้องตัดออกก่อน)
        foreach (['ไม่พร้อม', 'ยังไม่', 'ยกเลิก', 'ไม่เอา', 'ไว้ก่อน', 'ไว้คราวหน้า'] as $neg) {
            if (mb_strpos($t, $neg) !== false) {
                return false;
            }
        }

        foreach (['พร้อมโอนค่าครู', 'พร้อมโอน', 'ยืนยันพร้อมโอน', '__consent_ok__'] as $kw) {
            if (mb_strpos($t, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 💭 ส่งคำเตือน/เตือนสติเมื่อลูกค้ายกเลิกบิล — แยกเจตนา
     *
     * เรียกจาก closeAllActiveConversations() แทน loop wakeup เดิม
     *
     * - เหตุสุดวิสัย (โอนไม่ได้/แอพมีปัญหา) หรือ caller flow-internal ($cancelReasonText=null)
     *     → wakeup เดิม (นุ่มนวล) — "ยกเลิกปกติ"
     * - เจตนาเบี้ยว/ฝืนกติกา → รูป (scope cancel) + ข้อความเตือนแรง (ครั้งเดียว/บิล)
     *
     * @param  string|null  $cancelReasonText  ข้อความที่ลูกค้าพิมพ์ตอนยกเลิก (null = ไม่ทราบ/flow internal)
     */
    protected function sendCancelConsentOrWakeup(FortuneReading $cancelledReading, ?string $cancelReasonText): void
    {
        $platform = $cancelledReading->platform ?? 'facebook';
        $userId = $cancelledReading->platform_user_id ?? $cancelledReading->facebook_user_id;
        if (empty($userId)) {
            return;
        }

        $platformService = app(FortuneChannelManager::class)->getPlatform($platform);
        if (! $platformService) {
            return;
        }

        // เจตนาเบี้ยวหรือไม่ — ต้องมี cancelReasonText (= ลูกค้ากดยกเลิกจริง) + ไม่ใช่เหตุสุดวิสัย
        $intentional = $cancelReasonText !== null;
        $forceMajeure = $intentional
            && method_exists($this, 'looksLikeNeedPaymentHelp')
            && $this->looksLikeNeedPaymentHelp($cancelReasonText);
        $alreadyWarned = (bool) $cancelledReading->getConversationState('cancel_warning_sent');

        $shouldWarnHard = $intentional && ! $forceMajeure && ! $alreadyWarned;

        // 🔴 เจตนาเบี้ยว → รูป (scope cancel) + ข้อความเตือนแรง
        //   helper เช็ค consent_cancel_enabled เอง → ปิด/fail = false → fallthrough wakeup เดิม
        if ($shouldWarnHard && FortuneConsentImage::deliverCancelWarning($platformService, (string) $userId)) {
            $cancelledReading->setConversationState('cancel_warning_sent', true);

            Log::info('Fortune: ส่งคำเตือนยกเลิก (เจตนาเบี้ยว) + รูป', [
                'user_id' => $userId,
                'reading_id' => $cancelledReading->id,
            ]);

            return;
        }

        // 🟢 เหตุสุดวิสัย / ปิดระบบ / flow-internal → wakeup เดิม (นุ่มนวล)
        $wakeupMessage = FortuneReading::buildCancelWakeupMessage($cancelledReading, 'user_cancelled');
        $platformService->sendMessage($userId, $wakeupMessage);

        if ($forceMajeure) {
            Log::info('Fortune: ยกเลิกเหตุสุดวิสัย → wakeup ปกติ (ไม่เตือนแรง)', [
                'user_id' => $userId,
                'reading_id' => $cancelledReading->id,
            ]);
        }
    }
}
