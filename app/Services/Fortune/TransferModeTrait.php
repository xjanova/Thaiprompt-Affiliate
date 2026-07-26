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

            if (! $this->settings->isFreeReadingEnabled()) {
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

            Log::info('Transfer: LINE แจกคำทำนายฟรีอัตโนมัติ', [
                'platform_user_id' => $platformUserId,
                'text_preview' => mb_substr($text, 0, 40),
            ]);

            return $this->startFreeCardFlow($platformUserId, $userProfile, $messageText);
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
