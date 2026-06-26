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
 *   3. ลูกค้ากด "พร้อมบูชาครู" → processMessage detect → handleConsentAcceptIfPending()
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

    /** 🔊 (2026-06-26) Cache key — รหัสยืนยันจากเสียงกติกา (audio-code gate) */
    protected const CONSENT_CODE_PREFIX = 'fortune:consent_code:';

    /** 🔊 (2026-06-26) Cache key — URL ไฟล์เสียงรวม (กติกา+รหัส) ของรอบนี้ */
    protected const CONSENT_AUDIO_URL_PREFIX = 'fortune:consent_audio_url:';

    /** 🔊 (2026-06-26) Cache key — ความยาวเสียงรวม (ms) สำหรับ LINE audio metadata (ต้องตรงกับ FortuneSystemVoiceService) */
    protected const CONSENT_AUDIO_DUR_PREFIX = 'fortune:consent_audio_dur:';

    /** 🔊 (2026-06-26) Cache key — จำนวนครั้งที่พิมพ์รหัสผิด */
    protected const CONSENT_CODE_ATTEMPTS_PREFIX = 'fortune:consent_code_attempts:';

    /** 🔊 (2026-06-26) พิมพ์รหัสผิดกี่ครั้งแล้วเฉลยรหัสให้ (กันลูกค้าติด) */
    protected const CONSENT_CODE_MAX_ATTEMPTS = 3;

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

        // 🔔 (2026-06-20) marker สำหรับ fortune:flow-nudge — กระตุ้น "กดพร้อมบูชาครู" ถ้าเงียบ 1 นาที
        //   / ออกจากโฟลว์ถ้าเงียบ 30 นาที (ยังไม่สร้างบิล). เก็บ tier ไว้โชว์ราคาให้ถูก
        if ($reading) {
            $reading->setConversationState('consent_gate_shown_at', now()->toIso8601String());
            $reading->setConversationState('consent_gate_tier', $tier);
            $reading->setConversationState('flow_nudge_sent_at', null);
        }

        // 🪧 (2026-06-25) อยู่หน้ากล่องกติกา = pay-intent ชัด (กำลังจะกดบูชาครู+โอน) → ถ้าลูกค้า
        //   โอนแล้วส่งสลิปมา "ก่อนกดปุ่ม/ไม่พิมพ์โอนแล้ว" → ตรวจ+เปิดให้เอง ไม่ตกร่อง
        //   (ช่วงระหว่าง consent gate → กดพร้อมบูชาครู ยังไม่มีบิล → handler อื่นจับสลิปไม่ได้)
        $this->markAwaitingPaymentSlip($uid, 'consent_gate');

        Log::info('Fortune: แสดงกล่องกติกาก่อนสร้างบิล', [
            'user_id' => $uid,
            'tier' => $tier,
            'reading_id' => $reading?->id,
        ]);

        // 🔊 (2026-06-26) โหมดบังคับฟังเสียงกติกา + กรอกรหัสท้ายคลิป
        //   เปิดเฉพาะเมื่อเข้าเกณฑ์บิลค้างไม่จ่าย (shouldUseAudioCode) — 0 = ทุกคน / N = เฉพาะคนค้างบิล >= N
        //   สร้างเสียง/รหัสไม่สำเร็จ → degrade เป็นกล่องกติกาปกติ (ห้ามบล็อกลูกค้า)
        if ($this->shouldUseAudioCode($uid)) {
            $codeGate = $this->buildConsentAudioCodeGate($uid, $tier, $reading);
            if ($codeGate !== null) {
                return $codeGate;
            }
        }

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
            ? "🙏 ก่อนเริ่มดูดวง รบกวนกดปุ่มยืนยันด้านล่างก่อนนะคะ — \"🙏 พร้อมบูชาครู {$price}฿\" หรือ \"🙏 ยังไม่พร้อม\""
            : $this->settings->getConsentText();

        return [
            'action' => 'consent_gate',
            'message' => $message,
            'consent_image_url' => $imageUrl,
            'quick_replies' => [
                // 🛡️ ต้องมีทั้ง 'text' (LINE ใช้) และ 'payload' (FB ใช้ payload ?? title — ไม่อ่าน text)
                ['content_type' => 'text', 'title' => "🙏 พร้อมบูชาครู {$price}฿", 'text' => 'พร้อมบูชาครูแล้ว', 'payload' => 'พร้อมบูชาครูแล้ว'],
                ['content_type' => 'text', 'title' => '🙏 ยังไม่พร้อม', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก'],
            ],
            'show_quick_replies' => true,
            'block_followups' => true,
            'reading' => $reading,
        ];
    }

    /**
     * ✅ ตรวจ + จัดการเมื่อลูกค้ากด "พร้อมบูชาครู" จากกล่องกติกา
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

        // 🔊 (2026-06-26) ถ้ากล่องกติกานี้เป็นโหมด "บังคับรหัสเสียง" → ต้องพิมพ์รหัสตรงก่อนจึงสร้างบิล
        //   (มี fallback กันลูกค้าติด: ผิด 3 ครั้ง / "ขอรหัส-ฟังไม่ได้" → เฉลยรหัส ; ยกเลิก/ปฏิเสธ → ปิด)
        if ($this->consentAudioCodeActiveFor($uid)) {
            return $this->handleConsentAudioCodeReply($uid, $messageText, $pendingTier, $userProfile);
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

            // 🙏 (2026-06-11) Soft decline (ไม่ดูแล้ว/ไม่เอา/ไว้ก่อน ฯลฯ) → ปิดทันที ไม่ตื้อ
            //   เคสจริง (FB 27004874569110137 / R5800): ลูกค้าบอก "ผมไม่ดูแล้ว" แต่บอท
            //   เด้งกล่องกติกาซ้ำอีก — ผิดกฎ "ลูกค้าปฏิเสธ ห้ามโน้มน้าว"
            if (method_exists($this, 'looksLikeSoftDeclineDuringPayment')
                && $this->looksLikeSoftDeclineDuringPayment($messageText)) {
                Cache::forget(self::CONSENT_PENDING_PREFIX.$uid);
                if (method_exists($this, 'closeAllActiveConversations')) {
                    $this->closeAllActiveConversations($uid);
                }

                Log::info('Fortune: consent gate — ลูกค้าปฏิเสธ (soft decline) → ปิด ไม่ตื้อ', [
                    'user_id' => $uid,
                    'text_preview' => mb_substr($messageText, 0, 40),
                ]);

                return [
                    'action' => 'cancelled',
                    'message' => "🙏 รับทราบค่ะ ไม่เป็นไรเลยนะคะ\n\nไว้พร้อมเมื่อไหร่ พิมพ์ \"ดูดวง\" ทักมาหาแม่หมอได้เสมอค่ะ ✨",
                    'reading' => null,
                ];
            }

            // 🆕 (2026-06-24) ลูกค้าเห็นกติกาแล้ว + เลือกแพ็กเกจแล้ว แต่พิมพ์ข้อความที่
            //   "ไม่ใช่ยอมรับชัด และไม่ใช่ปฏิเสธ" — ส่วนใหญ่ = สับสน/ทำไม่เป็น/ถามวิธี (ลูกค้าสูงอายุ)
            //   เคสจริง PSID 26341241742179291 (ชายเกิด 2498): "ทำไม่ถูกครับ ยังไม่เข้าใจ"
            //   → เดิมเด้งกล่องกติกาเดิมซ้ำ = สิ่งเดียวที่เขาไม่เข้าใจอยู่แล้ว → ลูปจนเลิก (เสียลูกค้า)
            //   owner decision (2026-06-24): ไม่ใช่ปฏิเสธ → ตัดเข้าสร้างบิล+QR เลย
            //   (QR + เลขบัญชี = คำตอบของ "ทำไม่เป็น" + เห็นกติกา 1 รอบแล้ว = ยินยอมโดยปริยาย)
            //
            //   🛡️ Safety: กรอง "ลังเล/ยังไม่พร้อม" ที่ isCancelRequest/softDecline จับไม่ติด
            //      (เช่น "ยังไม่พร้อม" หลุด 'ยัง'-exclusion ใน looksLikeSoftDeclineDuringPayment)
            //      + ข้อความว่าง/แนบรูป → คงพฤติกรรมเดิม (เด้งกล่องซ้ำ) ไม่ตัดบิลให้คนยังไม่พร้อม
            //      (เคารพกฎ "ลูกค้าปฏิเสธ ห้ามตื้อ"). hesitation จับเฉพาะคำกริยา "ความพร้อม/จ่าย"
            //      — ไม่ชน "ยังไม่เข้าใจ/ยังไม่รู้" (= สับสน ต้องช่วย = ตัดบิล)
            if (mb_strlen(trim($messageText)) === 0 || $this->looksLikeConsentHesitation($messageText)) {
                Log::info('Fortune: consent gate ค้าง + ลังเล/ยังไม่พร้อม/ว่าง → เด้งกล่องกติกาซ้ำ (ไม่ตัดบิล)', [
                    'user_id' => $uid,
                    'tier' => $pendingTier,
                    'text_preview' => mb_substr($messageText, 0, 40),
                ]);

                return $this->buildConsentGateResponse($pendingTier, null, true);
            }

            Log::info('Fortune: consent gate ค้าง + ไม่ปฏิเสธ (สับสน/ถามวิธี) → ตัดเข้าสร้างบิล (auto-consent)', [
                'user_id' => $uid,
                'tier' => $pendingTier,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            // fall-through ↓ → สร้างบิล (เส้นทางเดียวกับ accept ชัด)
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
     * 🔊 (2026-06-26) เปิดโหมดบังคับฟังเสียงกติกา + รหัสยืนยันหรือไม่ (toggle + consent ต้องเปิด)
     */
    protected function consentAudioCodeEnabled(): bool
    {
        return (bool) ($this->settings->enable_consent_audio_code ?? false)
            && $this->settings->isConsentEnabled();
    }

    /**
     * 🔊 (2026-06-26) ควร "บังคับรหัสเสียง" กับ uid นี้ไหม — ตามเกณฑ์บิลค้างไม่จ่าย
     *
     * - toggle ปิด → false
     * - consent_audio_code_min_unpaid_bills = 0 → true (บังคับทุกบิลทุกคน)
     * - N > 0 → true เฉพาะลูกค้าที่มีประวัติบิลค้างไม่จ่าย >= N (ลูกค้าใหม่/ดี = ไม่บังคับ)
     * - นับไม่ได้/ไม่มี uid → false (degrade ปลอดภัย ไม่เพิ่ม friction ให้คนที่ระบุตัวไม่ได้)
     */
    protected function shouldUseAudioCode(string $uid): bool
    {
        if (! $this->consentAudioCodeEnabled()) {
            return false;
        }

        $threshold = (int) ($this->settings->consent_audio_code_min_unpaid_bills ?? 0);
        if ($threshold <= 0) {
            return true; // 0 = ทุกบิลทุกคน
        }

        if (empty($uid)) {
            return false;
        }

        try {
            $unpaid = app(\App\Services\Fortune\BillTrollGuardService::class)->unpaidBillCountAllTime($uid);

            return $unpaid >= $threshold;
        } catch (\Throwable $e) {
            Log::warning('🔊 ConsentCode: นับบิลค้างไม่สำเร็จ → ไม่บังคับรหัส (degrade)', ['user_id' => $uid, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * 🔊 มีรหัสค้างอยู่สำหรับ uid นี้ไหม (= กล่องกติกาถูกแสดงในโหมดรหัสเสียง ไม่ใช่ degrade)
     */
    protected function consentAudioCodeActiveFor(string $uid): bool
    {
        return $this->consentAudioCodeEnabled()
            && ! empty($uid)
            && Cache::get(self::CONSENT_CODE_PREFIX.$uid) !== null;
    }

    /**
     * 🔊 สร้างกล่องกติกาแบบ "เสียงกติกา + รหัสท้ายคลิป"
     *
     * - มีรหัส+เสียงค้างอยู่แล้ว (re-entry/nudge) → reuse (ไม่สุ่มรหัสใหม่ กันรหัสที่ลูกค้าได้ยินเพี้ยน)
     * - ยังไม่มี → สุ่มรหัส 4 หลัก + เจนเสียงรวม (provider เลือกได้) + เก็บรหัส
     *
     * @return array|null response / null (เสียงสร้างไม่สำเร็จ → caller degrade เป็นกล่องปกติ)
     */
    protected function buildConsentAudioCodeGate(string $uid, string $tier, ?FortuneReading $reading = null): ?array
    {
        try {
            // reuse ถ้ามีอยู่แล้ว — กันรหัสเปลี่ยนตอน consentGateOrNull ถูกเรียกซ้ำ (nudge/re-entry)
            $existingCode = Cache::get(self::CONSENT_CODE_PREFIX.$uid);
            $existingUrl = Cache::get(self::CONSENT_AUDIO_URL_PREFIX.$uid);
            if (! empty($existingCode) && ! empty($existingUrl)) {
                return $this->consentAudioCodeGateResponse((string) $existingUrl, $reading,
                    (int) Cache::get(self::CONSENT_AUDIO_DUR_PREFIX.$uid, 0));
            }

            // 🔒 กันสร้างซ้อน (double-tap ปุ่มแพคเกจก่อนเสียงเจนเสร็จ → 2 รหัสแข่งกัน)
            $lock = Cache::lock('fortune:consent_code_gen:'.$uid, 30);
            if (! $lock->get()) {
                usleep(500000);
                $code2 = Cache::get(self::CONSENT_CODE_PREFIX.$uid);
                $url2 = Cache::get(self::CONSENT_AUDIO_URL_PREFIX.$uid);
                if (! empty($code2) && ! empty($url2)) {
                    return $this->consentAudioCodeGateResponse((string) $url2, $reading,
                        (int) Cache::get(self::CONSENT_AUDIO_DUR_PREFIX.$uid, 0));
                }

                return null; // อีก request กำลังเจนอยู่ + ยังไม่เสร็จ → degrade เป็นกล่องปกติ (ไม่ค้าง)
            }

            try {
                $code = (string) random_int(1000, 9999);
                $provider = $this->settings->consent_audio_code_voice_provider ?: 'minimax';

                $audioUrl = (new \App\Services\FortuneSystemVoiceService($this->settings))
                    ->buildConsentAudioWithCode($code, $uid, $provider);

                if (empty($audioUrl)) {
                    Log::warning('🔊 ConsentCode: สร้างเสียง+รหัสไม่สำเร็จ → ใช้กล่องกติกาปกติแทน', ['user_id' => $uid]);

                    return null;
                }

                $durMs = (int) Cache::get(self::CONSENT_AUDIO_DUR_PREFIX.$uid, 0);

                // เก็บรหัส + url + reset attempts (Cache + reading state เพื่อความทน)
                Cache::put(self::CONSENT_CODE_PREFIX.$uid, $code, self::CONSENT_TTL);
                Cache::put(self::CONSENT_AUDIO_URL_PREFIX.$uid, $audioUrl, self::CONSENT_TTL);
                Cache::put(self::CONSENT_CODE_ATTEMPTS_PREFIX.$uid, 0, self::CONSENT_TTL);
                if ($reading) {
                    $reading->setConversationState('consent_audio_code', $code);
                    $reading->setConversationState('consent_audio_url', $audioUrl);
                }

                Log::info('🔊 ConsentCode: แสดงกล่องกติกา+รหัสเสียง', ['user_id' => $uid, 'tier' => $tier]);

                return $this->consentAudioCodeGateResponse($audioUrl, $reading, $durMs);
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::warning('🔊 ConsentCode: buildConsentAudioCodeGate exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 🔊 response กล่องกติกาโหมดรหัสเสียง (ข้อความ + URL เสียงรวม) — ส่งผ่าน ChannelManager
     */
    protected function consentAudioCodeGateResponse(string $audioUrl, ?FortuneReading $reading = null, int $durationMs = 0): array
    {
        return [
            'action' => 'consent_gate',
            'message' => "🔊 ก่อนเริ่มดูดวง รบกวนกด ▶️ ฟังเสียงกติกาให้จบนะคะ\n\n"
                ."📌 ท้ายคลิปจะมี *รหัสยืนยัน 4 หลัก* — พิมพ์รหัสนั้นกลับมาในแชท แม่หมอจะส่ง QR ให้ทันทีค่ะ\n"
                .'🙏 ฟังไม่ชัด/ฟังไม่ได้ กดปุ่ม "ขอรหัส" — หรือ "ยกเลิก" ได้เลยนะคะ',
            'consent_audio_url' => $audioUrl,
            // ความยาวเสียง (LINE ใช้โชว์ metadata) — เผื่อสูง กันแสดงสั้นกว่าจริง (รหัสอยู่ท้ายคลิป)
            'consent_audio_duration_ms' => $durationMs > 0 ? $durationMs : 180000,
            // 🛡️ (anti-trap) ปุ่มกดหนีได้เสมอ — "ขอรหัส" (เฉลยรหัส) / "ยกเลิก" (ปิด)
            //   กันลูกค้าสูงอายุ/พิมพ์ไทยไม่ถนัดติดลูป (บทเรียน 2026-06-24 lost customer)
            'quick_replies' => [
                ['content_type' => 'text', 'title' => '🔑 ขอรหัส (ฟังไม่ได้)', 'text' => 'ขอรหัส', 'payload' => 'ขอรหัส'],
                ['content_type' => 'text', 'title' => '🙏 ยกเลิก', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก'],
            ],
            'show_quick_replies' => true,
            'block_followups' => true,
            'reading' => $reading,
        ];
    }

    /**
     * 🔊 จัดการคำตอบลูกค้าตอนอยู่หน้ากล่อง "รหัสเสียง"
     *
     * - รหัสตรง → ผ่าน → สร้างบิล (เส้นทางเดียวกับ accept)
     * - ยกเลิก/ปฏิเสธนุ่ม → ปิด ไม่ตื้อ (เคารพกฎห้ามตื้อ)
     * - "ขอรหัส"/ฟังไม่ได้/ทำไม่เป็น หรือผิดครบ N ครั้ง → เฉลยรหัสเป็นข้อความ (กันลูกค้าติด — บทเรียน lost customer สูงอายุ)
     * - ผิด (ยังไม่ครบ N) → ย้ำให้ฟังท้ายคลิป
     */
    protected function handleConsentAudioCodeReply(string $uid, string $messageText, string $pendingTier, ?array $userProfile = null): ?array
    {
        $stored = (string) Cache::get(self::CONSENT_CODE_PREFIX.$uid, '');
        $digits = preg_replace('/\D/', '', $messageText);

        // 1) ยกเลิกชัดเจน ("ยกเลิก") → ปิดให้สุด (clear + forget pending) แล้วปล่อย cancel flow เดิม (upstream)
        if ($this->isCancelRequest($messageText)) {
            $this->clearConsentAudioCode($uid);
            Cache::forget(self::CONSENT_PENDING_PREFIX.$uid);

            return null;
        }

        // 2) ✅ รหัสตรง → ผ่าน → สร้างบิล (เหมือน accept tail)
        if ($stored !== '' && $digits === $stored) {
            $this->clearConsentAudioCode($uid);
            Cache::put(self::CONSENT_OK_PREFIX.$uid, true, self::CONSENT_TTL);
            Cache::forget(self::CONSENT_PENDING_PREFIX.$uid);

            Log::info('🔊 ConsentCode: รหัสถูกต้อง → สร้างบิล', ['user_id' => $uid, 'tier' => $pendingTier]);

            return $this->startDeepReadingFlow($uid, $userProfile, $pendingTier, null);
        }

        // 3) ขอเฉลย/ฟังไม่ได้/ทำไม่เป็น *หรือ* กดปุ่ม "พร้อมบูชาครู" (เก่า/จาก flow-nudge) → เฉลยรหัส (กันติด)
        //   🛡️ ลูกค้ากดปุ่มยอมรับ = ตั้งใจไปต่อแต่ยังไม่รู้รหัส → เฉลยให้ ไม่นับว่าพิมพ์ผิด (กันลูปคำสั่งขัดกัน)
        //   ตรวจ help/accept *ก่อน* soft-decline — ข้อความกำกวม "ฟังไม่ได้ ขอผ่าน" ต้องได้รหัส ไม่ใช่ปิดเงียบ
        if ($this->looksLikeConsentCodeHelp($messageText) || $this->matchesConsentAccept($messageText)) {
            Log::info('🔊 ConsentCode: ขอเฉลย/ฟังไม่ได้/กดปุ่มยอมรับ → เฉลยรหัส', ['user_id' => $uid]);

            return $this->revealConsentCodeResponse($stored);
        }

        // 4) ปฏิเสธนุ่ม (ไม่เอา/ไว้ก่อน/ยังไม่พร้อม) → ปิด ไม่ตื้อ
        if (method_exists($this, 'looksLikeSoftDeclineDuringPayment')
            && $this->looksLikeSoftDeclineDuringPayment($messageText)) {
            $this->clearConsentAudioCode($uid);
            Cache::forget(self::CONSENT_PENDING_PREFIX.$uid);
            if (method_exists($this, 'closeAllActiveConversations')) {
                $this->closeAllActiveConversations($uid);
            }

            return [
                'action' => 'cancelled',
                'message' => "🙏 รับทราบค่ะ ไม่เป็นไรเลยนะคะ\n\nไว้พร้อมเมื่อไหร่ พิมพ์ \"ดูดวง\" ทักมาได้เสมอค่ะ ✨",
                'reading' => null,
            ];
        }

        // 5) ผิด → นับครั้ง ; ครบ N → เฉลย (กันติด) ; ไม่ครบ → ย้ำ
        $attempts = (int) Cache::get(self::CONSENT_CODE_ATTEMPTS_PREFIX.$uid, 0) + 1;
        Cache::put(self::CONSENT_CODE_ATTEMPTS_PREFIX.$uid, $attempts, self::CONSENT_TTL);

        if ($attempts >= self::CONSENT_CODE_MAX_ATTEMPTS && $stored !== '') {
            Log::info('🔊 ConsentCode: ผิดครบ '.$attempts.' ครั้ง → เฉลยรหัส (กันติด)', ['user_id' => $uid]);

            return $this->revealConsentCodeResponse($stored);
        }

        return [
            'action' => 'consent_gate',
            'message' => "🙏 รหัสยังไม่ถูกนะคะ — ลองกด ▶️ ฟังเสียงกติกา *ท้ายคลิป* อีกครั้ง แล้วพิมพ์รหัส 4 หลักค่ะ\n"
                .'(ฟังไม่ได้จริง ๆ พิมพ์ "ขอรหัส" แม่หมอจะบอกให้ค่ะ)',
            'show_quick_replies' => false,
            'block_followups' => true,
            'reading' => null,
        ];
    }

    /**
     * 🔊 เฉลยรหัสเป็นข้อความ (anti-trap) — คงสถานะ pending ให้ลูกค้าพิมพ์รหัสต่อ
     */
    protected function revealConsentCodeResponse(string $code): array
    {
        $code = preg_replace('/\D/', '', (string) $code);
        if ($code === '') {
            return [
                'action' => 'consent_gate',
                'message' => '🙏 ขอโทษค่ะ ระบบเสียงมีปัญหาชั่วคราว — พิมพ์ "ดูดวง" อีกครั้งเพื่อเริ่มใหม่นะคะ',
                'show_quick_replies' => false,
                'reading' => null,
            ];
        }

        return [
            'action' => 'consent_gate',
            'message' => "🔑 รหัสยืนยันของเจ้าชะตาคือ *{$code}* ค่ะ\n"
                .'พิมพ์เลข 4 หลักนี้กลับมาในแชท แม่หมอจะส่ง QR ให้ทันทีนะคะ 🙏',
            'show_quick_replies' => false,
            'block_followups' => true,
            'reading' => null,
        ];
    }

    /**
     * 🔊 keyword: ลูกค้าขอเฉลยรหัส / ฟังเสียงไม่ได้ / ทำไม่เป็น
     */
    protected function looksLikeConsentCodeHelp(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }
        foreach (['ขอรหัส', 'ขอเลข', 'ไม่ได้ยิน', 'ฟังไม่ได้', 'ฟังไม่ออก', 'ไม่มีเสียง', 'เปิดเสียงไม่ได้',
            'หูไม่ดี', 'ทำไม่เป็น', 'ทำไม่ถูก', 'ไม่เข้าใจ', 'รหัสอะไร', 'ไม่เห็นรหัส', 'เสียงไม่ดัง'] as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🔊 ล้างรหัส + url + attempts
     */
    protected function clearConsentAudioCode(string $uid): void
    {
        Cache::forget(self::CONSENT_CODE_PREFIX.$uid);
        Cache::forget(self::CONSENT_AUDIO_URL_PREFIX.$uid);
        Cache::forget(self::CONSENT_CODE_ATTEMPTS_PREFIX.$uid);
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
        foreach (['ไม่พร้อม', 'ยังไม่', 'ยกเลิก', 'ไม่เอา', 'ไว้ก่อน', 'ไว้คราวหน้า', 'ไม่ตกลง', 'ไม่ยอมรับ', 'ไม่จ่าย', 'ไม่โอน'] as $neg) {
            if (mb_strpos($t, $neg) !== false) {
                return false;
            }
        }

        // 🩹 (2026-06-11) ขยาย accept — ลูกค้าพิมพ์เอง (ไม่กดปุ่ม) + typo เยอะ
        //   เคสจริง (FB 27004874569110137): "พร้อมค่าครู99฿เลขบัญชีด้วยครับ" — ไม่มีคำว่า
        //   "โอน" → ไม่ match → เด้งกล่องกติกาวน 3 รอบจนลูกค้าเลิกดู ทั้งที่ขอเลขบัญชีจะจ่ายแล้ว
        $acceptKeywords = [
            // 🙏 (2026-06-13) ปุ่มใหม่ "พร้อมบูชาครู" + คง 'พร้อมโอนค่าครู' (ปุ่มเก่าในประวัติแชทยังกดได้)
            'พร้อมบูชาครู', 'พร้อมบูชา',
            'พร้อมโอนค่าครู', 'พร้อมโอน', 'ยืนยันพร้อมโอน', '__consent_ok__',
            // ขอช่องทางจ่าย = ตั้งใจจ่ายแล้ว (gated ด้วย consent pending — ไม่ over-match บริบทอื่น)
            'เลขบัญชี', 'ขอบัญชี', 'เลขบช', 'qr', 'คิวอาร์', 'สแกนจ่าย',
            'โอนเลย', 'โอนยังไง', 'โอนที่ไหน', 'จ่ายเลย', 'จ่ายยังไง', 'ชำระเงิน',
            // คำตอบรับทั่วไปที่กล่องกติกา
            'เอาเลย', 'เริ่มเลย', 'จัดเลย', 'ตกลง', 'ยอมรับ', 'โอเค',
        ];
        foreach ($acceptKeywords as $kw) {
            if (mb_strpos($t, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        // "พร้อม" + บริบทค่าครู/ราคา/จ่าย — เช่น "พร้อมค่าครู99", "พร้อมจ่ายครับ"
        if (mb_strpos($t, 'พร้อม') !== false
            && preg_match('/ครู|99|39|โอน|จ่าย|เริ่ม|บัญชี/u', $t)) {
            return true;
        }

        // "พร้อม(แล้ว)" สั้นๆ เดี่ยวๆ
        if (preg_match('/^พร้อม(แล้ว)?(ค่ะ|ครับ|คะ|จ้า|เลย)?$/u', $t)) {
            return true;
        }

        return false;
    }

    /**
     * 🕊️ (2026-06-24) ตรวจ "ลังเล/ยังไม่พร้อม" ที่กล่องกติกา — กันตัดบิลให้คนที่ยังไม่พร้อม
     *
     * ใช้คู่กับ handleConsentAcceptIfPending (auto-consent flow): เมื่อข้อความ "ไม่ใช่ยอมรับ
     * และไม่ใช่ปฏิเสธชัด" เราเลือกตัดเข้าสร้างบิล (owner decision 2026-06-24) — แต่ต้องอุด
     * รอยรั่ว refusal ที่ isCancelRequest/looksLikeSoftDeclineDuringPayment จับไม่ติด
     * (เช่น "ยังไม่พร้อม" โดน 'ยัง'-exclusion ใน softDecline → หลุด → ถ้าไม่กรองจะตัดบิลผิด)
     *
     * 🎯 จับเฉพาะคำกริยา "ความพร้อม/การจ่าย/ขอเลื่อน" เท่านั้น — ต้อง *ไม่* ชนคำสับสน
     *    ("ยังไม่เข้าใจ/ยังไม่รู้/ทำไม่เป็น") ที่ลูกค้าต้องการความช่วยเหลือ = ควรตัดบิล+QR
     */
    protected function looksLikeConsentHesitation(string $messageText): bool
    {
        $t = mb_strtolower(trim($messageText));
        if ($t === '') {
            return false;
        }

        // คำที่สื่อ "ยังไม่พร้อมจ่าย / ขอเลื่อน / ปฏิเสธจ่าย" แบบไม่กำกวม
        //   (ไม่รวมคำกว้าง "รอ/เดี๋ยว" เดี่ยวๆ เพราะมักแปลว่า "รอแป๊บกำลังจะโอน" = เดินหน้า)
        return (bool) preg_match(
            '/(ยังไม่พร้อม|ไม่พร้อม|ยังไม่สะดวก|ไม่สะดวก|ยังไม่โอน|ยังไม่จ่าย|ยังไม่อยากจ่าย|'
            .'ไม่ตกลง|ไม่ยอมรับ|เดี๋ยวค่อย|ขอคิดก่อน|ขอคิดดู|ไว้ค่อย|ไว้ทีหลัง|เอาไว้ก่อน)/u',
            $t
        );
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
