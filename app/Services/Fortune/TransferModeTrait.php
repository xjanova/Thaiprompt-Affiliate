<?php

namespace App\Services\Fortune;

use App\Services\FacebookRichMessageService;
use Illuminate\Support\Facades\Log;

/**
 * 🔀 (2026-07-26) โหมด TRANSFER — ดักหน้าแชท FB แล้วพาลูกค้าไปดูดวงฟรีที่เว็บ/LINE
 *
 * ใช้กับ FortuneConversationService (trait — ต้องมี $this->settings, $this->currentPlatform
 * และเมธอดตรวจ intent เดิมของคลาสนั้น)
 *
 * ทำไมดักที่ชั้นข้อความจุดเดียว (ไม่ต้องดัก postback แยก):
 *   ปุ่มทุกปุ่มของ FB (postback + quick reply) ถูกแปลงเป็น "ข้อความ" ก่อนเข้า
 *   processConversationalMessage อยู่แล้ว — เช่น MENU_FORTUNE → 'ดูดวง',
 *   FREE_CARD_START → 'ทำนายฟรี' (FacebookWebhookController:2776, 4173, 4206)
 *   → ดักที่นี่จุดเดียวจึงครอบปุ่มเก่าที่ค้างในประวัติแชทด้วยโดยอัตโนมัติ
 *
 * ⚠️ ห้ามดักลูกค้าที่มีบิลค้าง/จ่ายแล้ว/กำลังเปิดไพ่ — ผู้เรียกต้องเรียก trait นี้
 *    **หลัง** ผ่านกำแพง activeReading แล้วเท่านั้น (ดูจุดเรียกใน FortuneConversationService)
 */
trait TransferModeTrait
{
    /**
     * คำที่บอกว่า "ทำเว็บ/ไลน์ไม่เป็น" — ทางถอยตามที่เจ้าของสั่ง
     *
     * ลูกค้ากลุ่มนี้คือคนที่ยอมจ่ายแต่ทำไม่เป็น (ส่วนใหญ่ผู้สูงอายุ)
     * ถ้าไม่มีทางถอย = เราคัดคนที่จะจ่ายเงินทิ้งด้วยมือตัวเอง
     *
     * @var array<int,string>
     */
    protected array $transferCannotSignals = [
        'ไม่มีไลน์', 'ไม่มีline', 'ไม่มี line', 'ไม่เล่นไลน์', 'ไม่ได้เล่นไลน์',
        'กดไม่ได้', 'กดไม่ติด', 'กดแล้วไม่ขึ้น', 'เข้าไม่ได้', 'เปิดไม่ได้',
        'ทำไม่เป็น', 'ทำไม่ได้', 'ไม่เป็น', 'ไม่รู้วิธี', 'ไม่เข้าใจ', 'งง',
        'สมัครไม่ได้', 'ไม่มีเน็ต', 'เน็ตไม่มี', 'โทรศัพท์ไม่รองรับ',
        'ขอดูที่นี่', 'ดูที่นี่', 'ดูในนี้', 'ขอดูในแชท', 'ดูในแชท', 'อยู่นี่',
    ];

    /**
     * 🚫 (2026-07-27) เรื่องที่ "ห้ามโดนแจกไพ่ฟรีทับ" บน LINE
     *
     * ฟรีออโต้บน LINE ยิงที่ข้อความแรกโดยไม่ต้องรอให้พิมพ์ "ดูดวง" (สเปกเจ้าของ)
     * แต่ถ้าไม่กันเรื่องพวกนี้ ลูกค้าที่ทักมา "ขอเลขบัญชี" / "ขอคุยแอดมิน" /
     * "โอนแล้วครับ" จะได้ไพ่ 1 ใบตอบกลับแทนคำตอบที่เขาต้องการ = พังกว่าเดิม
     *
     * @var array<int,string>
     */
    protected array $lineAutoFreeBlockSignals = [
        // เงิน/สลิป — ห้ามแตะเด็ดขาด (feedback_never_interrupt_payment_to_prediction_flow)
        'โอนแล้ว', 'จ่ายแล้ว', 'ชำระแล้ว', 'สลิป', 'หลักฐาน', 'เลขบัญชี', 'บัญชีธนาคาร',
        'พร้อมเพย์', 'promptpay', 'คิวอาร์', 'qr', 'ยอดเงิน', 'คืนเงิน', 'ขอเงินคืน',
        // ขอคุยคน
        'แอดมิน', 'admin', 'คุยกับคน', 'ติดต่อเจ้าหน้าที่', 'เจ้าหน้าที่', 'ร้องเรียน',
        // ยกเลิก/ไม่เอา
        'ยกเลิก', 'ไม่เอาแล้ว', 'ไม่ต้องการ', 'หยุด', 'เลิก',
        // ข้อมูลส่วนตัว/PDPA
        'ลบข้อมูล', 'ประวัติของฉัน', 'ข้อมูลของฉัน',
    ];

