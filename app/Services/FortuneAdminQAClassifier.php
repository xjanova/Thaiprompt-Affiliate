<?php

namespace App\Services;

use App\Models\FortuneAdminQA;
use App\Models\FortuneReading;

/**
 * 🏷 FortuneAdminQAClassifier
 *
 * แปลง state ของ conversation → category ของ Q&A (8 หมวด)
 *
 * เป้าหมาย: pre-filter ก่อน cosine retrieve → AI ค้นถูกบริบทเร็วขึ้น 5-10x
 *
 * Logic priority (most specific → least):
 *   1. มี reading active?
 *      - Celtic states (4) → celtic_picking
 *      - collecting_birthdate → birthdate_help
 *      - collecting_questions/tarot → question_input
 *      - pending_payment / stripe → pre_payment
 *      - paid → payment_confirm
 *      - completed → post_reading
 *      - new/awaiting_confirmation/tier_choice/basic_done → pre_purchase
 *   2. ไม่มี reading → ใช้ keyword heuristic เลือก pre_purchase หรือ general
 *
 * Accuracy: 100% สำหรับ "มี reading active" (มาจาก state machine จริง)
 *           ~80% สำหรับ "ไม่มี reading" (ขึ้นกับ keyword)
 */
class FortuneAdminQAClassifier
{
    /**
     * 🔍 Pre-purchase keyword patterns
     *
     * ใช้ตอนลูกค้ายังไม่มี reading active — เดาว่าถามเรื่องบริการหรือไม่
     */
    private const PRE_PURCHASE_KEYWORDS = [
        'ราคา', 'เท่าไหร่', 'กี่บาท', 'ค่าบริการ',
        'ฟรี', 'ทดลอง', 'เริ่ม', 'ดูยังไง',
        'ดูดวง', 'แม่หมอ', 'ทำนาย',
        '39', '99', 'แพคเกจ', 'แพ็กเกจ',
        'celtic', 'เซลติก', 'เซลติค', 'ไพ่ 10',
        'deep', 'เชิงลึก', 'ลึก',
    ];

    /**
     * 🎯 Main classify method
     *
     * @param  FortuneReading|null  $reading  reading ของลูกค้า ณ ขณะ admin ตอบ
     * @param  string|null  $messageText  ข้อความลูกค้า (สำหรับ fallback heuristic)
     * @return string  หนึ่งใน FortuneAdminQA::CATEGORIES
     */
    public static function classify(?FortuneReading $reading, ?string $messageText = null): string
    {
        // 1) มี reading active → derive จาก conversation_status
        if ($reading !== null) {
            $categoryFromReading = self::fromReadingStatus($reading);
            if ($categoryFromReading !== null) {
                return $categoryFromReading;
            }
        }

        // 2) ไม่มี reading → keyword heuristic
        if ($messageText !== null && self::looksLikePrePurchase($messageText)) {
            return FortuneAdminQA::CATEGORY_PRE_PURCHASE;
        }

        // 3) Default — general
        return FortuneAdminQA::CATEGORY_GENERAL;
    }

    /**
     * แปลง reading conversation_status → category (return null ถ้า map ไม่ได้)
     */
    protected static function fromReadingStatus(FortuneReading $reading): ?string
    {
        $status = $reading->conversation_status;
        $readingType = $reading->reading_type;

        // 🃏 Celtic 99฿ flow — 5 states
        $celticStates = [
            FortuneReading::STATUS_CELTIC_PICKING,
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            FortuneReading::STATUS_CELTIC_GENERATING,
            FortuneReading::STATUS_CELTIC_QA_PROMPT,
        ];
        if (in_array($status, $celticStates, true)) {
            return FortuneAdminQA::CATEGORY_CELTIC_PICKING;
        }

        // 💳 Pending payment (Deep 39฿, Celtic 99฿, Stripe)
        $pendingPaymentStates = [
            FortuneReading::STATUS_PENDING_PAYMENT,
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            FortuneReading::STATUS_PENDING_STRIPE_PAYMENT,
            FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
        ];
        if (in_array($status, $pendingPaymentStates, true)) {
            return FortuneAdminQA::CATEGORY_PRE_PAYMENT;
        }

        // 📝 Collecting birthdate
        if ($status === FortuneReading::STATUS_COLLECTING_BIRTHDATE) {
            return FortuneAdminQA::CATEGORY_BIRTHDATE_HELP;
        }

        // ❓ Collecting questions / tarot
        if (in_array($status, [
            FortuneReading::STATUS_COLLECTING_QUESTIONS,
            FortuneReading::STATUS_COLLECTING_TAROT,
            FortuneReading::STATUS_DISCOVERY_CHAT,
            FortuneReading::STATUS_DISCOVERY_CONFIRM,
        ], true)) {
            return FortuneAdminQA::CATEGORY_QUESTION_INPUT;
        }

        // ✅ Paid — รอ AI ทำนาย / ยืนยันรับเงิน
        if ($status === FortuneReading::STATUS_PAID) {
            return FortuneAdminQA::CATEGORY_PAYMENT_CONFIRM;
        }

        // 📜 Completed — อ่านคำทำนายแล้ว ถามต่อ
        if ($status === FortuneReading::STATUS_COMPLETED) {
            return FortuneAdminQA::CATEGORY_POST_READING;
        }

        // 🌱 Early stages — new, awaiting_confirmation, basic_done, tier_choice
        if (in_array($status, [
            FortuneReading::STATUS_NEW,
            FortuneReading::STATUS_AWAITING_CONFIRMATION,
            FortuneReading::STATUS_BASIC_DONE,
            FortuneReading::STATUS_TIER_CHOICE,
            FortuneReading::STATUS_FREE_PREDICTED,
            FortuneReading::STATUS_FREE_DECLINED,
        ], true)) {
            return FortuneAdminQA::CATEGORY_PRE_PURCHASE;
        }

        // ไม่รู้ status — ปล่อย null ให้ caller fallback ไป heuristic
        return null;
    }

    /**
     * เช็คว่า message มีลักษณะถามเรื่องบริการ/ราคา ก่อนซื้อหรือไม่
     */
    protected static function looksLikePrePurchase(string $messageText): bool
    {
        $text = mb_strtolower(trim($messageText));
        if ($text === '') {
            return false;
        }

        foreach (self::PRE_PURCHASE_KEYWORDS as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🛠 Reverse classifier — สำหรับ retrieve hot path
     *
     * รับ context ปัจจุบันของลูกค้า (reading + messageText) → คืน category
     * ใช้ใน FortuneAIService::injectAdminQARagFewShot ก่อน retrieve
     *
     * Alias ของ classify() เพื่อความชัดเจนเวลาใช้งาน
     */
    public static function currentCategoryForRetrieve(?FortuneReading $reading, ?string $messageText = null): string
    {
        return self::classify($reading, $messageText);
    }
}
