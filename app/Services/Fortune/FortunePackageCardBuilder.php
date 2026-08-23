<?php

namespace App\Services\Fortune;

/**
 * FortunePackageCardBuilder — ประกอบ "การ์ดแพคเกจดูดวง" ของแม่หมอ ให้แต่ละแพลตฟอร์ม
 *
 * ทรงเดียวกับการ์ดเสนอสินค้า (MuOfferCardBuilder):
 *   Facebook = generic template (การ์ดเลื่อนซ้าย-ขวา มีรูป)
 *   LINE     = Flex carousel (บับเบิลละแพคเกจ มีรูป hero)
 *
 * 🖼️ รูปหัวการ์ดมีตัวหนังสือไทยพิมพ์ติดอยู่ในรูปแล้ว (ชื่อแพคเกจ + ราคา + กติกา 3 ข้อ)
 *   ⇒ ข้อความข้างการ์ดจึงสั้นได้ ไม่ต้องบรรยายซ้ำ
 *   ⇒ เปลี่ยนรูป = แทนไฟล์ใน public/images/fortune/packages/ (ไม่ต้องแก้โค้ด)
 *
 * 🚨 กฎที่ห้ามลืมของฝั่ง Facebook
 *   1. `sendButtonTemplate()` **ไม่ห่อ attachment ให้** — payload ต้องห่อ
 *      `['attachment' => ['type' => 'template', 'payload' => [...]]]` มาเองเสมอ
 *      ลืมห่อ = กล่องหายเงียบ ไม่มี error (เคยพังมาแล้ว 5 จุด)
 *   2. รูปต้องเป็น https และไม่ใช่ webp (LINE ไม่รองรับ webp)
 *   3. ปุ่มบนการ์ดใช้ postback payload เดิม (TIER_DEEP_39 / TIER_CELTIC_99 /
 *      TIER_CELTIC_BLACKMAGIC) — FacebookWebhookController จับอยู่แล้ว ไม่ต้องแตะ state machine
 *
 * 🚨 ราคาถูกพิมพ์ติดในรูป — ถ้า admin แก้ราคาในหลังบ้านจนไม่ตรงกับรูป
 *    `facebookTemplate()`/`lineFlex()` จะคืน null เพื่อให้ caller ตกกลับไปเมนูข้อความ
 *    (การ์ดที่โชว์ราคาผิด = ลูกค้าโอนผิดยอด แล้วบิลไม่จับคู่ — เสียหายกว่าการ์ดไม่สวย)
 */
class FortunePackageCardBuilder
{
    /**
     * ค่าที่ "พิมพ์ติดอยู่ในรูป" แล้ว — ต้องตรงกับค่าจริงในหลังบ้าน ไม่งั้นห้ามส่งการ์ด
     *
     * ⚠️ แก้ค่าพวกนี้ในหลังบ้านเมื่อไหร่ ต้องสร้างรูปใหม่แล้วแก้ค่าตรงนี้ให้ตรงกันด้วย
     *    ไม่งั้นการ์ดจะหายไปเงียบ ๆ (ตกไปเมนูข้อความ) — ดู Log::warning ใน mismatch()
     */
    private const BAKED_DEEP_PRICE = 39;

    private const BAKED_CELTIC_PRICE = 99;

    /** "คุยถามต่อได้ 7 นาที" ที่พิมพ์อยู่บนรูป 39 */
    private const BAKED_DEEP_WINDOW = 7;

    /** "ถามได้ 5 คำถาม ใน 15 นาที" ที่พิมพ์อยู่บนรูป 99 */
    private const BAKED_CELTIC_Q_LIMIT = '5 คำถาม';

    private const BAKED_CELTIC_WINDOW = 15;

    /** ไฟล์รูปหัวการ์ด (relative จาก public/) */
    private const IMAGE_DEEP = 'images/fortune/packages/pkg-deep39.jpg';

    private const IMAGE_CELTIC = 'images/fortune/packages/pkg-celtic99.jpg';

    private const IMAGE_BLACK_MAGIC = 'images/fortune/packages/pkg-blackmagic99.jpg';

