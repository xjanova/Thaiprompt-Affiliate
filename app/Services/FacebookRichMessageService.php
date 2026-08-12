<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;

/**
 * Facebook Rich Message Service
 *
 * สร้าง Facebook Messenger Templates (Button, Generic, Quick Replies)
 * เทียบเท่า LineFlexMessageService แต่ใช้ Facebook Messenger Platform API
 *
 * รองรับ:
 * - Button Template (ข้อความ + สูงสุด 3 ปุ่ม)
 * - Generic Template (การ์ดพร้อมรูป + ปุ่ม, carousel ได้)
 * - Quick Replies (สูงสุด 13 ปุ่ม, 20 ตัวอักษร/ปุ่ม)
 *
 * ข้อจำกัด Facebook:
 * - Button Template: text สูงสุด 640 chars, สูงสุด 3 ปุ่ม
 * - Generic Template: สูงสุด 10 cards, title 80 chars, subtitle 80 chars
 * - Quick Replies: สูงสุด 13 ปุ่ม, title 20 chars
 * - URL buttons: ต้องเป็น HTTPS
 */
class FacebookRichMessageService
{
    protected FortuneTellingSetting $settings;

    /**
     * ชื่อแบรนด์ดูดวง
     */
    protected string $brandName;

    public function __construct(FortuneTellingSetting $settings)
    {
        $this->settings = $settings;
        $this->brandName = $settings->getFortuneBrandName() ?? 'ระบบดูดวง AI';
    }

    // ============================================================
    // ข้อความต้อนรับ (Welcome / Get Started)
    // ============================================================

