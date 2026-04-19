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
     * @param string $userName ชื่อผู้ใช้
     * @return array Facebook Messenger API payload
     */
    public function buildWelcomeTemplate(string $userName): array
    {
        $buttons = [
            [
                'type' => 'postback',
                'title' => '🔮 ดูดวงฟรี',
                'payload' => 'MENU_FORTUNE',
            ],
            [
                'type' => 'postback',
                'title' => '💎 ดูดวงละเอียด',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],
        ];

        // เพิ่มปุ่ม LINE ถ้ามีการตั้งค่า
        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 เพิ่มเพื่อน LINE',
                'url' => $lineUrl,
            ];
        } else {
            $buttons[] = [
                'type' => 'postback',
                'title' => '📊 เช็คสิทธิ์ดูดวง',
                'payload' => 'MENU_CHECK_REMAINING',
            ];
        }

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "✨ สวัสดีค่ะ คุณ{$userName}!\n\n🔮 ยินดีต้อนรับสู่ {$this->brandName}\n\nพร้อมทำนายดวงชะตาให้คุณค่ะ\n\n🆓 ดูดวงพื้นฐาน (ฟรี)\n💎 ดูดวงละเอียด (เชิงลึก)\n\n💡 พิมพ์อะไรก็ได้มาคุยกันเลยค่ะ!",
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
     * @param string $userName ชื่อผู้ใช้
     * @param float $price ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildUpsellTemplate(string $userName, float $price): array
    {
        $priceText = number_format($price, 0);

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "💎 คุณ{$userName} ต้องการดูดวงละเอียดไหม?\n\n✅ วิเคราะห์เชิงลึก 2 คำถาม\n✅ ใช้วันเดือนปีเกิดวิเคราะห์\n✅ ทำนายแม่นยำยิ่งขึ้น\n\n💰 ราคาเพียง {$priceText} บาท",
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '💎 ดูดวงละเอียด',
                            'payload' => 'DEEP_READING_ACCEPT',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '🔮 ถามเพิ่ม (ฟรี)',
                            'payload' => 'FORTUNE_BASIC',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '❌ ไม่ต้อง',
                            'payload' => 'DEEP_READING_NO',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ============================================================
    // แสดงบิลชำระเงิน (pending_payment)
    // ============================================================

    /**
     * สร้าง Payment Template แสดงรายละเอียดบิล
     *
     * ใช้ตอน: pending_payment → แสดงข้อมูลชำระเงิน
     * ⚠️ ไม่แก้ไข logic ของ SMS Payment — แค่แสดงผลสวยขึ้น
     *
     * @param FortuneReading $reading ข้อมูลบิล
     * @param string|null $bankInfo ข้อมูลบัญชีธนาคาร
     * @return array Facebook Messenger API payload
     */
    public function buildPaymentTemplate(FortuneReading $reading, ?string $bankInfo = null): array
    {
        $amount = $reading->unique_amount ?? $reading->amount_paid ?? 0;
        $amountText = number_format($amount, 2);
        $billRef = $reading->bill_reference ?? "FR-{$reading->id}";

        $text = "📋 บิล: {$billRef} — {$amountText} บาท\n";
        $text .= "⏰ กรุณาชำระภายใน 30 นาที\n";
        $text .= "✅ โอนแล้วกดปุ่ม \"แจ้งชำระเงินแล้ว\" ด้านล่างค่ะ";

        // ตัดให้ไม่เกิน 640 ตัวอักษร (Facebook limit)
        $text = mb_substr($text, 0, 630);

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $text,
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '✅ แจ้งชำระเงินแล้ว',
                            'payload' => 'REPORT_PAYMENT',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '📊 เช็คสถานะบิล',
                            'payload' => 'CHECK_REMAINING',
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
     * @param string|null $referralUrl URL เชิญเพื่อน (ถ้ามี)
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
     * @param int $remaining จำนวนครั้งฟรีที่เหลือ
     * @param int $maxFree จำนวนครั้งฟรีทั้งหมด
     * @param int $todayCount จำนวนที่ใช้ไปวันนี้
     * @return array Facebook Messenger API payload
     */
    public function buildCheckRemainingTemplate(int $remaining, int $maxFree, int $todayCount): array
    {
        $settings = FortuneTellingSetting::getSettings();
        $freeEnabled = $settings->isFreeReadingEnabled();
        $price = number_format((float) ($settings->deep_reading_price ?? 99), 0);

        if ($freeEnabled) {
            $text = "📊 สิทธิ์ดูดวงของคุณ\n\n";
            $text .= "🆓 ดูดวงฟรีวันนี้: {$todayCount}/{$maxFree} ครั้ง\n";
            $text .= "✅ เหลืออีก: {$remaining} ครั้ง\n\n";

            if ($remaining > 0) {
                $text .= '💡 พิมพ์คำถามมาได้เลย!';
            } else {
                $text .= '💎 หมดสิทธิ์ฟรีแล้ว ลองดูดวงละเอียดสิ!';
            }
        } else {
            // ปิดบริการฟรี — ไม่พูดถึงฟรี
            $text = "💎 บริการดูดวงโดยแม่หมอจันทรา\n\n";
            $text .= "💰 ค่าครู {$price} บาท/ครั้ง\n";
            $text .= "📌 ถามได้ 2 คำถาม วิเคราะห์จากวันเกิด\n\n";
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

        $buttons[] = [
            'type' => 'postback',
            'title' => '💎 ดูดวงละเอียด',
            'payload' => 'DEEP_READING_ACCEPT',
        ];

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
     * @param string $referralUrl URL สำหรับแชร์
     * @param string|null $commissionInfo ข้อมูลค่าคอมมิชชั่น
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
     * @param float $price ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildBirthdatePromptTemplate(float $price): array
    {
        $priceText = number_format($price, 0);

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "📅 กรุณาบอกวันเดือนปีเกิด\n\nเพื่อวิเคราะห์ดวงชะตาให้แม่นยำขึ้น\n\n💡 ตัวอย่าง:\n• 15 มกราคม 2540\n• 15/01/2540\n• 15 ม.ค. 40\n\n💰 ราคาดูดวงละเอียด: {$priceText} บาท",
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '❌ ยกเลิก',
                            'payload' => 'CANCEL_DEEP',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '❓ วิธีใช้งาน',
                            'payload' => 'MENU_HELP',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '📊 เช็คสิทธิ์',
                            'payload' => 'CHECK_REMAINING',
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
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => "⏰ บิลดูดวงหมดอายุแล้วค่ะ\n\nหากต้องการดูดวงละเอียด สามารถเริ่มใหม่ได้เลยนะคะ",
                    'buttons' => [
                        [
                            'type' => 'postback',
                            'title' => '💎 เริ่มดูดวงละเอียด',
                            'payload' => 'DEEP_READING_ACCEPT',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '🔮 ดูดวงฟรี',
                            'payload' => 'FORTUNE_BASIC',
                        ],
                        [
                            'type' => 'postback',
                            'title' => '📊 เช็คสิทธิ์',
                            'payload' => 'CHECK_REMAINING',
                        ],
                    ],
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
     * @param float $price ราคาดูดวงละเอียด
     * @return array Facebook Messenger API payload
     */
    public function buildAiLimitTemplate(float $price): array
    {
        $priceText = number_format($price, 0);
        $freeEnabled = FortuneTellingSetting::getSettings()->isFreeReadingEnabled();

        $buttons = [
            [
                'type' => 'postback',
                'title' => '💎 ดูดวงละเอียด',
                'payload' => 'DEEP_READING_ACCEPT',
            ],
        ];

        $lineUrl = $this->getLineAddFriendUrl();
        if ($lineUrl) {
            $buttons[] = [
                'type' => 'web_url',
                'title' => '💚 แอด LINE รับสิทธิ์',
                'url' => $lineUrl,
            ];
        }

        // เช็คสิทธิ์ — แสดงเฉพาะเมื่อมีบริการฟรี
        if ($freeEnabled) {
            $buttons[] = [
                'type' => 'postback',
                'title' => '📊 เช็คสิทธิ์',
                'payload' => 'CHECK_REMAINING',
            ];
        }

        // ข้อความแตกต่างตามสถานะฟรี
        $text = $freeEnabled
            ? "😊 สิทธิ์ดูดวงฟรีวันนี้หมดแล้ว\n\n💎 ลองดูดวงละเอียดสิ!\n• วิเคราะห์เชิงลึก 2 คำถาม\n• ใช้วันเกิดวิเคราะห์\n\n💰 ค่าครู {$priceText} บาท\n\n💚 หรือแอด LINE เพื่อรับสิทธิ์พิเศษ!"
            : "💎 ดูดวงโดยแม่หมอจันทรา\n\n• วิเคราะห์เชิงลึก 2 คำถาม\n• ใช้วันเกิดวิเคราะห์\n• ทำนายแม่นยำ\n\n💰 ค่าครู {$priceText} บาท";

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
            ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
            ['content_type' => 'text', 'title' => '📢 เชิญเพื่อน', 'payload' => 'AFFILIATE_SHARE'],
            ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
            ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
        ];
    }

    // ============================================================
    // Helper: รอชำระเงิน (waiting_payment — เตือนซ้ำ)
    // ============================================================

    /**
     * สร้าง Waiting Payment Template (เตือนชำระเงิน)
     *
     * @param string $remainingTime เวลาที่เหลือ เช่น "25 นาที"
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

    /**
     * ดึง LINE Add Friend URL จากการตั้งค่า
     *
     * @return string|null URL สำหรับเพิ่มเพื่อน LINE
     */
    public function getLineAddFriendUrl(): ?string
    {
        // ⚠️ ใช้ line_bot_basic_id (@xxx) ไม่ใช่ line_channel_id (ตัวเลข)
        $basicId = $this->settings->line_bot_basic_id ?? null;

        if (empty($basicId)) {
            return null;
        }

        if (! str_starts_with($basicId, '@')) {
            $basicId = '@' . $basicId;
        }

        return 'https://line.me/R/ti/p/' . $basicId;
    }

    /**
     * สร้าง standard Quick Replies ตาม action
     *
     * ใช้เมื่อไม่ต้องการ Button/Generic Template — แค่ Quick Replies
     *
     * @param string $action action จาก FortuneConversationService
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
            'reading_complete', 'deep_reading_result', 'view_reading_deep' => [
                ['content_type' => 'text', 'title' => '📢 ชวนเพื่อน/รับรายได้', 'payload' => 'FORTUNE_EARN_INFO'],
                ['content_type' => 'text', 'title' => '🔗 แชร์เพจ', 'payload' => 'SHARE_PAGE'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_HUMAN'],
            ],

            'check_remaining' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
            ],
            'collecting_questions', 'need_more_questions', 'retry_question' => $this->buildQuestionQuickReplies(),
            'ai_limit', 'payment_expired' => [
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ],
            'declined', 'cancelled' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💚 แอด LINE', 'payload' => 'LINE_INVITE'],
                ['content_type' => 'text', 'title' => '📢 เชิญเพื่อน', 'payload' => 'AFFILIATE_SHARE'],
            ],
            default => null,
        };
    }
}