    /** ชื่อการ์ดบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_TITLE_MAX = 80;

    /** คำบรรยายใต้ชื่อบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_SUBTITLE_MAX = 80;

    /** ป้ายปุ่มบน Facebook ยาวได้ไม่เกิน 20 ตัวอักษร */
    private const FB_BUTTON_MAX = 20;

    /** ป้ายปุ่มบน LINE ยาวได้ไม่เกิน 20 ตัวอักษร */
    private const LINE_BUTTON_MAX = 20;

    /** สีธีมจันทรา (juntra-payakorn) — ม่วงเข้ม + ทอง */
    private const COLOR_BG = '#160A26';

    private const COLOR_HEADER = '#241038';

    private const COLOR_GOLD = '#E7C97A';

    private const COLOR_TEXT = '#F3E9FF';

    private const COLOR_MUTED = '#C8B8DE';

    /**
     * ข้อความนำสั้น ๆ ก่อนการ์ด — รายละเอียดอยู่ในรูปแล้ว จึงไม่ต้องบรรยายซ้ำ
     *
     * @param  bool  $celticOnlyIntro  โหมด Celtic-only (ไม่ใช่ "เลือก 1 จาก N")
     */
    public function intro(bool $celticOnlyIntro = false): string
    {
        if ($celticOnlyIntro) {
            return "🌙 แม่หมอจันทรายินดีต้อนรับค่ะ\n\n"
                ."ที่นี่ไม่ใช่ดูดวงสำเร็จรูปนะคะ แม่หมอเปิดไพ่จริงให้ แล้วคุยตอบกันสดๆ\n\n"
                ."พร้อมเริ่ม กดปุ่มบนการ์ดได้เลยค่ะ\n"
                ."ยังไม่แน่ใจ ถามแม่หมอก่อนได้ ไม่คิดค่าใช้จ่ายค่ะ\n"
                .'ไม่สะดวกตอนนี้ พิมพ์ "ไว้คราวหน้า" ได้นะคะ 🙏';
        }

        // 🚪 บรรทัด "ไว้คราวหน้า" = ทางออกของลูกค้าที่ไม่อยากดู ห้ามลบ
        //    (การ์ดมีแต่ปุ่ม "เลือก" ไม่มีปุ่มปฏิเสธ — ถ้าไม่บอกทางออก ลูกค้าจะค้างในโฟลว์)
        return "🌙 แม่หมอจันทรายินดีต้อนรับค่ะ\n\n"
            ."แม่หมอเปิดไพ่จริงและตอบเองทุกข้อความ ไม่ใช่คำทำนายสำเร็จรูป\n"
            ."เลื่อนดูการ์ดแล้วกดปุ่มแพคเกจที่ถูกใจได้เลยค่ะ 👉\n\n"
            ."ยังไม่แน่ใจ ถามแม่หมอก่อนได้ ไม่คิดค่าใช้จ่ายค่ะ\n"
            .'ไม่สะดวกตอนนี้ พิมพ์ "ไว้คราวหน้า" ได้นะคะ 🙏';
    }