    /**
     * สร้างข้อความต้อนรับพร้อม Generic Template
     *
     * ใช้ตอน: GET_STARTED postback หรือ ผู้ใช้เข้ามาครั้งแรก
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $facebookUserId  FB PSID — ใช้ตรวจว่า user คนนี้ใช้สิทธิ์ฟรีไปแล้วหรือยัง
     *                                       (ถ้า null → behave เดิม โชว์ปุ่มฟรีตาม admin setting)
     * @return array Facebook Messenger API payload
     */
    public function buildWelcomeTemplate(string $userName, ?string $facebookUserId = null): array
    {
        // ตรวจสอบ flags ของระบบ (ฟรี / Celtic Cross 99฿)
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        // 🩹 (2026-05-04) ซ่อนปุ่ม+ข้อความฟรี ถ้า user คนนี้ใช้สิทธิ์ไปแล้ว
        //    user request: "คนใช้สิทธิ์ฟรีไปแล้ว ไม่ต้องขึ้นดูฟรี + นำออกจากกล่องรายการ"
        if ($freeEnabled && $facebookUserId) {
            try {
                if (\App\Models\FortuneReading::hasUsedFreeCard('facebook', $facebookUserId)) {
                    $freeEnabled = false;
                }
            } catch (\Throwable $e) {
                // ถ้า DB error → fall back behave เดิม (ไม่ block welcome)
            }
        }
        // 🌙 (2026-05-23) Deep 39฿ ปิดเพราะลูกค้าสับสน — เช็ค toggle ก่อนโชว์บรรทัด Deep
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
        $deepPrice = (int) ($this->settings->deep_reading_price ?? 39);
        $celticPrice = 99;
        try {
            $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
            // ถ้า service ไม่พร้อม ใช้ default 99
        }

        // กันซ้อน "คุณคุณ" เมื่อไม่มีชื่อจริง
        $hasName = ! empty($userName) && $userName !== 'คุณ';
        $isLao = \App\Services\FortuneLocaleService::current() === \App\Services\FortuneLocaleService::LOCALE_LO;
        if ($isLao) {
            $greeting = $hasName
                ? "✨ ສະບາຍດີ ທ່ານ{$userName}!"
                : '✨ ສະບາຍດີ!';
        } else {
            $greeting = $hasName
                ? "✨ สวัสดีค่ะ คุณ{$userName}!"
                : '✨ สวัสดีค่ะ!';
        }

        // 🎯 Welcome buttons (FB button template max 3 ปุ่ม)
        //   ปุ่ม "ดูดวง" หลัก → tier menu (39 vs 99)
        //   ปุ่ม LINE add friend (ถ้ามี URL)
        // 🎁 (2026-05-04) ลบปุ่ม "🆓 ดูดวงฟรี" ออกจาก welcome bubble
        //   เพราะระบบฟรีได้รับเฉพาะการตอบกลับ DM react/comment เท่านั้น
        //   (auto-trigger ผ่าน tryAutoFreeCardForFirstReply — ลูกค้าไม่ต้องกดปุ่ม)
        $buttons = [];

        // ปุ่ม 1: ดูดวง (entry หลัก → tier menu)
        $buttons[] = [
            'type' => 'postback',
            'title' => $isLao ? '🔮 ເບິ່ງດວງ' : '🔮 ดูดวง',
            'payload' => 'MENU_FORTUNE',
        ];

        // 🌐 ปุ่ม 2: ดูดวงฟรีบนเว็บ (Magic Link — เฉพาะเมื่อเปิด enable_web_fortune_button)
        $webUrl = $this->getWebFortuneUrl($facebookUserId);
        if ($webUrl && count($buttons) < 3) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '🌐 ดูดวงฟรีบนเว็บ',
                'url' => $webUrl,
            ];
        }

        // ปุ่ม 3: LINE (เฉพาะเมื่อตั้งค่า URL ไว้)
        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl && count($buttons) < 3) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => $isLao ? '💚 ເພີ່ມເພື່ອນ LINE' : '💚 เพิ่มเพื่อน LINE',
                'url' => $lineUrl,
            ];
        }

        // 💰 ข้อความแนะนำบริการ — เน้นยอดเงินด้วย CAPS + emoji ขนาบ
        //    (FB Messenger ไม่รองรับ markdown bold — ใช้ visual emphasis แทน)
        $serviceLines = [];
        if ($isLao) {
            // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนบรรทัด Deep
            if ($deepEnabled) {
                $serviceLines[] = "🔹 ເບິ່ງດວງ 💰💰 {$deepPrice} BAHT 💰💰\n   📅 ວັນເດືອນປີເກີດ + 🃏 ໄພ່ຍິບຊີ 1 ໃບ";
            }
            if ($celticEnabled) {
                // 🃏 (2026-05-03) ອະທິບາຍ Celtic Cross 10 ໃບ — ໃຫ້ລູກຄ້າເຫັນຄຸນຄ່າ
                // 🌙 (2026-05-23 v3) ປະກາດກະຕິກາໃຫ້ຊັດ: 5 ຄຳຖາມ / 15 ນາທີ + ຕອບທັນທີ
                $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
                $qaWindowLao = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
                $qLimitTextLao = $maxQRaw <= 0 ? 'ບໍ່ຈຳກັດ' : "{$maxQRaw} ຄຳຖາມ";
                $serviceLines[] = "🔮 Celtic Cross 💰💰 {$celticPrice} BAHT 💰💰\n"
                    ."   🃏 ຈິດເລືອກໄພ່ 10 ໃບ ຄອບຄຸມ:\n"
                    ."   • ອະດີດ → ປັດຈຸບັນ → ອະນາຄົດ\n"
                    ."   • ອຸປະສັກ + ຕົວທ່ານ + ຄົນຮອບຂ້າງ\n"
                    ."   • ຄວາມຫວັງ&ຢ້ານ + ຜົນລັບ\n"
                    ."   ❓ ຖາມໄດ້ {$qLimitTextLao} ພາຍໃນ {$qaWindowLao} ນາທີ\n"
                    .'   ⚡ ຕອບທັນທີ ບໍ່ມີລໍຖ້າ';
            }
            // 🎁 (2026-05-04) ลบบรรทัด "🆓 ດູດວງຟຣີ" ออก — ฟรีได้เฉพาะตอบกลับ DM react/comment
        } else {
            // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนบรรทัด Deep
            if ($deepEnabled) {
                $serviceLines[] = "🔹 ดูดวง 💰💰 {$deepPrice} BAHT 💰💰\n   📅 วันเดือนปีเกิด + 🃏 ไพ่ยิปซี 1 ใบ";
            }
            if ($celticEnabled) {
                // 🃏 (2026-05-03) อธิบาย Celtic Cross 10 ใบ — ให้ลูกค้าเห็นคุณค่า
                // 🌙 (2026-05-23 v3) ประกาศกติกาให้ชัด: 5 คำถาม / 15 นาที + ตอบทันที
                $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
                $qaWindowTh = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
                $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";
                $serviceLines[] = "🔮 Celtic Cross 💰💰 {$celticPrice} BAHT 💰💰\n"
                    ."   🃏 จิตเลือกไพ่ 10 ใบ ครอบคลุม:\n"
                    ."   • อดีต → ปัจจุบัน → อนาคต\n"
                    ."   • อุปสรรค + ตัวคุณ + คนรอบข้าง\n"
                    ."   • ความหวัง&กลัว + ผลลัพธ์\n"
                    ."   ❓ ถามได้ {$qLimitText} ภายใน {$qaWindowTh} นาที\n"
                    .'   ⚡ ตอบทันที ไม่มีรอ';
            }
            // 🎁 (2026-05-04) ลบบรรทัด "🆓 ดูดวงฟรี" ออก — ฟรีได้เฉพาะตอบกลับ DM react/comment
        }
        $serviceText = implode("\n\n", $serviceLines);

        if ($isLao) {
            $welcomeText = "{$greeting}\n\n🌙 ຍິນດີຕ້ອນຮັບສູ່ {$this->brandName}\n\n{$serviceText}\n\n💡 ກົດປຸ່ມດ້ານລຸ່ມເພື່ອເລີ່ມໄດ້ເລີຍ!";
        } else {
            $welcomeText = "{$greeting}\n\n🌙 ยินดีต้อนรับสู่ {$this->brandName}\n\n{$serviceText}\n\n💡 กดปุ่มด้านล่างเพื่อเริ่มได้เลยค่ะ!";
        }

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($welcomeText, 0, 630), // safety cut < 640
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // Upsell ดูดวงละเอียด (หลัง basic_done)
    // ============================================================

    /**
     * สร้าง Upsell Template เสนอดูดวงละเอียด
     *
     * ใช้ตอน: basic_done → เสนอดูดวงเชิงลึก
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  float  $price  ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildUpsellTemplate(string $userName, float $price): array
    {
        $priceText = number_format($price, 0);
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        // กันซ้อน "คุณคุณ" — ถ้าไม่มีชื่อจริง ใช้ "คุณ" เดี่ยวๆ
        $hasName = ! empty($userName) && $userName !== 'คุณ';
        $subject = $hasName ? "คุณ{$userName}" : 'คุณ';

        $isLao = \App\Services\FortuneLocaleService::current() === \App\Services\FortuneLocaleService::LOCALE_LO;

        // สร้างปุ่ม — ซ่อนปุ่ม "ถามเพิ่ม (ฟรี)" เมื่อระบบฟรีปิดอยู่
        $buttons = [
            [
                'type' => 'postback',
                'title' => $isLao ? '💎 ເບິ່ງດວງ' : '💎 ดูดวง',
                'payload' => 'DEEP_READING_ACCEPT',
            ],
        ];

        if ($freeEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => $isLao ? '🔮 ຖາມເພີ່ມ (ຟຣີ)' : '🔮 ถามเพิ่ม (ฟรี)',
                'payload' => 'FORTUNE_BASIC',
            ];
        }

        $buttons[] = [
            'type' => 'postback',
            'title' => $isLao ? '❌ ບໍ່ເອົາ' : '❌ ไม่ต้อง',
            'payload' => 'DEEP_READING_NO',
        ];

        if ($isLao) {
            $bodyText = "💎 {$subject}ຕ້ອງການເບິ່ງດວງເຊິງເລິກບໍ?\n\n✅ 1 ຄຳຖາມໂຟກັສດຽວ — ແມ່ນຍຳກວ່າ\n✅ ດາວເຈົ້າຊະຕາ + ໄພ່ຍິບຊີທີ່ຈິດເລືອກ\n✅ ບໍ່ຍົກເມກ — ມີຫຼັກການ ພິສູດໄດ້\n\n💰 ຄ່າຄູ {$priceText} ບາດ";
        } else {
            $bodyText = "💎 {$subject}ต้องการดูดวงเชิงลึกไหม?\n\n✅ 1 คำถามโฟกัสเดียว — แม่นยำกว่า\n✅ ดาวเจ้าชนะ + ไพ่ยิปซีที่จิตเลือก\n✅ ไม่ยกเมฆ — มีหลักการณ์ พิสูจน์ได้\n\n💰 ค่าครู {$priceText} บาท";
        }

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $bodyText,
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // แสดงบิลชำระเงิน (pending_payment)
    // ============================================================

    /**
     * สร้างข้อความปิดท้ายขั้นชำระเงิน — "โอนแล้วรอ" (ไม่มีปุ่ม)
     *
     * ใช้ตอน: pending_payment / celtic_pending_payment → ส่งต่อท้าย QR
     * ⚠️ ไม่แก้ไข logic ของ SMS Payment — แค่ข้อความ
     *
     * 🌙 (2026-05-31) เอาปุ่ม "แจ้งโอนแล้ว/ยกเลิก" ออก (user request — คนแก่สับสน)
     *   - แจ้งโอน: ไม่ต้องกด — ระบบตัดบิลอัตโนมัติจาก SMS แล้วเปิดไพ่ให้เอง
     *   - ยกเลิก: ลูกค้าพิมพ์ "ยกเลิก" ได้ (handleCelticPendingPayment / handlePendingPayment)
     *   คืนค่าเป็น string ส่งผ่าน sendMessage ธรรมดา (ไม่ใช่ button template แล้ว)
     *
     * @param  FortuneReading  $reading  ข้อมูลบิล
     * @return string ข้อความสั้น เป็นมิตรกับผู้สูงอายุ
     */
    public function buildPaymentInstructionText(FortuneReading $reading): string
    {
        $amount = $reading->unique_amount ?? $reading->amount_paid ?? 0;
        $amountText = number_format($amount, 2);
        $billRef = $reading->bill_reference ?? "FR-{$reading->id}";

        // 🌐 (2026-05-03) Lao variant — สำหรับลูกค้าลาวที่ใช้ Cross-Border QR (BCEL One, LDB, JDB)
        $isLao = \App\Services\FortuneLocaleService::current() === \App\Services\FortuneLocaleService::LOCALE_LO;

        if ($isLao) {
            // 🌙 (2026-05-31) ບລັອກສະອາດ ເປັນມິດກັບຜູ້ສູງອາຍຸ — ເຫຼືອ QR + ບອກໃຫ້ລໍຖ້າ
            //   ⚠️ ເກັບໝາຍເຫດ KIP→BAHT ໄວ້ — ສຳຄັນສຳລັບ Cross-Border QR ລາວ
            $text = "📋 ບິນ: {$billRef}\n";
            $text .= "💰 *{$amountText}* ບາດ\n\n";
            $text .= "🇱🇦 ສະແກນ QR ດ້ານເທິງດ້ວຍແອັບທະນາຄານລາວ — ຍອດຂຶ້ນເອງ\n";
            $text .= "   (ແປງ KIP→BAHT ອັດຕະໂນມັດ ອາດໃຊ້ເວລາ 1-5 ນາທີ)\n";
            $text .= "   ຖ້າໂອນເອງ ໃສ່ໃຫ້ຄົບ {$amountText} ບາດ\n\n";
            $text .= '🙏 ໂອນແລ້ວລໍຖ້າບຶດໜຶ່ງເດີ ແມ່ໝໍຊິເປີດໄພ່ໃຫ້ ✨';
        } else {
            // 🌙 (2026-05-31) บล็อกสะอาด เป็นมิตรกับผู้สูงอายุ — เหลือ QR + บอกให้รอ
            //   เก็บคำเตือนทศนิยมไว้แบบสั้น (ยอดต้องตรงเป๊ะ ระบบถึงตัดบิลอัตโนมัติ)
            $text = "📋 บิล: {$billRef}\n";
            $text .= "💰 *{$amountText}* บาท\n\n";
            $text .= "📲 สแกน QR ด้านบนได้เลยค่ะ ยอดขึ้นเอง\n";
            $text .= "   (ถ้าโอนเอง ใส่ให้ตรง {$amountText} บาท)\n\n";
            $text .= '🙏 โอนแล้วรอสักครู่นะคะ เดี๋ยวแม่หมอเปิดไพ่ให้ ✨';
        }

        // ตัดความยาวกันเกิน limit (FB plain text ~2000) — ข้อความสั้นอยู่แล้ว
        return mb_substr($text, 0, 1900);
    }

    // ============================================================
    // คำทำนายเสร็จ (completed / basic_done result)
    // ============================================================

    /**
     * สร้าง Reading Result Template หลังดูดวงเสร็จ
     *
     * ใช้ตอน: completed → คำทำนายละเอียดพร้อม
     *
     * @return array Facebook Messenger API payload
     */
    public function buildReadingCompleteTemplate(): array
    {
        $buttons = [
            [
                'type' => 'postback',
                'title' => '🔮 ดูดวงอีกครั้ง',
                'payload' => 'FORTUNE_BASIC',
            ],
        ];

        // เพิ่มปุ่ม LINE
        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 แอด LINE รับสิทธิ์',
                'url' => $lineUrl,
            ];
        }

        $buttons[] = [
            'type' => 'postback',
            'title' => '📢 เชิญเพื่อนได้เงิน',
            'payload' => 'AFFILIATE_SHARE',
        ];

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "✅ ส่งคำทำนายเรียบร้อยแล้ว\n\n💚 เพิ่มเพื่อนใน LINE เพื่อดูดวงแบบสวยงาม\nและรับสิทธิ์พิเศษมากขึ้น\n\n📢 เชิญเพื่อนมาดูดวง ได้เงินจริง!",
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // เชิญแอดเพื่อน LINE (LINE Invite)
    // ============================================================

    /**
     * สร้าง LINE Invite Template
     *
     * ใช้ตอน: หลังดูดวงเสร็จ → เชิญแอด LINE
     *
     * @param  string|null  $referralUrl  URL เชิญเพื่อน (ถ้ามี)
     * @return array|null Facebook Messenger API payload (null ถ้าไม่มี LINE URL)
     */
    public function buildLineInviteTemplate(?string $referralUrl = null): ?array
    {
        $lineUrl = $this->getLineAddFriendUrl();
        if (! $lineUrl) {
            return null;
        }

        $buttons = [
            [
                'type' => 'web_url',
                'title' => '💚 เพิ่มเพื่อน LINE',
                'url' => $lineUrl,
            ],
        ];

        if ($referralUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '📢 เชิญเพื่อนได้เงิน',
                'url' => $referralUrl,
            ];
        }

        $buttons[] = [
            'type' => 'postback',
            'title' => '🔮 ดูดวงต่อ',
            'payload' => 'FORTUNE_BASIC',
        ];

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "💚 เพิ่มเพื่อนใน LINE เพื่อ:\n\n✨ ดูดวงแบบสวยงาม (Flex Message)\n📊 ติดตามสถานะการทำนาย\n💰 เชิญเพื่อนได้เงินจริง!\n🎁 รับสิทธิ์พิเศษเฉพาะ LINE",
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // เช็คสิทธิ์ดูดวง (check_remaining)
    // ============================================================

    /**
     * สร้าง Check Remaining Template แสดงสิทธิ์คงเหลือ
     *
     * @param  int  $remaining  จำนวนครั้งฟรีที่เหลือ
     * @param  int  $maxFree  จำนวนครั้งฟรีทั้งหมด
     * @param  int  $todayCount  จำนวนที่ใช้ไปวันนี้
     * @return array Facebook Messenger API payload
     */
    public function buildCheckRemainingTemplate(int $remaining, int $maxFree, int $todayCount): array
    {
        $settings = FortuneTellingSetting::getSettings();
        $freeEnabled = $settings->isFreeReadingEnabled();
        // 🌙 (2026-05-23) Round 6 — Deep/Celtic-aware CTA
        $deepEnabled = $settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
        $celticPrice = 99;
        if ($celticEnabled) {
            try {
                $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
        }
        $price = number_format((float) ($settings->deep_reading_price ?? 99), 0);

        if ($freeEnabled) {
            $text = "📊 สิทธิ์ดูดวงของคุณ\n\n";
            $text .= "🆓 ดูดวงฟรีวันนี้: {$todayCount}/{$maxFree} ครั้ง\n";
            $text .= "✅ เหลืออีก: {$remaining} ครั้ง\n\n";

            if ($remaining > 0) {
                $text .= '💡 พิมพ์คำถามมาได้เลย!';
            } elseif ($deepEnabled) {
                $text .= '💎 หมดสิทธิ์ฟรีแล้ว ลองดูดวงเชิงลึกสิ!';
            } elseif ($celticEnabled) {
                $text .= "💎 หมดสิทธิ์ฟรีแล้ว ลองไพ่ Celtic Cross 10 ใบ ({$celticPrice}฿) สิ!";
            } else {
                $text .= '🙏 หมดสิทธิ์ฟรีวันนี้แล้ว — พรุ่งนี้ลองใหม่ได้นะ';
            }
        } else {
            // ปิดบริการฟรี — ไม่พูดถึงฟรี (ใช้ brand name จาก settings)
            $qCount = \App\Services\FortuneConversationService::REQUIRED_QUESTIONS;
            $text = "💎 บริการดูดวงโดย{$this->brandName}\n\n";
            $text .= "💰 ค่าครู {$price} บาท/{$qCount} คำถาม\n";
            $text .= "📌 ดาวเจ้าชนะ + ไพ่ยิปซีที่จิตเลือก — ไม่ยกเมฆ\n\n";
            $text .= '💡 กดปุ่มด้านล่างเพื่อเริ่ม';
        }

        $buttons = [];

        // ปุ่มดูดวงฟรี — แสดงเฉพาะเมื่อเปิดบริการฟรี
        if ($freeEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '🔮 ดูดวงฟรี',
                'payload' => 'FORTUNE_BASIC',
            ];
        }

        // 🌙 (2026-05-23) Round 6 — Deep ปิด → swap เป็น Celtic ถ้าเปิด
        if ($deepEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'DEEP_READING_ACCEPT',
            ];
        } elseif ($celticEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => "ดู vip ส่วนตัว {$celticPrice}บาท",
                'payload' => 'TIER_CELTIC_99',
            ];
        }

        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 แอด LINE',
                'url' => $lineUrl,
            ];
        }

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $text,
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // Affiliate Share (เชิญเพื่อนได้เงิน)
    // ============================================================

    /**
     * สร้าง Affiliate Share Template
     *
     * ใช้ตอน: ผู้ใช้กดปุ่ม "เชิญเพื่อนได้เงิน"
     *
     * @param  string  $referralUrl  URL สำหรับแชร์
     * @param  string|null  $commissionInfo  ข้อมูลค่าคอมมิชชั่น
     * @return array Facebook Messenger API payload
     */
    public function buildAffiliateShareTemplate(string $referralUrl, ?string $commissionInfo = null): array
    {
        $text = "📢 เชิญเพื่อนมาดูดวง ได้เงินจริง!\n\n";

        if ($commissionInfo) {
            $text .= "💰 {$commissionInfo}\n\n";
        } else {
            $text .= "💰 รับค่าคอมมิชชั่นทุกครั้งที่เพื่อนดูดวง\n\n";
        }

        $text .= "🔗 แชร์ลิงก์นี้ให้เพื่อน:\n{$referralUrl}";

        $buttons = [
            [
                'type' => 'web_url',
                'title' => '🔗 เปิดลิงก์เชิญเพื่อน',
                'url' => $referralUrl,
            ],
        ];

        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 แอด LINE',
                'url' => $lineUrl,
            ];
        }

        $buttons[] = [
            'type' => 'postback',
            'title' => '🔮 ดูดวง',
            'payload' => 'FORTUNE_BASIC',
        ];

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($text, 0, 630),
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // ขอวันเกิด (collecting_birthdate)
    // ============================================================

    /**
     * สร้าง Birthdate Prompt Template
     *
     * @param  float  $price  ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildBirthdatePromptTemplate(float $price): array
    {
        // 🎯 (2026-05-17) ข้อความใหม่ตาม user spec — สั้น เน้น พ.ศ. 4 หลัก
        //   "ขณะนี้ ให้กรอกวันเดือนปีเกิด เป็นตัวเลข พศ ต้องกรอก 4 ตัว เช่น 1/1/2521"
        //   $price param เก็บไว้เพื่อ backward compat แต่ไม่ใช้ใน text แล้ว
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "🎂 ขณะนี้ ให้กรอกวันเดือนปีเกิด\n\n"
                        ."เป็นตัวเลข พ.ศ. ต้องกรอก 4 ตัว\n\n"
                        .'📅 เช่น 1/1/2521',
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '❌ ยกเลิก',
                            'payload' => 'CANCEL_DEEP',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ============================================================
    // บิลหมดอายุ (payment_expired)
    // ============================================================

    /**
     * สร้าง Payment Expired Template
     *
     * @return array Facebook Messenger API payload
     */
    public function buildPaymentExpiredTemplate(): array
    {
        // ซ่อนปุ่ม "🔮 ดูดวงฟรี" เมื่อ admin ปิดบริการฟรี
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        // 🌙 (2026-05-23) Round 6 — swap Deep button → Celtic ถ้าปิด
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

        $buttons = [];
        if ($deepEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '💎 เริ่มดูดวง',
                'payload' => 'DEEP_READING_ACCEPT',
            ];
        } elseif ($celticEnabled) {
            $celticPriceX = 99;
            try {
                $celticPriceX = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
            $buttons[] = [
                'type' => 'postback',
                'title' => "ดู vip ส่วนตัว {$celticPriceX}บาท",
                'payload' => 'TIER_CELTIC_99',
            ];
        }

        if ($freeEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '🔮 ดูดวงฟรี',
                'payload' => 'FORTUNE_BASIC',
            ];
        }
        // 🎯 Phase M — เอาปุ่ม "เช็คสิทธิ์" ออก (ซ้ำซ้อน ผู้ใช้งง)

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "⏰ บิลดูดวงหมดอายุแล้วค่ะ\n\nหากต้องการดูดวง สามารถเริ่มใหม่ได้เลยนะคะ",
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // หมดสิทธิ์ฟรี (ai_limit)
    // ============================================================

    /**
     * สร้าง AI Limit Template เมื่อหมดสิทธิ์ฟรี
     *
     * @param  float  $price  ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildAiLimitTemplate(float $price): array
    {
        $priceText = number_format($price, 0);
        $settings = FortuneTellingSetting::getSettings();
        $freeEnabled = $settings->isFreeReadingEnabled();
        // 🌙 (2026-05-23) Round 6 — toggle-aware upsell text + button
        $deepEnabled = $settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
        $celticPrice = 99;
        if ($celticEnabled) {
            try {
                $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
        }

        $buttons = [];
        if ($deepEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'DEEP_READING_ACCEPT',
            ];
        } elseif ($celticEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => "ดู vip ส่วนตัว {$celticPrice}บาท",
                'payload' => 'TIER_CELTIC_99',
            ];
        }

        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 แอด LINE รับสิทธิ์',
                'url' => $lineUrl,
            ];
        }

        // 🎯 Phase M — เอาปุ่ม "เช็คสิทธิ์" ออก (ซ้ำซ้อน ผู้ใช้งง)

        // ข้อความแตกต่างตามสถานะฟรี + Deep/Celtic toggle
        $qCount = \App\Services\FortuneConversationService::REQUIRED_QUESTIONS;

        // 🌙 (2026-05-24) Celtic spec announce — 5 คำถาม / 15 นาที (dynamic จาก settings)
        $celticMaxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
        $celticQaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        // 🌙 (2026-06-07) maxQ=0 = ไม่จำกัดคำถาม → แสดง "ไม่จำกัด" แทน "0 คำถาม"
        $celticSpec = $celticMaxQ > 0
            ? "{$celticMaxQ} คำถาม / {$celticQaWindow} นาที"
            : "ไม่จำกัดคำถาม ภายใน {$celticQaWindow} นาที";

        if ($freeEnabled) {
            if ($deepEnabled) {
                $text = "😊 สิทธิ์ดูดวงฟรีวันนี้หมดแล้ว\n\n💎 ลองดูดวงเชิงลึกสิ!\n• {$qCount} คำถามโฟกัสเดียว — แม่นยำ\n• ดาวเจ้าชนะ + ไพ่ยิปซีจริง\n\n💰 ค่าครู {$priceText} บาท\n\n💚 หรือแอด LINE เพื่อรับสิทธิ์พิเศษ!";
            } elseif ($celticEnabled) {
                $text = "😊 สิทธิ์ดูดวงฟรีวันนี้หมดแล้ว\n\n🔮 ลองไพ่ Celtic Cross 10 ใบสิ!\n• เปิดไพ่ยิปซี 10 ใบเต็มสำรับ\n• ถามได้ {$celticSpec}\n\n💰 ค่าครู {$celticPrice} บาท\n\n💚 หรือแอด LINE เพื่อรับสิทธิ์พิเศษ!";
            } else {
                $text = "😊 สิทธิ์ดูดวงฟรีวันนี้หมดแล้ว\n\n🙏 กลับมาใหม่พรุ่งนี้ได้\n\n💚 แอด LINE เพื่อรับสิทธิ์พิเศษ!";
            }
        } else {
            if ($deepEnabled) {
                $text = "💎 ดูดวงโดย{$this->brandName}\n\n• {$qCount} คำถามโฟกัสเดียว — แม่นยำ\n• ดาวเจ้าชนะ + ไพ่ยิปซี (ไม่ยกเมฆ)\n• ใช้วันเกิดวิเคราะห์\n\n💰 ค่าครู {$priceText} บาท";
            } elseif ($celticEnabled) {
                $text = "🔮 ไพ่ Celtic Cross 10 ใบ โดย{$this->brandName}\n\n• เปิดไพ่ยิปซีเต็มสำรับ\n• ถามได้ {$celticSpec}\n• ทำนายลึกซึ้ง — แม่นยำที่สุด\n\n💰 ค่าครู {$celticPrice} บาท";
            } else {
                $text = "🙏 บริการดูดวงปิดชั่วคราว\n\nกรุณากลับมาใหม่ภายหลังนะคะ";
            }
        }

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $text,
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // Quick Replies สำหรับหมวดดูดวง
    // ============================================================

    /**
     * สร้าง Quick Replies สำหรับเลือกหมวดดูดวง
     *
     * @return array Quick Replies array
     */
    public function buildCategoryQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'FORTUNE_LOVE'],
            ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'FORTUNE_WORK'],
            ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'FORTUNE_MONEY'],
            ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'FORTUNE_HEALTH'],
            ['content_type' => 'text', 'title' => '🔮 ดูดวงรวม', 'payload' => 'FORTUNE_OVERVIEW'],
        ];
    }

    /**
     * สร้าง Quick Replies สำหรับเลือกคำถาม (ดูดวงละเอียด)
     *
     * @return array Quick Replies array
     */
    public function buildQuestionQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'QUESTION_LOVE'],
            ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'QUESTION_WORK'],
            ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'QUESTION_MONEY'],
            ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'QUESTION_HEALTH'],
            ['content_type' => 'text', 'title' => '✏️ พิมพ์เอง', 'payload' => 'QUESTION_CUSTOM'],
        ];
    }

    /**
     * สร้าง Quick Replies หลังดูดวงเสร็จ (พร้อม LINE invite)
     *
     * @return array Quick Replies array
     */
    public function buildPostReadingQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '🔮 ดูดวงอีก', 'payload' => 'FORTUNE_BASIC'],
            ['content_type' => 'text', 'title' => '💎 ดูดวง', 'payload' => 'FORTUNE_DEEP'],
            ['content_type' => 'text', 'title' => '📢 เชิญเพื่อน', 'payload' => 'AFFILIATE_SHARE'],
            ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
            // 🎯 Phase M — เอาปุ่ม "เช็คสิทธิ์" ออก
        ];
    }

    // ============================================================
    // Helper: รอชำระเงิน (waiting_payment — เตือนซ้ำ)
    // ============================================================

    /**
     * สร้าง Waiting Payment Template (เตือนชำระเงิน)
     *
     * @param  string  $remainingTime  เวลาที่เหลือ เช่น "25 นาที"
     * @return array Facebook Messenger API payload
     */
    public function buildWaitingPaymentTemplate(string $remainingTime): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "⏳ ยังรอการชำระเงินอยู่ค่ะ\n\n⏰ เหลือเวลาอีก: {$remainingTime}\n\nกรุณาโอนเงินตามยอดที่แจ้งไว้ค่ะ\nระบบจะตรวจสอบอัตโนมัติ",
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '✅ แจ้งชำระแล้ว',
                            'payload' => 'REPORT_PAYMENT',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '💳 ดูบัญชีธนาคาร',
                            'payload' => 'SHOW_BANK_ACCOUNT',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '❌ ยกเลิก',
                            'payload' => 'CANCEL_PAYMENT',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ============================================================
    // ยืนยันชำระเงินสำเร็จ (payment_confirmed_wait)
    // ============================================================

    /**
     * สร้าง Payment Confirmed Template
     *
     * @return array Facebook Messenger API payload
     */
    public function buildPaymentConfirmedTemplate(): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "✅ ได้รับเงินเรียบร้อยแล้วค่ะ!\n\n🔮 กำลังวิเคราะห์ดวงชะตาให้คุณ...\nกรุณารอสักครู่ค่ะ (ประมาณ 1-2 นาที)\n\n💚 ระหว่างรอ แอด LINE เพื่อรับสิทธิ์พิเศษ!",
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '📊 เช็คสถานะ',
                            'payload' => 'CHECK_REMAINING',
                        ],
                        [
                            'type' => 'web_url',
                            'title' => '💚 เพิ่มเพื่อน LINE',
                            'url' => $this->getLineAddFriendUrl() ?: 'https://line.me',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '📢 เชิญเพื่อนได้เงิน',
                            'payload' => 'AFFILIATE_SHARE',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ============================================================
    // ปฏิเสธ/ยกเลิก (declined / cancelled)
    // ============================================================

    /**
     * สร้าง Declined Template
     *
     * @return array Facebook Messenger API payload
     */
    public function buildDeclinedTemplate(): array
    {
        $buttons = [
            [
                'type' => 'postback',
                'title' => '🔮 ดูดวงอีกครั้ง',
                'payload' => 'FORTUNE_BASIC',
            ],
        ];

        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 เพิ่มเพื่อน LINE',
                'url' => $lineUrl,
            ];
        }

        $buttons[] = [
            'type' => 'postback',
            'title' => '📢 เชิญเพื่อนได้เงิน',
            'payload' => 'AFFILIATE_SHARE',
        ];

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "ไม่เป็นไรค่ะ 😊\n\nสามารถกลับมาดูดวงได้ทุกเมื่อนะคะ\n\n💚 เพิ่มเพื่อน LINE เพื่อรับสิทธิ์พิเศษ\n📢 หรือเชิญเพื่อนมาดูดวง ได้เงินจริง!",
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    // ============================================================
    // Helpers
    // ============================================================

    // ============================================================
    // 🔀 (2026-07-26) โหมด TRANSFER — กล่องพาลูกค้าไปดูดวงฟรีที่เว็บ/LINE
    // ============================================================

    /**
     * กล่อง "ดูดวงฟรี" ที่พาออกจากแชท FB
     *
     * คืน null เมื่อ **ไม่มีปลายทางให้ไปเลย** (ปุ่มเว็บปิด + ไม่ได้ตั้ง LINE OA)
     * → ผู้เรียกต้องตกไปโฟลเดิม ห้ามส่งกล่องที่กดแล้วไม่มีอะไรเกิดขึ้น
     *
     * @param  string|null  $psid  PSID ของลูกค้า (ใช้สร้าง magic link ต่อคน)
     * @param  bool  $freeAvailable  ลูกค้าคนนี้ยังมีสิทธิ์ดูฟรีในรอบนี้ไหม
     * @return array|null Facebook Messenger payload
     */
    public function buildTransferBox(?string $psid, bool $freeAvailable = true): ?array
    {
        $webUrl = $this->getWebFortuneUrl($psid);
        $lineUrl = $this->getLineAddFriendUrl();

        if (! $webUrl && ! $lineUrl) {
            return null;
        }

        $buttons = [];

        if ($webUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => $freeAvailable ? '🎁 ดูดวงฟรีเลย' : '🔮 ไปดูที่เว็บ',
                'url' => $webUrl,
            ];
        }

        if ($lineUrl && count($buttons) < 3) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 ดูทาง LINE',
                'url' => $lineUrl,
            ];
        }

        // ทางถอย: ลูกค้าที่ทำไม่เป็นจริง ๆ กดบอกได้ตรงนี้ ไม่ต้องพิมพ์อธิบาย
        if (count($buttons) < 3) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '🙏 ทำไม่เป็น ขอดูที่นี่',
                'payload' => 'TRANSFER_STAY_FB',
            ];
        }

        $text = $freeAvailable
            ? "🌙 แม่หมอเปิดไพ่ให้ฟรี 1 ใบค่ะ\n\n"
                ."กดปุ่มด้านล่าง แม่หมอจะเปิดไพ่ให้ทันที\n"
                ."✅ ไม่ต้องสมัคร ไม่ต้องกรอกอะไร เข้าให้เองเลย\n"
                ."✅ คำทำนายเก็บไว้ ย้อนดูได้ทุกเมื่อ\n\n"
                .'เลือกทางที่ถนัดได้เลยค่ะ 👇'
            : "🌙 ตอนนี้แม่หมอย้ายไปดูให้ที่เว็บกับ LINE แล้วนะคะ\n\n"
                ."ที่นั่นแม่หมอเปิดไพ่ได้เต็มที่ เก็บคำทำนายให้ย้อนดูได้\n"
                ."และมีผังสายงานสำหรับคนที่อยากแนะนำเพื่อนด้วยค่ะ\n\n"
                .'กดปุ่มด้านล่างได้เลย 👇';

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($text, 0, 630),
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    /**
     * กล่องถามความสมัครใจ ก่อนยอมเปิดบิลให้ในแชท FB (ทางถอย)
     *
     * บอก "ความจริง" ว่าเสียอะไร — ห้ามขู่ลอย ๆ เพราะจะย้อนกลับมาเป็นเรื่องร้องเรียน
     * ถามครั้งเดียว ยืนยันแล้วจำไว้ ไม่ตื๊อซ้ำ
     */
    public function buildStayOnFbConfirmBox(?string $psid): array
    {
        $buttons = [
            [
                'type' => 'postback',
                'title' => '✅ ดูในแชทนี้',
                'payload' => 'TRANSFER_STAY_FB_CONFIRM',
            ],
        ];

        $webUrl = $this->getWebFortuneUrl($psid);
        if ($webUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '🌐 ลองอีกครั้ง',
                'url' => $webUrl,
            ];
        }

        $text = "ได้ค่ะ แม่หมอดูให้ในแชทนี้ก็ได้ 🙏\n\n"
            ."บอกไว้ก่อนนะคะว่าถ้าดูที่นี่ เจ้าชะตาจะไม่ได้:\n"
            ."• ประวัติคำทำนายย้อนดูภายหลัง\n"
            ."• กระเป๋าเงินสำหรับดูครั้งต่อไป\n"
            ."• ผังสายงาน/ค่าแนะนำเพื่อน\n"
            ."และถ้าเพจถูกจำกัด ข้อความอาจส่งไม่ถึงกันค่ะ\n\n"
            .'ยืนยันดูในแชทนี้ไหมคะ?';

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($text, 0, 630),
                    'buttons' => $buttons,
                ],
            ],
        ];
    }

    /**
     * quick reply ท้ายข้อความ AI ในโหมด transfer — ชวนเบา ๆ ไม่ยัดกล่องซ้ำ
     *
     * @return array|null null = ไม่มีปลายทาง (ไม่ต้องแนบปุ่ม)
     */
    public function buildTransferQuickReplies(?string $psid): ?array
    {
        $items = [];

        if ($this->getWebFortuneUrl($psid)) {
            $items[] = ['content_type' => 'text', 'title' => '🎁 ดูดวงฟรี', 'payload' => 'TRANSFER_GO'];
        }

        if ($this->getLineAddFriendUrl()) {
            $items[] = ['content_type' => 'text', 'title' => '💚 ดูทาง LINE', 'payload' => 'TRANSFER_GO_LINE'];
        }

        return empty($items) ? null : $items;
    }

    /**
     * ดึง LINE Add Friend URL จากการตั้งค่า
     *
     * @return string|null URL สำหรับเพิ่มเพื่อน LINE
     */
    /**
     * 🌐 (2026-07-24) สร้าง Magic Link "ดูดวงฟรีบนเว็บ" ต่อลูกค้าคนหนึ่ง
     *
     * คืน null เมื่อ: ไม่มี PSID / สวิตช์ enable_web_fortune_button ปิด / service error
     * — ทุกจุดที่เรียกจะข้ามปุ่มไปเฉยๆ (พฤติกรรมเดิม 100% เมื่อปิดสวิตช์)
     *
     * @param  string|null  $psid  Facebook PSID ของลูกค้า
     * @param  string|null  $to  🎁 (2026-07-26) path ปลายทางบนเว็บจันทรา
     *                           default = '/tarot/free' (หน้าดูดวงฟรี 1 ใบ)
     *                           — เดิมไม่ส่งเลย ลูกค้าจึงไปโผล่ /dashboard ที่ไม่มีอะไรให้ทำ
     *                           ⚠️ ต้องผ่าน regex `#^/[A-Za-z0-9/_\-]*$#` ของ
     *                           FortuneWebLinkService (ห้ามมี query string/จุด/ภาษาไทย)
     */
    public function getWebFortuneUrl(?string $psid, ?string $to = '/tarot/free'): ?string
    {
        if (empty($psid)) {
            return null;
        }

        try {
            $svc = app(\App\Services\FortuneWebLinkService::class);
            if (! $svc->isEnabled()) {
                return null;
            }

            return $svc->generateChatLink('facebook', $psid, $to);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getLineAddFriendUrl(): ?string
    {
        // ⚠️ ใช้ line_bot_basic_id (@xxx) ไม่ใช่ line_channel_id (ตัวเลข)
        $basicId = $this->settings->line_bot_basic_id ?? null;

        if (empty($basicId)) {
            return null;
        }

        if (! str_starts_with($basicId, '@')) {
            $basicId = '@'.$basicId;
        }

        return 'https://line.me/R/ti/p/'.$basicId;
    }

    /**
     * สร้าง standard Quick Replies ตาม action
     *
     * ใช้เมื่อไม่ต้องการ Button/Generic Template — แค่ Quick Replies
     *
     * @param  string  $action  action จาก FortuneConversationService
     * @return array|null Quick Replies array หรือ null
     */
    public function getQuickRepliesForAction(string $action): ?array
    {
        return match ($action) {
            'awaiting_confirmation' => $this->buildCategoryQuickReplies(),
            'basic_done' => $this->buildPostReadingQuickReplies(),
            'completed' => $this->buildPostReadingQuickReplies(),

            // 🔔 คำทำนายพร้อม — ต้องมีปุ่ม "อ่านคำทำนาย" โดดเด่น
            'fortune_ready_notification', 'reading_ready' => [
                ['content_type' => 'text', 'title' => '📖 อ่านคำทำนาย', 'payload' => 'VIEW_READING'],
                ['content_type' => 'text', 'title' => '⏰ ไว้ดูทีหลัง', 'payload' => 'VIEW_LATER'],
            ],

            // 💬 ทำนายจบแล้ว — ขอบคุณ + เชิญชวนทำการตลาด + แชร์
            //    🧹 (2026-05-01) ตัดปุ่ม "💬 คุยกับแม่หมอ" ออกจาก FB (ทับซ้อนกับ admin handover keyword detection)
            'reading_complete', 'deep_reading_result', 'view_reading_deep' => [
                ['content_type' => 'text', 'title' => '📢 ชวนเพื่อน/รับรายได้', 'payload' => 'FORTUNE_EARN_INFO'],
                ['content_type' => 'text', 'title' => '🔗 แชร์เพจ', 'payload' => 'SHARE_PAGE'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'MENU_FORTUNE'],
            ],

            // 📊 เช็คสิทธิ์ — dedupe ปุ่ม "ดูดวง" ที่เคยซ้ำกัน
            'check_remaining' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
            ],
            'collecting_questions', 'need_more_questions', 'retry_question' => $this->buildQuestionQuickReplies(),
            // dedupe — เคยมี "💎 ดูดวง" + "🔮 ดูดวง" ซ้ำ
            'ai_limit', 'payment_expired' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
            ],
            'declined', 'cancelled' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
                ['content_type' => 'text', 'title' => '📢 เชิญเพื่อน', 'payload' => 'AFFILIATE_SHARE'],
            ],
            default => null,
        };
    }
}
