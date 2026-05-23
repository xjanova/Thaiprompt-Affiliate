<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\TarotCard;
use App\Services\CelticCrossService;
use App\Services\FortuneAIService;
use App\Services\FortuneLocaleService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🎁 Trait: ระบบ "ทำนายฟรี 1 ใบ ครั้งแรก/platform" (2026-05-03)
 *
 * นโยบาย:
 *   - ฟรีครั้งเดียวต่อ platform_user_id (FB / LINE แยกกัน)
 *   - ลูกค้าใหม่ครั้งแรก DM → เห็นปุ่ม "🎁 ทำนายฟรี (1 ใบ)"
 *   - กดปุ่ม → จั่วไพ่ 1 ใบทันที (ไม่ขอ context จากลูกค้า)
 *   - Gemini ทำนายโดยใช้ "จิตสัมผัสดวงสมพงษ์" + ผูกกับบริบทยุค
 *   - ทำนายเสร็จ → AI พ่วงท้ายชวนซื้อ 39/99 เนียน
 *   - ลูกค้าปฏิเสธ → คำลา + ปรัชญาชีวิต (ไม่ฮาร์ดเซล ไม่ตบท้าย)
 *   - หลังจบ state ลูกค้าจะคุยกับ Groq (chat AI) ตามปกติ
 *
 * State flow:
 *   [first-time DM] → tier menu (มีปุ่มฟรีเพิ่ม)
 *      → กด FREE_CARD_START → startFreeCardFlow()
 *         → จั่วไพ่ + AI ทำนาย → STATUS_FREE_PREDICTED
 *            → ลูกค้ากด 39 → ไป tier 39
 *            → ลูกค้ากด 99 → ไป Celtic 99
 *            → ลูกค้ากด ไม่สนใจ → endFreeCardWithGoodbye() → STATUS_FREE_DECLINED
 *
 * ใช้:
 *   class FortuneConversationService { use FreeCardConversationTrait; }
 */
trait FreeCardConversationTrait
{
    /**
     * Dispatch handler สำหรับ free card states
     * เรียกจาก main dispatch ใน FortuneConversationService
     *
     * @return array|null null ถ้าไม่ใช่ free state — ให้ caller ส่งต่อ default handler
     */
    protected function handleFreeCardState(FortuneReading $reading, string $messageText): ?array
    {
        if ($reading->conversation_status !== FortuneReading::STATUS_FREE_PREDICTED) {
            return null;
        }

        return $this->handleFreeCardUpsellResponse($reading, $messageText);
    }

    /**
     * 🎯 ตรวจสอบว่าข้อความเป็นการขอ "ทำนายฟรี" หรือไม่
     *
     * รองรับ:
     *   - กดปุ่ม Quick Reply (FB payload "FREE_CARD_START" → text "ทำนายฟรี")
     *   - พิมพ์ตรงๆ "ทำนายฟรี" / "ดูดวงฟรี" / "ฟรี"
     *   - 🇱🇦 ลาว: "ທຳນາຍຟຣີ" / "ເບິ່ງດວງຟຣີ"
     */
    protected function matchesFreeCardKeyword(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        // ลบคำลงท้ายไทย/ลาว
        $clean = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|หน่อย|ที|สิ|เลย|เດີ|ແດ່)\s*$/u', '', $clean);

        $exact = [
            // ไทย
            'ทำนายฟรี', 'ทำนายฟรี1ใบ', 'ทำนายฟรี 1 ใบ', 'ทำนายฟรี1', 'ทำนาย ฟรี',
            'ดูดวงฟรี', 'ดูดวง ฟรี', 'ขอฟรี', 'ฟรี', 'free',
            'free_card_start', 'free card start', 'free card',
            // 🇱🇦 ລາວ
            'ທຳນາຍຟຣີ', 'ເບິ່ງດວງຟຣີ', 'ຟຣີ',
        ];

        foreach ($exact as $kw) {
            if ($clean === $kw || $clean === mb_strtolower($kw)) {
                return true;
            }
        }

        // ตรวจ "ทำนาย" + "ฟรี" ในข้อความเดียวกัน (allow: "ขอทำนายฟรีหน่อย")
        if (mb_strpos($clean, 'ฟรี') !== false &&
            (mb_strpos($clean, 'ทำนาย') !== false || mb_strpos($clean, 'ดูดวง') !== false)) {
            return mb_strlen($clean) <= 30;
        }