    /**
     * ประกอบ payload การ์ดสำหรับ Facebook Messenger (generic template carousel)
     *
     * ⚠️ ส่งต่อให้ `FacebookWebhookService::sendButtonTemplate()` ได้ตรงๆ — ห่อ attachment มาให้แล้ว
     *
     * @param  array<string,mixed>  $ctx  ดู packages()
     * @return array<string,mixed>|null null = ส่งการ์ดไม่ได้ (ราคาไม่ตรงรูป / ไม่มีแพคเกจ / รูปใช้ไม่ได้)
     */
    public function facebookTemplate(array $ctx): ?array
    {
        $packages = $this->packages($ctx);

        if ($packages === null || empty($packages)) {
            return null;
        }

        $elements = [];

        foreach ($packages as $p) {
            $image = $this->imageUrl($p['image']);

            if ($image === null) {
                continue; // ไม่มีรูป = ส่งไปการ์ดก็โล่ง สู้ตกไปเมนูข้อความดีกว่า
            }

            $elements[] = [
                'title' => mb_substr($p['fb_title'], 0, self::FB_TITLE_MAX),
                'subtitle' => mb_substr($p['fb_subtitle'], 0, self::FB_SUBTITLE_MAX),
                'image_url' => $image,
                'buttons' => [
                    [
                        'type' => 'postback',
                        'title' => mb_substr($p['button_label'], 0, self::FB_BUTTON_MAX),
                        'payload' => $p['fb_payload'],
                    ],
                ],
            ];
        }

        // 🚨 รูปหายแม้แต่ใบเดียว = การ์ดชุดไม่ครบ → ตกไปเมนูข้อความทั้งชุด
        //    (โชว์ 2 จาก 3 = ลูกค้าไม่เห็นแพคเกจที่หายไปเลย ซึ่งแย่กว่าเมนูข้อความที่ครบ)
        if (count($elements) !== count($packages)) {
            return null;
        }

        // 🔒 ต้องห่อ attachment เอง — sendButtonTemplate ไม่ห่อให้
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'image_aspect_ratio' => 'square', // รูปการ์ดเป็น 1:1 — horizontal จะครอบตัวหนังสือหาย
                    'elements' => $elements,
                ],
            ],
        ];
    }

    /**
     * ประกอบ Flex carousel สำหรับ LINE
     *
     * @param  array<string,mixed>  $ctx  ดู packages()
     * @return array<string,mixed>|null null = ส่งการ์ดไม่ได้ (caller ตกไปเมนูข้อความ)
     */
    public function lineFlex(array $ctx): ?array
    {
        $packages = $this->packages($ctx);

        if ($packages === null || empty($packages)) {
            return null;
        }

        $bubbles = [];

        foreach ($packages as $p) {
            $image = $this->imageUrl($p['image']);

            if ($image === null) {
                return null;
            }

            $bubbles[] = [
                'type' => 'bubble',
                'size' => 'mega',
                'hero' => [
                    'type' => 'image',
                    'url' => $image,
                    'size' => 'full',
                    'aspectRatio' => '1:1',
                    'aspectMode' => 'cover',
                    'action' => [
                        'type' => 'message',
                        'label' => mb_substr($p['button_label'], 0, self::LINE_BUTTON_MAX),
                        'text' => $p['line_text'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'paddingAll' => 'lg',
                    'backgroundColor' => self::COLOR_HEADER,
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $p['line_title'],
                            'color' => '#FFFFFF',
                            'size' => 'lg',
                            'weight' => 'bold',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'text',
                            'text' => $p['line_subtitle'],
                            'color' => self::COLOR_MUTED,
                            'size' => 'sm',
                            'wrap' => true,
                            'lineSpacing' => '4px',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'paddingAll' => 'lg',
                    'backgroundColor' => self::COLOR_BG,
                    'contents' => [
                        $this->lineTapButton($p['button_label'], $p['line_text']),
                    ],
                ],
            ];
        }

        // บับเบิลเดียวห้ามห่อ carousel — LINE ต้องการ bubble ตรงๆ
        return count($bubbles) === 1
            ? $bubbles[0]
            : ['type' => 'carousel', 'contents' => $bubbles];
    }

    /**
     * ปุ่มบน Flex — ใช้ box+action แทน component button
     *
     * (component button บังคับสีตัวอักษรจนกลืนพื้นทอง — เหตุผลเดียวกับ
     *  LineFortuneService::buildFlexTapButton ที่ทำไว้ตั้งแต่ 2026-07-25)
     *
     * @return array<string,mixed>
     */
    private function lineTapButton(string $label, string $text): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'backgroundColor' => self::COLOR_GOLD,
            'cornerRadius' => 'md',
            'paddingAll' => 'md',
            'action' => [
                'type' => 'message',
                'label' => mb_substr($label, 0, self::LINE_BUTTON_MAX),
                'text' => $text,
            ],
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $label,
                    'color' => '#2B1608',
                    'size' => 'md',
                    'weight' => 'bold',
                    'align' => 'center',
                    'wrap' => true,
                ],
            ],
        ];
    }

    /**
     * รายการแพคเกจที่จะขึ้นการ์ด — เรียงตามที่ลูกค้าควรเห็น (ถูก → แพง → เฉพาะทาง)
     *
     * $ctx ที่ต้องส่งมา:
     *   deep_enabled, celtic_enabled, black_magic_enabled, celtic_only_intro (bool)
     *   deep_price, celtic_price (string ที่ number_format แล้ว)
     *   deep_window, qa_window (int นาที) · q_limit_text (string เช่น "5 คำถาม")
     *
     * @param  array<string,mixed>  $ctx
     * @return array<int,array<string,mixed>>|null null = ราคาจริงไม่ตรงกับที่พิมพ์ในรูป
     */
    private function packages(array $ctx): ?array
    {
        $deepEnabled = (bool) ($ctx['deep_enabled'] ?? false);
        $celticEnabled = (bool) ($ctx['celtic_enabled'] ?? false);
        $blackMagicEnabled = (bool) ($ctx['black_magic_enabled'] ?? false);
        $celticOnlyIntro = (bool) ($ctx['celtic_only_intro'] ?? false);

        $deepPrice = $this->priceInt($ctx['deep_price'] ?? null);
        $celticPrice = $this->priceInt($ctx['celtic_price'] ?? null);

        $deepWindow = (int) ($ctx['deep_window'] ?? 7);
        $qaWindow = (int) ($ctx['qa_window'] ?? 15);
        $qLimitText = trim((string) ($ctx['q_limit_text'] ?? 'ไม่จำกัด'));

        // 🚨 ราคา/กติกาพิมพ์ติดในรูป — ไม่ตรงเมื่อไหร่ ห้ามส่งการ์ด (เช็คเฉพาะแพคเกจที่เปิดอยู่)
        //    การ์ดที่โชว์ "ถามได้ 5 คำถาม" ทั้งที่หลังบ้านตั้ง 3 = สัญญาเกินจริง ลูกค้าร้องเรียน
        if ($deepEnabled && (
            $this->mismatch('ราคา 39', $deepPrice, self::BAKED_DEEP_PRICE)
            || $this->mismatch('deep_reading_qa_window_minutes', $deepWindow, self::BAKED_DEEP_WINDOW)
        )) {
            return null;
        }

        if (($celticEnabled || $blackMagicEnabled) && (
            $this->mismatch('ราคา 99', $celticPrice, self::BAKED_CELTIC_PRICE)
            || $this->mismatch('celtic_cross_max_questions', $qLimitText, self::BAKED_CELTIC_Q_LIMIT)
            || $this->mismatch('celtic_cross_qa_window_minutes', $qaWindow, self::BAKED_CELTIC_WINDOW)
        )) {
            return null;
        }

        $packages = [];

        // 🔹 ดูพื้นดวง 39
        if ($deepEnabled) {
            $packages[] = [
                'image' => self::IMAGE_DEEP,
                'fb_title' => "🔹 ดูพื้นดวง — ค่าครู {$deepPrice} บาท",
                'fb_subtitle' => "คำนวณดาวจากวันเกิด + เปิดไพ่ 1 ใบ · คุยถามต่อได้ {$deepWindow} นาที",
                'line_title' => "🔹 ดูพื้นดวง · ฿{$deepPrice}",
                'line_subtitle' => "คำนวณดาวจากวันเกิด + เปิดไพ่ 1 ใบ\nคุยถามแม่หมอต่อได้ {$deepWindow} นาที",
                'button_label' => "🔹 เลือก {$deepPrice} บาท",
                'fb_payload' => 'TIER_DEEP_39',
                'line_text' => '39',
            ];
        }

        // 👑 ไพ่เต็มสำรับ Celtic Cross 99
        if ($celticEnabled) {
            // 🆕 Celtic-only intro — ปุ่มเป็น "เริ่มเลย" (ยืนยันราคา) ไม่ใช่ "เลือกแพคเกจ"
            $packages[] = [
                'image' => self::IMAGE_CELTIC,
                'fb_title' => "👑 ไพ่เต็มสำรับ Celtic Cross — ค่าครู {$celticPrice} บาท",
                'fb_subtitle' => "เปิดไพ่โบราณ 10 ใบ ฟันธงทีละใบ · ถามได้ {$qLimitText} ใน {$qaWindow} นาที",
                'line_title' => "👑 ไพ่เต็มสำรับ Celtic Cross · ฿{$celticPrice}",
                'line_subtitle' => "เปิดไพ่โบราณ 10 ใบ ฟันธงทีละใบ\nถามได้ {$qLimitText} ใน {$qaWindow} นาที ตอบสดไม่มีรอ",
                'button_label' => $celticOnlyIntro
                    ? "✨ เริ่มเลย {$celticPrice} บาท"
                    : "ดู vip ส่วนตัว {$celticPrice}บาท",
                'fb_payload' => 'TIER_CELTIC_99',
                'line_text' => $celticOnlyIntro ? 'เริ่มเลย' : '99',
            ];
        }

        // 🪬 ดูคุณไสย / มนต์ดำ 99 — ใช้ engine เดียวกับ Celtic แต่ล็อกเรื่องของทั้งรอบ
        if ($blackMagicEnabled) {
            $packages[] = [
                'image' => self::IMAGE_BLACK_MAGIC,
                'fb_title' => "🪬 ดูคุณไสย / มนต์ดำ / โดนของ — ค่าครู {$celticPrice} บาท",
                'fb_subtitle' => 'เปิดไพ่ 10 ใบ ล็อกทั้งสำรับ · โดนจริงไหม ชนิดของ ใครทำ วิธีแก้',
                'line_title' => "🪬 ดูคุณไสย / มนต์ดำ · ฿{$celticPrice}",
                'line_subtitle' => "เปิดไพ่ 10 ใบ ล็อกทั้งสำรับเจาะเรื่องของ\nโดนจริงไหม ชนิดของ ใครทำ และวิธีแก้",
                'button_label' => "🪬 ดูคุณไสย {$celticPrice}฿",
                'fb_payload' => 'TIER_CELTIC_BLACKMAGIC',
                'line_text' => 'ดูคุณไสย',
            ];
        }

        return $packages;
    }

    /**
     * เทียบค่าจริงกับค่าที่พิมพ์ติดในรูป — ไม่ตรงให้ log บอกว่าต้องไปสร้างรูปใหม่
     *
     * ⚠️ ถ้าไม่ log ตรงนี้ เจ้าของจะเจออาการ "การ์ดสวย ๆ หายไปเฉย ๆ" แล้วไล่หาสาเหตุไม่เจอ
     */
    private function mismatch(string $field, mixed $actual, mixed $baked): bool
    {
        if ($actual === $baked) {
            return false;
        }

        \Log::warning('การ์ดแพคเกจ: ค่าในหลังบ้านไม่ตรงกับที่พิมพ์ในรูป — ตกไปเมนูข้อความ', [
            'field' => $field,
            'actual' => $actual,
            'baked_in_image' => $baked,
            'fix' => 'สร้างรูปการ์ดใหม่ให้ตรงค่าใหม่ แล้วแก้ค่า BAKED_* ใน FortunePackageCardBuilder',
        ]);

        return true;
    }

    /**
     * แปลงราคาที่ number_format มาแล้ว ("1,099") กลับเป็น int เพื่อเทียบกับราคาในรูป
     *
     * ⚠️ ต้องตัด comma ออกก่อน ไม่งั้น (int) "1,099" = 1 → เทียบราคาเพี้ยน
     */
    private function priceInt(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) str_replace(',', '', (string) $raw);
    }

    /**
     * URL รูปแบบเต็มที่ปลอดภัยพอจะส่งเข้าแชท
     *
     * เงื่อนไข: ต้องเป็น https (ทั้ง FB และ LINE ปฏิเสธ http)
     *
     * @return string|null null = ใช้รูปนี้ไม่ได้ (เช่นเครื่อง dev ที่ APP_URL เป็น localhost)
     */
    private function imageUrl(string $relativePath): ?string
    {
        $url = asset($relativePath);

        if (str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        return str_starts_with($url, 'https://') ? $url : null;
    }
}