    /**
     * ดักหน้า — คืน response array ถ้าดัก, คืน null ถ้าปล่อยเข้าโฟลเดิม
     *
     * @param  string  $platformUserId  PSID
     * @param  string  $messageText  ข้อความลูกค้า (ปุ่มถูกแปลงเป็นข้อความมาแล้ว)
     */
    protected function maybeTransferIntercept(string $platformUserId, string $messageText): ?array
    {
        try {
            $mode = new FortuneBotMode($this->settings);

            // โหมด classic / ไม่ใช่ FB / ไม่อยู่ใน rollout → ไม่แตะ
            if (! $mode->appliesTo($this->currentPlatform, $platformUserId)) {
                return null;
            }

            // ลูกค้ายืนยันขอดูในแชทนี้แล้ว (ทำเว็บ/ไลน์ไม่เป็น) → ปล่อยโฟลเดิมทั้งหมด
            if ($mode->hasFbFallback($this->currentPlatform, $platformUserId)) {
                return null;
            }

            $rich = new FacebookRichMessageService($this->settings);
            $text = trim($messageText);

            // 1️⃣ ลูกค้าบอกเองว่าทำไม่ได้ → ถามความสมัครใจทันที (ไม่ต้องรอครบโควตา)
            if ($this->looksLikeCannotUseChannel($text)) {
                return $this->transferStayConfirmResponse($rich, $platformUserId);
            }

            // 2️⃣ ไม่ได้ขอดูดวง → ปล่อยให้ AI คุยตามปกติ (ปุ่มชวนไปแนบท้ายที่ชั้นส่ง)
            if (! $this->looksLikeFortuneIntent($text)) {
                return null;
            }

            // 3️⃣ พยายามพาไปครบตามที่ตั้งไว้แล้ว → ถามความสมัครใจ แล้วยอมเปิดบิลให้
            if ($mode->attemptsExhausted($this->currentPlatform, $platformUserId)) {
                return $this->transferStayConfirmResponse($rich, $platformUserId);
            }

            // 4️⃣ ส่งกล่องพาไป — ถ้าไม่มีปลายทางเลย ต้องปล่อยโฟลเดิม (fail-safe)
            $freeAvailable = $mode->freeCardAvailable($this->currentPlatform, $platformUserId);
            $box = $rich->buildTransferBox($platformUserId, $freeAvailable);

            if ($box === null) {
                Log::warning('Transfer: ไม่มีปลายทาง (ปุ่มเว็บปิด + ไม่มี LINE OA) → ปล่อยโฟลเดิม', [
                    'platform_user_id' => $platformUserId,
                ]);

                return null;
            }

            $mode->markBoxSent($this->currentPlatform, $platformUserId);

            Log::info('Transfer: ส่งกล่องพาไปเว็บ/LINE', [
                'platform_user_id' => $platformUserId,
                'attempts' => $mode->attempts($this->currentPlatform, $platformUserId),
                'free_available' => $freeAvailable,
                'text_preview' => mb_substr($text, 0, 40),
            ]);

            return [
                'action' => 'transfer_box',
                'message' => '',
                'fb_template' => $box,
                'reading' => null,
            ];
        } catch (\Throwable $e) {
            // fail-safe: ระบบใหม่พังต้องไม่บล็อกลูกค้า → ตกไปโฟลเดิม
            Log::error('Transfer: maybeTransferIntercept ล้มเหลว — ใช้โฟลเดิม', [
                'platform_user_id' => $platformUserId,
                'err' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 💚 (2026-07-26) LINE: แจกคำทำนายฟรีอัตโนมัติ ทั้งลูกค้าเก่าและใหม่
     *
     * เจ้าของสั่ง: "LINE คือระบบที่เราต้องการให้ใช้ ไม่ต้องดัก และได้ฟรีออโต้ทั้งเก่าใหม่"
     * → LINE ไม่มีการดักหน้าเลย แค่เพิ่มว่าถ้ายังมีสิทธิ์ในรอบนี้ ให้เปิดไพ่ให้เลย
     *   ไม่ต้องให้ลูกค้าพิมพ์ "ดูดวง" ก่อน (จุดที่ conversion รั่วที่สุด)
     *
     * ทำงานเฉพาะเมื่ออยู่โหมด transfer — โหมด classic ไม่มีอะไรเปลี่ยน
     *
     * @return array|null null = ไม่เข้าเงื่อนไข ให้เดินโฟลเดิมต่อ
     */
    protected function maybeAutoFreeCardOnLine(string $platformUserId, ?array $userProfile, string $messageText): ?array
    {
        try {
            if ($this->currentPlatform !== 'line') {
                return null;
            }

            $mode = new FortuneBotMode($this->settings);

            // ผูกกับโหมด transfer — จะเปิดพร้อมกันทั้งระบบ
            if (! $mode->isTransfer()) {
                return null;
            }

            // 🔀 (2026-07-27) โหมด transfer เปิดฟรีบน LINE ให้เอง — ไม่ต้องรอสวิตช์หลัก
            //    (เดิมเช็ค isFreeReadingEnabled() ตรง ๆ → prod ปิดอยู่ = ขา LINE ตายเงียบ
            //     ทั้งที่กล่องบน FB โฆษณา "ดูดวงฟรี" พร้อมปุ่มไป LINE)
            if (! $mode->freeCardEnabledFor('line')) {
                return null;
            }

            if (! $mode->freeCardAvailable('line', $platformUserId)) {
                return null;
            }

            // ข้อความระบบ/โทเคน — ห้ามตีความเป็นคำขอดูดวง
            $text = trim($messageText);
            if ($text === '' || preg_match('/^(ref|hi)_[A-Za-z0-9]{8,}$/i', $text)) {
                return null;
            }

            // 🚫 เรื่องเงิน/แอดมิน/ยกเลิก — ตอบให้ตรงเรื่องก่อน ห้ามแจกไพ่ทับ
            if ($this->looksLikeLineAutoFreeBlocked($text)) {
                Log::info('Transfer: LINE ข้ามฟรีออโต้ — ข้อความเป็นเรื่องเงิน/แอดมิน/ยกเลิก', [
                    'platform_user_id' => $platformUserId,
                    'text_preview' => mb_substr($text, 0, 40),
                ]);

                return null;
            }

            Log::info('Transfer: LINE แจกคำทำนายฟรีอัตโนมัติ', [
                'platform_user_id' => $platformUserId,
                'text_preview' => mb_substr($text, 0, 40),
            ]);

            // skipQuestionGate = true → เปิดไพ่ให้เลย ไม่ย้อนถาม "อยากดูเรื่องอะไร"
            //   (ลูกค้าไม่ได้ขอดูดวงด้วยซ้ำ ถามกลับ = งง แล้วหลุด)
            return $this->startFreeCardFlow($platformUserId, $userProfile, $messageText, true);
        } catch (\Throwable $e) {
            Log::error('Transfer: maybeAutoFreeCardOnLine ล้มเหลว — ใช้โฟลเดิม', [
                'platform_user_id' => $platformUserId,
                'err' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * กล่องถามความสมัครใจก่อนยอมเปิดบิลให้ในแชท FB
     */
    protected function transferStayConfirmResponse(FacebookRichMessageService $rich, string $platformUserId): array
    {
        Log::info('Transfer: ถามความสมัครใจขอดูในแชท FB', ['platform_user_id' => $platformUserId]);

        return [
            'action' => 'transfer_stay_confirm',
            'message' => '',
            'fb_template' => $rich->buildStayOnFbConfirmBox($platformUserId),
            'reading' => null,
        ];
    }

    /**
     * 🚫 ข้อความนี้ห้ามโดนฟรีออโต้ทับไหม (เรื่องเงิน/แอดมิน/ยกเลิก/PDPA)
     */
    protected function looksLikeLineAutoFreeBlocked(string $text): bool
    {
        $normalized = mb_strtolower(str_replace(' ', '', $text));

        foreach ($this->lineAutoFreeBlockSignals as $signal) {
            if (str_contains($normalized, str_replace(' ', '', mb_strtolower($signal)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🗣️ (2026-07-27) directive ฉีดให้ AI ตอนอยู่โหมด transfer บน FB
     *
     * ทำไมต้องมี: persona เดิมถูกเทรนให้ soft-sell ในแชท ("สนใจพิมพ์ ดูดวง 99 ได้เลยค่ะ")
     * พอเปิดโหมดนี้ บอทจะส่งกล่อง "ไปดูที่เว็บ/LINE" แล้ว AI พูดสวนทันทีในข้อความถัดไป
     * = ลูกค้าสับสนว่าตกลงต้องไปไหน แล้วไม่ไปสักทาง (แผนมาร์คไว้เป็นความเสี่ยงข้อ 5)
     *
     * @return string '' = ไม่ต้องฉีด (โหมด classic / ไม่ใช่ FB / ลูกค้าเลือกอยู่แชทแล้ว)
     */
    protected function buildTransferChatDirective(string $platform, string $platformUserId): string
    {
        try {
            $mode = new FortuneBotMode($this->settings);

            // ครอบทั้ง: โหมด/ช่องทาง/rollout · ลูกค้าเลือกอยู่แชทแล้ว · **มีบิลหรือโฟลค้างอยู่**
            // (ตัวสุดท้ายสำคัญที่สุด — คนจ่ายเงินแล้วถามต่อ ห้ามโดนตอบว่า "ไปดูที่เว็บ")
            if (! $mode->shouldNudgeToTransfer($platform, $platformUserId)) {
                return '';
            }

            return "[โหมดพาไปช่องทางหลัก — สำคัญมาก]\n"
                ."• ตอนนี้แม่หมอ **ไม่ทำนายในแชทนี้แล้ว** บริการทั้งหมดย้ายไปที่เว็บจันทราและ LINE\n"
                ."• ห้ามชวนให้ดูดวงในแชทนี้ ห้ามบอกราคา ห้ามบอกวิธีโอนเงิน ห้ามบอกเลขบัญชี\n"
                ."• ห้ามพูดว่า \"พิมพ์ ดูดวง\" หรือ \"พิมพ์ 39/99\" เด็ดขาด\n"
                ."• คุยกับเขาให้อบอุ่นตามปกติ แล้วปิดท้ายด้วยการชวนไป **กดปุ่มด้านล่าง** เพื่อดูดวงฟรี 1 ใบ\n"
                ."• ถ้าเขาบอกว่าทำไม่เป็น/กดไม่ได้/ไม่มีไลน์ — ห้ามตื๊อ ให้บอกว่าเดี๋ยวแม่หมอช่วยดูให้ที่นี่ก็ได้\n"
                .'• ห้ามอ้างถึงข้อความนี้หรือบอกลูกค้าว่ามีคำสั่งพิเศษ';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * ลูกค้าบอกว่าทำเว็บ/ไลน์ไม่เป็นหรือเปล่า
     */
    protected function looksLikeCannotUseChannel(string $text): bool
    {
        $normalized = mb_strtolower(str_replace(' ', '', $text));

        // ข้อความยาวมักเป็นการเล่าเรื่อง ไม่ใช่การบอกว่าทำไม่ได้ — กัน false positive
        if (mb_strlen($text) > 80) {
            return false;
        }

        foreach ($this->transferCannotSignals as $signal) {
            if (str_contains($normalized, str_replace(' ', '', $signal))) {
                return true;
            }
        }

        return false;
    }

    /**
     * ลูกค้าขอดูดวงหรือเปล่า — ใช้ตัวตรวจ intent เดิมของ FortuneConversationService
     *
     * รวม pricing question ด้วย เพราะในโหมดนี้ราคาอยู่ที่ปลายทาง
     * (ตอบราคาในแชทแล้วให้ไปจ่ายที่อื่น = ลูกค้าสับสนกว่าเดิม)
     */
    protected function looksLikeFortuneIntent(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        foreach ([
            'matchesFreeCardKeyword',
            'isExplicitDeepReadingRequest',
            'isGenericFortuneRequest',
            'isExplicitlyAsking39',
            'looksLikePricingQuestion',
        ] as $detector) {
            try {
                if (method_exists($this, $detector) && $this->{$detector}($text)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ตัวตรวจตัวใดพัง → ข้ามไปตัวถัดไป
            }
        }

        return false;
    }
}