        return false;
    }

    /**
     * 🩹 (2026-05-05) ตรวจว่า message เป็นคำถามที่ทำนายได้ หรือเป็นทักทาย/บริบทไม่พอ
     *
     * user spec:
     *   "ถ้าจับใจความไม่ได้ เช่น สวัสดี ค่ะ → ควรบอกให้ตอบเป็นคำถามที่อยากดูฟรี"
     *
     * Reject (ไม่ใช่คำถาม):
     *   - empty / whitespace-only
     *   - ความยาว < 10 chars (น้อยเกินจะมี context)
     *   - greeting keywords: สวัสดี, ดี, hello, hi, ฮัลโหล, สบายดี
     *   - acknowledgment: ค่ะ, ครับ, ok, โอเค, ขอบคุณ, thanks
     *   - free card keyword (ลูกค้ากดปุ่ม "ทำนายฟรี" → message=keyword ไม่ใช่คำถาม)
     *   - emoji-only / sticker
     *
     * Accept (เป็นคำถาม):
     *   - มี keyword เนื้อหา (รัก, งาน, เงิน, สุขภาพ, คน, จะ, อยาก, เรื่อง, ฯลฯ)
     *   - หรือยาว ≥ 10 chars + ไม่ใช่ทักทาย/acknowledgment ล้วน
     */
    protected function isSubstantiveQuestion(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $clean = mb_strtolower(trim($text));
        // ลบคำลงท้ายไทย/ลาว
        $clean = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|หน่อย|ที|สิ|เลย|เດີ|ແດ່|\?|\.|!)\s*$/u', '', $clean);
        $clean = trim($clean);

        if (mb_strlen($clean) === 0) {
            return false;
        }

        // ❌ Reject — สั้นเกินไป (greetings มักสั้น)
        if (mb_strlen($clean) < 10) {
            return false;
        }

        // ❌ Reject — free card keyword (ลูกค้ากดปุ่ม)
        if ($this->matchesFreeCardKeyword($text)) {
            return false;
        }

        // ❌ Reject — pure greeting / acknowledgment list
        $nonQuestionKeywords = [
            // ทักทาย ไทย
            'สวัสดี', 'สวัสดีค่ะ', 'สวัสดีครับ', 'หวัดดี', 'ดีค่ะ', 'ดีครับ', 'ฮัลโหล', 'สบายดี',
            'ทักทาย', 'อยู่ไหม', 'อยู่ป่ะ', 'หมอจันทรา', 'แม่หมอ',
            // ทักทาย English
            'hello', 'hi', 'hey', 'good morning', 'good evening', 'good night',
            // Acknowledgment ไทย
            'ขอบคุณ', 'ขอบคุณค่ะ', 'ขอบคุณครับ', 'ขอบใจ', 'โอเค', 'ตกลง', 'รับทราบ',
            'จ้า', 'จ้ะ', 'จ๊ะ', 'อืม', 'อ่อ', 'ครับผม', 'ค่าาา',
            // Acknowledgment English
            'ok', 'okay', 'thanks', 'thank you', 'tnx', 'yep', 'yes', 'yeah',
            // 🇱🇦 ลาว
            'ສະບາຍດີ', 'ດີ', 'ຂອບໃຈ', 'ຕົກລົງ', 'ໂອເຄ',
            // ทดสอบ / สั้น ๆ
            'test', 'ทดสอบ', '???', '...', 'มีไรเหรอ', 'ทำไม',
        ];

        foreach ($nonQuestionKeywords as $kw) {
            if ($clean === mb_strtolower($kw)) {
                return false;
            }
        }

        // ❌ Reject — emoji-only (ไม่มีตัวอักษร Thai/Lao/English)
        $hasLetter = preg_match('/[\x{0E00}-\x{0E7F}\x{0E80}-\x{0EFF}a-zA-Z]/u', $clean);
        if (! $hasLetter) {
            return false;
        }

        // ✅ Accept — มี keyword เนื้อหา
        $contentKeywords = [
            // ไทย
            'รัก', 'แฟน', 'คู่', 'ความรัก', 'งาน', 'การงาน', 'อาชีพ', 'เงิน', 'การเงิน',
            'สุขภาพ', 'ลูก', 'ครอบครัว', 'คน', 'เพื่อน', 'ศัตรู', 'เจ้านาย',
            'อยาก', 'จะ', 'ควร', 'ต้อง', 'มี', 'ได้', 'ไหม', 'หรือ', 'ทำไง', 'ยังไง', 'เมื่อไหร่',
            'เป็นไง', 'เป็นยังไง', 'ดีไหม', 'ดีมั้ย', 'ดีหรือ', 'ปีนี้', 'เดือนนี้', 'ช่วงนี้',
            'ดวง', 'ชะตา', 'อนาคต', 'โชค', 'เคราะห์', 'อุปสรรค', 'ปัญหา', 'เรื่อง', 'ทุกข์', 'สุข',
            'ย้าย', 'ลาออก', 'เปลี่ยน', 'ตัดสินใจ', 'รอ', 'หา', 'พบ',
            // ลาว
            'ຮັກ', 'ແຟນ', 'ວຽກ', 'ເງິນ', 'ສຸຂະພາບ', 'ດວງ', 'ຊາຕາ', 'ອານາຄົດ',
        ];

        foreach ($contentKeywords as $kw) {
            if (mb_strpos($clean, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        // ✅ Fallback: ยาว ≥ 15 chars + ไม่ใช่ greeting → ถือว่ามี context พอ
        if (mb_strlen($clean) >= 15) {
            return true;
        }

        // ❌ ไม่ผ่านเกณฑ์ใด ๆ
        return false;
    }

    /**
     * 🎯 Entry point: เริ่ม free card flow
     *
     * เรียกจาก:
     *   - dispatchAction เมื่อข้อความ match matchesFreeCardKeyword()
     *   - presentTierChoice → handleTierChoice เมื่อลูกค้าพิมพ์ "ฟรี"
     *
     * Logic:
     *   1. เช็ค feature toggle (isFreeReadingEnabled) — ถ้าปิด → fallback tier menu
     *   2. เช็คว่าลูกค้าเคยใช้สิทธิ์ฟรีแล้วหรือยัง (hasUsedFreeCard) — ถ้าใช้แล้ว → fallback tier menu
     *   3. สร้าง FortuneReading type='free_card' status='new' (placeholder)
     *   4. จั่วไพ่ 1 ใบ
     *   5. เรียก AI generateFreeCardReading() → ส่งคืน 'free_card_drawn' action
     *   6. ChannelManager จะส่งภาพไพ่ + ข้อความ + Quick Reply [39][99][ไม่สนใจ]
     */
    protected function startFreeCardFlow(string $platformUserId, ?array $userProfile = null, ?string $customerMessage = null): array
    {
        $platform = $this->currentPlatform;
        $name = ! empty($userProfile['name']) ? $userProfile['name'] : 'คุณ';

        // 🔒 Feature toggle
        if (! $this->settings->isFreeReadingEnabled()) {
            Log::info('FreeCard: ระบบฟรีปิดอยู่ — fallback tier menu', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);

            return $this->startDeepReadingFlow($platformUserId, $userProfile, null, $customerMessage);
        }

        // 🔒 First-timer check — ถ้าใช้สิทธิ์แล้ว → ไป tier menu ตรง (ไม่กล่าวถึงฟรีอีก)
        if (FortuneReading::hasUsedFreeCard($platform, $platformUserId)) {
            Log::info('FreeCard: ลูกค้าใช้สิทธิ์ฟรีไปแล้ว — fallback tier menu', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);

            return $this->startDeepReadingFlow($platformUserId, $userProfile, null, $customerMessage);
        }

        // 🩹 (2026-05-05) Question gate — user spec:
        //   "ถ้าจับใจความไม่ได้ เช่น สวัสดี ค่ะ → ควรบอกให้ตอบเป็นคำถามที่อยากดูฟรี
        //    เพื่อจะได้ทำนายครั้งแรกให้แม่นยำ"
        //
        //   ถาม customer ก่อนจั่วไพ่ ถ้า message ไม่ใช่คำถามที่ทำนายได้
        //   เช็ค Cache pending count — ถ้าถามไป 2 ครั้งแล้ว → proceed (ไม่ loop ถาวร)
        $pendingCacheKey = "fortune:free_card_ask:{$platform}:{$platformUserId}";
        $askedCount = (int) Cache::get($pendingCacheKey, 0);

        if (! $this->isSubstantiveQuestion($customerMessage) && $askedCount < 2) {
            Cache::put($pendingCacheKey, $askedCount + 1, now()->addMinutes(30));

            Log::info('FreeCard: ขอ customer พิมพ์คำถามก่อนทำนาย', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'asked_count' => $askedCount + 1,
                'message_preview' => $customerMessage ? mb_substr($customerMessage, 0, 50) : null,
            ]);

            $greetName = ($name && $name !== 'คุณ') ? "คุณ{$name}" : 'เจ้าชะตา';
            $askMessage = FortuneLocaleService::lo(
                "🌙 *แม่หมอจับจิต{$greetName}แล้ว — แต่ขอถามนิดนะคะ*\n\n"
                    ."💬 ตอนนี้เจ้าชะตา *อยากให้แม่หมอทำนายเรื่องอะไร* คะ?\n"
                    ."พิมพ์คำถามมาให้แม่หมอ เช่น:\n"
                    ."  • \"ความรักช่วงนี้เป็นไง\"\n"
                    ."  • \"งานในเดือนหน้า ควรเปลี่ยนไหม\"\n"
                    ."  • \"การเงินช่วงนี้ติดขัด ต้องทำยังไง\"\n"
                    ."  • \"คนที่คิดถึง เขายังคิดถึงเราไหม\"\n\n"
                    .'✨ ยิ่งเล่ารายละเอียดเยอะ — ไพ่ที่จิตเลือกจะแม่นเป๊ะค่ะ 🃏',
                "🌙 *ແມ່ໝໍຈັບຈິດ{$greetName}ແລ້ວ — ແຕ່ຂໍຖາມໜ້ອຍເດີ*\n\n"
                    ."💬 ຕອນນີ້ເຈົ້າຊາຕາ *ຢາກໃຫ້ແມ່ໝໍທຳນາຍເລື່ອງຫຍັງ* ເດີ?\n"
                    ."ພິມຄຳຖາມມາໃຫ້ແມ່ໝໍ ເຊັ່ນ:\n"
                    ."  • \"ຄວາມຮັກຊ່ວງນີ້ເປັນຫຍັງ\"\n"
                    ."  • \"ວຽກໃນເດືອນໜ້າ ຄວນປ່ຽນບໍ່\"\n"
                    ."  • \"ການເງິນຊ່ວງນີ້ຕິດຂັດ ຕ້ອງເຮັດແນວໃດ\"\n\n"
                    .'✨ ຍິ່ງເລົ່າລາຍລະອຽດເຍາະ — ໄພ່ທີ່ຈິດເລືອກຈະແມ່ນເປັະເດີ 🃏'
            );

            return [
                'action' => 'free_card_ask_question',
                'message' => $askMessage,
                'reading' => null,
            ];
        }

        // ✅ ผ่าน gate — clear pending counter
        Cache::forget($pendingCacheKey);

        // ⚠️ ปิด conversation เก่า (กัน orphan state)
        $this->closeAllActiveConversations($platformUserId);

        // 1️⃣ สร้าง reading สถานะ 'new' (placeholder ก่อนจั่วไพ่)
        $reading = FortuneReading::create([
            'facebook_user_id' => $platformUserId,
            'facebook_user_name' => $name,
            'user_profile' => $userProfile,
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_FREE_CARD,
            'conversation_status' => FortuneReading::STATUS_NEW,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
        ]);

        Log::info('FreeCard: สร้าง free_card reading ใหม่', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
        ]);

        // 2️⃣ จั่วไพ่ 1 ใบ — สุ่มจากทั้งสำรับ + 50/50 reversed
        try {
            $card = $this->drawSingleTarotCard();
        } catch (Exception $e) {
            Log::error('FreeCard: จั่วไพ่ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'free_card_draw_failed',
                'message' => FortuneLocaleService::lo(
                    "🙏 ขออภัยค่ะ ระบบสุ่มไพ่ขัดข้องชั่วคราว\nลองทักมาใหม่ในอีกครู่ได้นะคะ ✨",
                    "🙏 ຂໍອະໄພເດີ ລະບົບສຸ່ມໄພ່ຂັດຂ້ອງຊົ່ວຄາວ\nລອງທັກມາໃໝ່ໃນອີກບຶດນຶງເດີ ✨"
                ),
                'reading' => $reading,
            ];
        }

        // เก็บข้อมูลไพ่ลง conversation_state เพื่อ debug + record
        $reading->setConversationState('free_card', [
            'card_id' => $card['card_id'],
            'card_name_th' => $card['card_name_th'],
            'card_name_en' => $card['card_name_en'],
            'is_reversed' => $card['is_reversed'],
            'image_url' => $card['image_url'],
            'drawn_at' => now()->toIso8601String(),
        ]);

        // 3️⃣ เรียก AI ทำนาย
        try {
            $deepPrice = (int) $this->getDeepReadingPrice();
            $celticPrice = (int) app(CelticCrossService::class)->getPrice();
            $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

            // 🆕 (2026-05-07) Free Card → request 'free_card' purpose ตอน acquire key
            //   admin ตั้ง key purpose='free_card' = สงวนเฉพาะทำนายฟรี (ไม่ปนกับ paid prediction)
            $aiService = new FortuneAIService($this->settings, 'free_card');
            $result = $aiService->generateFreeCardReading(
                card: $card,
                userName: $name,
                deepPrice: $deepPrice,
                celticPrice: $celticPrice,
                celticEnabled: $celticEnabled,
                userContext: "free_card:{$reading->id}",
                // 💬 (2026-05-04) pass customer message → AI ใช้เป็น context ในการทำนาย
                customerMessage: $customerMessage,
            );

            $response = trim($result['response'] ?? '');
            // 🩹 (2026-05-05) ขั้นต่ำ 600 ตัวอักษร — prompt ใหม่ขอ 1500-2000 chars
            //   user spec: "ทำนายฟรีสั้นไป" → reject ถ้า AI ตอบสั้นเกิน
            if ($response === '' || mb_strlen($response) < 600) {
                throw new Exception('AI ตอบกลับสั้นเกินไป ('.mb_strlen($response).' ตัวอักษร — ต้อง ≥ 600)');
            }
        } catch (\Throwable $e) {
            Log::error('FreeCard: AI ทำนายล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            // ❌ AI fail → ปิด reading (ไม่ mark consumed — ลูกค้าลองใหม่ได้)
            //    เพราะ hasUsedFreeCard() เช็ค responded_at != null เท่านั้น
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'free_card_ai_failed',
                'message' => FortuneLocaleService::lo(
                    "🙏 ขออภัยค่ะ แม่หมอเชื่อมพลังจักรวาลไม่ติดในจังหวะนี้\n"
                        .'ลองทักมาใหม่ในอีก 1-2 นาทีนะคะ — สิทธิ์ฟรียังไม่ถูกใช้ค่ะ ✨',
                    "🙏 ຂໍອະໄພເດີ ແມ່ໝໍເຊື່ອມພະລັງຈັກກະວານບໍ່ຕິດໃນຈັງຫວະນີ້\n"
                        .'ລອງທັກມາໃໝ່ໃນອີກ 1-2 ນາທີເດີ — ສິດທິຟຣີຍັງບໍ່ໄດ້ໃຊ້ ✨'
                ),
                'reading' => $reading,
            ];
        }

        // 4️⃣ บันทึกผลลง reading + เปลี่ยน state เป็น FREE_PREDICTED
        $reading->update([
            'ai_response' => $response,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
            'tokens_used' => (int) ($result['tokens_used'] ?? 0),
            'responded_at' => now(), // ⭐ mark ว่าใช้สิทธิ์ฟรีแล้ว
            'conversation_status' => FortuneReading::STATUS_FREE_PREDICTED,
        ]);

        Log::info('FreeCard: ทำนายสำเร็จ → STATUS_FREE_PREDICTED', [
            'reading_id' => $reading->id,
            'card' => $card['card_name_th'].($card['is_reversed'] ? ' (กลับหัว)' : ' (ตั้งตรง)'),
            'response_len' => mb_strlen($response),
            'provider' => $result['provider'] ?? '?',
            'tokens' => $result['tokens_used'] ?? 0,
        ]);

        // 5️⃣ ส่ง response — ChannelManager จะ:
        //   - ส่งภาพไพ่ก่อน (จาก tarot_image_url)
        //   - ส่งข้อความคำทำนาย
        //   - ส่ง Quick Reply [🔹 ดูดวง 39][🔮 99][🌙 ไม่สนใจ]
        // 🌐 (2026-05-03) localize header — ลูกค้าลาวเห็น label ลาว ไม่ใช่ไทยตามด้วย AI ลาว
        $orientation = $card['is_reversed']
            ? FortuneLocaleService::lo('(กลับหัว)', '(ກັບຫົວ)')
            : FortuneLocaleService::lo('(ตั้งตรง)', '(ຕັ້ງຊື່)');
        $cardHeaderLabel = FortuneLocaleService::lo(
            '🃏✨ *ไพ่ที่จิตเจ้าชะตาเลือก:*',
            '🃏✨ *ໄພ່ທີ່ຈິດເຈົ້າຊາຕາເລືອກ:*'
        );
        $cardHeader = "{$cardHeaderLabel}\n"
            ."*{$card['card_name_th']}* {$orientation}\n"
            ."({$card['card_name_en']})\n\n"
            ."━━━━━━━━━━━━━━━━━\n\n";

        return [
            'action' => 'free_card_drawn',
            'message' => $cardHeader.$response,
            'reading' => $reading,
            'tarot_image_url' => $card['image_url'],
            'free_card_data' => $card,
        ];
    }

    /**
     * จั่วไพ่ 1 ใบ — สุ่มจากทั้งสำรับ + 50/50 reversed
     *
     * @return array ['card_id', 'card_name_th', 'card_name_en', 'is_reversed', 'meaning', 'image_url']
     *
     * @throws Exception เมื่อไม่พบไพ่ในระบบ
     */
    protected function drawSingleTarotCard(): array
    {
        $card = TarotCard::where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (! $card) {
            throw new Exception('ไม่พบไพ่ทาโรต์ในระบบ — กรุณา seed tarot_cards table');
        }

        $isReversed = (bool) random_int(0, 1);

        return [
            'card_id' => $card->id,
            'card_name_th' => $card->getName('th'),
            'card_name_en' => $card->getName('en'),
            'is_reversed' => $isReversed,
            'meaning' => $card->getMeaning($isReversed, 'th'),
            'image_url' => $card->image_url,
        ];
    }

    /**
     * State: STATUS_FREE_PREDICTED
     * ลูกค้าตอบกลับหลังเห็นคำทำนายฟรี — เลือกซื้อ 39/99 หรือปฏิเสธ
     */
    protected function handleFreeCardUpsellResponse(FortuneReading $reading, string $messageText): array
    {
        $textLower = mb_strtolower(trim($messageText));
        $deepPriceInt = (int) $this->getDeepReadingPrice();
        $celticPriceInt = (int) app(CelticCrossService::class)->getPrice();
        // 🌙 (2026-05-23) Deep 39฿ ปิดเพราะลูกค้าสับสน — keyword/upsell ของ Deep ต้อง guard
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
        // tier ที่ใช้ใน startDeepReadingFlow — ถ้า Deep ปิดแต่ Celtic เปิด ส่ง celtic explicit
        $upsellTier = (! $deepEnabled && $celticEnabled) ? 'celtic' : null;

        // ❌ ปฏิเสธ / ไม่สนใจ — ส่งคำลา + ปรัชญา (ไม่ฮาร์ดเซล)
        $declineKeywords = [
            'ไม่สนใจ', 'ไม่เอา', 'ไม่อยาก', 'ไม่จ่าย', 'ไม่ดู', 'ไม่', 'ขอบคุณ', 'ขอบคุณค่ะ',
            'พอแค่นี้', 'พอแล้ว', 'จบ', 'ไม่ขอบคุณ', 'no', 'no thanks', 'cancel', 'ยกเลิก',
            'ไว้ก่อน', 'ไว้ทีหลัง', 'ไว้คิดดู', 'ขอคิดดูก่อน',
            // 🇱🇦 ลาว
            'ບໍ່ສົນໃຈ', 'ບໍ່ເອົາ', 'ບໍ່ຢາກ', 'ບໍ່', 'ຂອບໃຈ', 'ພໍແລ້ວ', 'ຍົກເລີກ',
            'free_card_decline',
        ];
        foreach ($declineKeywords as $kw) {
            if ($textLower === mb_strtolower($kw) || str_contains($textLower, mb_strtolower($kw))) {
                if (mb_strlen($textLower) <= 30) { // ป้องกันจับผิด — keyword ต้องอยู่ในข้อความสั้น
                    return $this->endFreeCardWithGoodbye($reading);
                }
            }
        }

        // 🔮 99฿ Celtic — เช็คก่อน 39 (กันชน)
        if ($celticEnabled) {
            $celticKeywords = [
                (string) $celticPriceInt, 'celtic', 'เซลติก', 'เต็มสำรับ', 'ไพ่ยิปซีเต็ม',
                'แพคเกจ 2', 'แพคเกจที่ 2', 'แบบที่ 2', 'tier_celtic', 'tier_celtic_99',
            ];
            foreach ($celticKeywords as $kw) {
                if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                    Log::info('FreeCard: ลูกค้าเลือก Celtic 99฿ หลังทำนายฟรี', [
                        'reading_id' => $reading->id,
                    ]);
                    // ปิด free reading + เริ่ม Celtic flow ใหม่
                    $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                    return $this->startDeepReadingFlow($reading->facebook_user_id, $reading->user_profile, 'celtic', $messageText);
                }
            }
        }

        // 🔹 39฿ Deep — เฉพาะถ้า toggle เปิด (🌙 2026-05-23 ปิดเพราะลูกค้าสับสน)
        //   ⚠️ ถ้า Deep ปิด ห้ามจับ keyword 'ดูดวง'/'ทำนาย' แล้ว redirect ไป Deep เพราะ
        //     - ระบบจะตกไป startDeepReadingFlow → tier menu (ที่ถูก guard แล้ว) → Celtic ตรง
        //     - แต่ถ้า Deep ปิด + Celtic ปิด → flow break ลูกค้าค้าง chitchat
        if ($deepEnabled) {
            $deepKeywords = [
                (string) $deepPriceInt, 'ดูดวง 39', 'เชิงลึก', 'ละเอียด', 'พื้นฐาน',
                'แพคเกจ 1', 'แพคเกจที่ 1', 'แบบที่ 1', 'tier_deep', 'tier_deep_39',
                'ดูดวง', 'ทำนาย', // ลูกค้าพิมพ์ "ดูดวง" lone หลังฟรี = อยากซื้อต่อ
            ];
            foreach ($deepKeywords as $kw) {
                if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                    Log::info('FreeCard: ลูกค้าเลือก Deep 39฿ หลังทำนายฟรี', [
                        'reading_id' => $reading->id,
                    ]);
                    $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                    return $this->startDeepReadingFlow($reading->facebook_user_id, $reading->user_profile, null, $messageText);
                }
            }
        } elseif ($celticEnabled) {
            // Deep ปิด + Celtic เปิด → ดักคำว่า "ดูดวง"/"ทำนาย" lone redirect ไป Celtic แทน
            $genericKeywords = ['ดูดวง', 'ทำนาย', 'ต่อ', 'ซื้อ'];
            foreach ($genericKeywords as $kw) {
                if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                    Log::info('FreeCard: Deep ปิด — redirect Celtic 99฿ หลังทำนายฟรี', [
                        'reading_id' => $reading->id,
                    ]);
                    $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                    return $this->startDeepReadingFlow($reading->facebook_user_id, $reading->user_profile, 'celtic', $messageText);
                }
            }
        }

        // ❓ ข้อความอื่น — chitchat → ส่งให้ AI Chat (Groq) ตอบต่อ + แทรก hint ปุ่ม
        //    เพราะ state คือ FREE_PREDICTED → AI chat (Groq) จะตอบต่อตามปกติ
        //    ระบบจะ route กลับมาที่นี่ถ้าลูกค้าตอบใหม่ → ลองจับ keyword อีกรอบ
        $hintLine = FortuneLocaleService::lo(
            '🌙 หากเจ้าชะตาอยากเจาะลึก เลือกได้ที่ปุ่มด้านล่างเลยนะคะ ✨',
            '🌙 ຫາກເຈົ້າຊາຕາຢາກເຈາະເລິກ ເລືອກໄດ້ທີ່ປຸ່ມດ້ານລຸ່ມເລີຍເດີ ✨'
        );
        // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนบรรทัด Deep ออกจาก hint
        $deepLine = $deepEnabled
            ? FortuneLocaleService::lo(
                "🔹 *ดูดวงเชิงลึก {$deepPriceInt} บาท* — วิเคราะห์ดาวเจ้าชนะ + ไพ่ยิปซี",
                "🔹 *ເບິ່ງດວງເຈາະເລິກ {$deepPriceInt} ບາດ* — ວິເຄາະດາວເຈົ້າຊະນະ + ໄພ່ຍິບຊີ"
            )
            : null;
        // 🌙 (2026-05-24) บอกสเปคชัด — เดิม "ถามได้หลายคำถาม / ຖາມໄດ້ຫຼາຍຄຳຖາມ" (vague)
        //   ปัจจุบัน 5 คำถาม / 15 นาที (Session 2026-05-23 #7) — admin override ได้
        $celticMaxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $celticQaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);

        $celticLine = $celticEnabled
            ? FortuneLocaleService::lo(
                "🔮 *Celtic Cross {$celticPriceInt} บาท* — ไพ่ 10 ใบ ถามได้ {$celticMaxQ} คำถาม / {$celticQaWindow} นาที",
                "🔮 *Celtic Cross {$celticPriceInt} ບາດ* — ໄພ່ 10 ໃບ ຖາມໄດ້ {$celticMaxQ} ຄຳຖາມ / {$celticQaWindow} ນາທີ"
            )
            : null;
        $declineLine = FortuneLocaleService::lo(
            '🌙 *ไม่สนใจ* — ลาแม่หมอแบบสุภาพ',
            '🌙 *ບໍ່ສົນໃຈ* — ລາແມ່ໝໍແບບສຸພາບ'
        );

        return [
            'action' => 'free_card_chitchat',
            'message' => $hintLine."\n\n"
                .($deepLine ? $deepLine."\n" : '')
                .($celticLine ? $celticLine."\n" : '')
                .$declineLine,
            'reading' => $reading,
        ];
    }

    /**
     * 🌙 จบ free flow แบบสุภาพ + ปรัชญา (ไม่ฮาร์ดเซล)
     *
     * ส่ง:
     *   - คำลา (สุ่ม 1 จาก 3 แบบ)
     *   - ปรัชญาชีวิต (สุ่ม 1 จาก 7 quote — theme "ยังไม่ถึงเวลา")
     *
     * ❌ ห้าม: ขายของ / กดดัน / "ถ้าเปลี่ยนใจ..." / "ค่าครูแค่..."
     */
    protected function endFreeCardWithGoodbye(FortuneReading $reading): array
    {
        // เลือก locale ปัจจุบัน (TH หรือ Lao)
        $isLao = FortuneLocaleService::current() === FortuneLocaleService::LOCALE_LO;
        $locale = $isLao ? 'lo' : 'th';

        // โหลดจาก config
        $quotes = config('fortune-philosophies.quotes', []);
        $goodbyes = config('fortune-philosophies.goodbyes', []);

        // 🎲 สุ่ม 1 ข้อ
        $quote = ! empty($quotes) ? $quotes[array_rand($quotes)] : null;
        $goodbye = ! empty($goodbyes) ? $goodbyes[array_rand($goodbyes)] : null;

        // เลือกข้อความตาม locale (fallback Thai)
        $quoteText = $quote ? ($quote[$locale] ?? $quote['th'] ?? '') : '';
        $goodbyeText = $goodbye ? ($goodbye[$locale] ?? $goodbye['th'] ?? '') : '';

        // ปิด state
        $reading->update(['conversation_status' => FortuneReading::STATUS_FREE_DECLINED]);

        // ประกอบข้อความ
        $message = $goodbyeText."\n\n"
            ."━━━━━━━━━━━━━━━━━\n\n"
            .'💭 '.$quoteText."\n\n"
            .'🙏';

        Log::info('FreeCard: ลูกค้าปฏิเสธ → ส่งคำลา + ปรัชญา', [
            'reading_id' => $reading->id,
            'locale' => $locale,
        ]);

        return [
            'action' => 'free_card_declined',
            'message' => $message,
            'reading' => $reading,
            'no_default_qr' => true, // ⛔ ห้าม ChannelManager ใส่ default QR (ไม่ฮาร์ดเซล)
        ];
    }
}
