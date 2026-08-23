<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSetting;

/**
 * MuOfferCardBuilder — ประกอบ "การ์ดเสนอสินค้า" ของแม่หมอ ให้แต่ละแพลตฟอร์ม
 *
 * Facebook = generic template (การ์ดเลื่อนซ้าย-ขวา มีรูป)
 * LINE     = Flex carousel (บับเบิลละชิ้น มีรูป hero)
 *
 * 🚨 กฎที่ห้ามลืมของฝั่ง Facebook
 *   1. `sendButtonTemplate()` **ไม่ห่อ attachment ให้** — payload ที่ส่งเข้าไปต้องห่อ
 *      `['attachment' => ['type' => 'template', 'payload' => [...]]]` มาเองเสมอ
 *      ลืมห่อ = กล่องหายเงียบ ไม่มี error (เคยพังมาแล้ว 5 จุด)
 *   2. Quick Reply เป็นลิงก์ไม่ได้ — ต้องใช้ template ที่มีปุ่ม `web_url` เท่านั้น
 *   3. รูปต้องเป็น https และไม่ใช่ webp — ฝั่ง LINE ไม่รองรับ webp
 *      (ของจริงในฐาน: jpg 847 · png 259 · webp 3 ⇒ ต้องกัน 3 ตัวนั้นไว้)
 *
 * 🚨 ป้ายกำกับพันธมิตร: ใส่ทุกครั้ง ปิดไม่ได้ (แก้ข้อความได้เท่านั้น)
 */
class MuOfferCardBuilder
{
    /** ข้อความนำก่อนเสนอสินค้า — owner แก้ได้ที่หลังบ้าน */
    private const SETTING_INTRO = 'fortune_mu_offer_intro';

    /** ป้ายบอกว่าเป็นลิงก์พันธมิตร — แก้ข้อความได้ แต่ปิดไม่ได้ */
    private const SETTING_DISCLOSURE = 'fortune_mu_offer_disclosure';

    /** ข้อความชวนคุยต่อหลังส่งการ์ด (ปิดการขาย) */
    private const SETTING_FOLLOW_UP = 'fortune_mu_offer_follow_up';

    private const DEFAULT_INTRO = "พบกันแล้วก็คือวาสนานะลูก 🙏\n"
        ."แม่หมอมีของเสริมดวงฝากไว้ให้ดู สร้างบุญเล็กๆ น้อยๆ — ค่าตอบแทนที่แม่หมอได้ ก็นำไปต่อบุญต่อ ลูกก็ได้ของติดตัวไว้\n"
        .'ถ้ายังไม่พร้อมดูดวงกับแม่หมอ สั่งของก็ได้นะ อยากได้อะไรบอกมาได้เลย แม่หมอหาให้';

    private const DEFAULT_DISCLOSURE = 'เป็นลิงก์พันธมิตรของ Lazada — แม่หมอได้ค่าตอบแทนเล็กน้อยเมื่อลูกสั่งซื้อ';

    private const DEFAULT_FOLLOW_UP = 'ถูกใจชิ้นไหนไหมลูก หรืออยากได้อะไรอีก บอกแม่หมอได้เลยนะ 🙏';

    /** ป้ายปุ่มบน Facebook ยาวได้ไม่เกิน 20 ตัวอักษร */
    private const FB_BUTTON_MAX = 20;

    /** ชื่อการ์ดบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_TITLE_MAX = 80;

    /** คำบรรยายใต้ชื่อบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_SUBTITLE_MAX = 80;

    /**
     * ข้อความนำก่อนเสนอของ
     */
    public function intro(): string
    {
        return trim((string) MarketplaceSetting::get(self::SETTING_INTRO, self::DEFAULT_INTRO));
    }

    /**
     * ป้ายกำกับลิงก์พันธมิตร (ค่าว่างในหลังบ้าน = กลับไปใช้ค่าเริ่มต้น ไม่ใช่ปิด)
     */
    public function disclosure(): string
    {
        $text = trim((string) MarketplaceSetting::get(self::SETTING_DISCLOSURE, ''));

        return $text !== '' ? $text : self::DEFAULT_DISCLOSURE;
    }

    /**
     * ข้อความชวนคุยต่อหลังส่งการ์ด
     */
    public function followUp(): string
    {
        return trim((string) MarketplaceSetting::get(self::SETTING_FOLLOW_UP, self::DEFAULT_FOLLOW_UP));
    }

    /**
     * ประกอบ payload การ์ดสำหรับ Facebook Messenger (generic template)
     *
     * ⚠️ ส่งต่อให้ `FacebookWebhookService::sendButtonTemplate()` ได้ตรงๆ —
     *    ห่อ attachment มาให้แล้วในนี้
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     * @return array<string,mixed>|null null = ไม่มีของที่ส่งได้
     */
    public function facebookTemplate(array $items): ?array
    {
        $elements = [];

        foreach ($items as $item) {
            $p = $item['product'];
            $image = $this->safeImageUrl($p->main_image_url);
            $url = trim((string) $p->affiliate_url);

            if ($image === null || $url === '') {
                continue; // ไม่มีรูปหรือไม่มีลิงก์ = ส่งไปก็ไม่ได้เงิน/ลูกค้าไม่เห็นของ
            }

            $elements[] = [
                'title' => mb_substr($this->cleanName($p->name), 0, self::FB_TITLE_MAX),
                'subtitle' => mb_substr($this->subtitle($p, $item['slot']), 0, self::FB_SUBTITLE_MAX),
                'image_url' => $image,
                'buttons' => [
                    [
                        'type' => 'web_url',
                        'title' => mb_substr('🛒 ดูสินค้า', 0, self::FB_BUTTON_MAX),
                        'url' => $url,
                    ],
                ],
            ];
        }

        if (empty($elements)) {
            return null;
        }

        // 🔒 ต้องห่อ attachment เอง — sendButtonTemplate ไม่ห่อให้
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'image_aspect_ratio' => 'square',
                    'elements' => $elements,
                ],
            ],
        ];
    }

    /**
     * ประกอบ Flex carousel สำหรับ LINE
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     * @return array<string,mixed>|null null = ไม่มีของที่ส่งได้
     */
    public function lineFlex(array $items): ?array
    {
        $bubbles = [];

        foreach ($items as $item) {
            $p = $item['product'];
            $image = $this->safeImageUrl($p->main_image_url);
            $url = trim((string) $p->affiliate_url);

            if ($image === null || $url === '') {
                continue;
            }

            $bubbles[] = [
                'type' => 'bubble',
                'size' => 'kilo',
                'hero' => [
                    'type' => 'image',
                    'url' => $image,
                    'size' => 'full',
                    'aspectRatio' => '1:1',
                    'aspectMode' => 'cover',
                    'action' => ['type' => 'uri', 'label' => 'ดูสินค้า', 'uri' => $url],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => mb_substr($this->cleanName($p->name), 0, 60),
                            'weight' => 'bold',
                            'size' => 'sm',
                            'wrap' => true,
                            'maxLines' => 3,
                        ],
                        [
                            'type' => 'text',
                            'text' => $this->priceLabel($p).'  ·  '.$this->slotLabel($item['slot']),
                            'size' => 'sm',
                            'color' => '#B8860B',
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'height' => 'sm',
                            'color' => '#B8860B',
                            'action' => ['type' => 'uri', 'label' => '🛒 ดูสินค้า', 'uri' => $url],
                        ],
                    ],
                ],
            ];
        }

        if (empty($bubbles)) {
            return null;
        }

        // บับเบิลเดียวห้ามห่อ carousel — LINE ต้องการ bubble ตรงๆ
        return count($bubbles) === 1
            ? $bubbles[0]
            : ['type' => 'carousel', 'contents' => $bubbles];
    }

    /**
     * ข้อความสำรอง เมื่อส่งการ์ดไม่สำเร็จ (นอกกรอบ 24 ชม. / template ถูกปฏิเสธ)
     *
     * 🚨 ต้องมีเสมอ — ค่าคงที่ `MESSAGE_TAG_USABLE` ในโค้ดฝั่ง FB ถูกปิดอยู่
     *    ⇒ นอกกรอบ 24 ชั่วโมง template ล้มแน่นอน (เคยทำปุ่มรีวิว Deep39 หายมาแล้ว)
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     */
    public function plainTextFallback(array $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $p = $item['product'];
            $url = trim((string) $p->affiliate_url);
            if ($url === '') {
                continue;
            }

            $lines[] = sprintf(
                "• %s\n  %s  %s\n  %s",
                mb_substr($this->cleanName($p->name), 0, 70),
                $this->priceLabel($p),
                $this->slotLabel($item['slot']),
                $url
            );
        }

        if (empty($lines)) {
            return '';
        }

        return implode("\n\n", $lines)."\n\n※ ".$this->disclosure();
    }

    /**
     * รูปที่ปลอดภัยพอจะส่งเข้าแชท
     *
     * เงื่อนไข: ต้องเป็น https · ต้องไม่ใช่ webp (LINE ไม่รองรับ ⇒ บับเบิลจะพัง)
     *
     * @return string|null null = ใช้รูปนี้ไม่ได้
     */
    private function safeImageUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || ! str_starts_with($url, 'https://')) {
            return null;
        }

        // ตัดพารามิเตอร์ท้าย URL ออกก่อนตรวจนามสกุล (Lazada ชอบต่อ ?x-oss-process=…)
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (preg_match('/\.webp$/i', $path)) {
            return null;
        }

        return $url;
    }

    /**
     * ตัดคำโฆษณารกๆ หน้าชื่อสินค้าออก ให้เหลือชื่อที่คนอ่านรู้เรื่อง
     *
     * ชื่อจาก Lazada มักขึ้นต้นด้วย [โปร 30 ซอง] / 【ของแท้ 100%】/ ส่งฟรี (จัดด่วนใน 2 วัน)**
     * ซึ่งกินพื้นที่ 80 ตัวอักษรของการ์ดจนชื่อของจริงถูกตัดหาย
     */
    private function cleanName(?string $name): string
    {
        $original = trim((string) $name);
        if ($original === '') {
            return '';
        }

        // วงเล็บโฆษณาหัวชื่อ (ทั้งแบบไทย/อังกฤษ/จีนเต็มความกว้าง) — ตัดซ้ำได้หลายชั้น
        $cleaned = $original;
        for ($i = 0; $i < 2; $i++) {
            $cleaned = (string) preg_replace('/^\s*(?:[\[\(【［]|\*\*)[^\]\)】］]{0,40}(?:[\]\)】］]|\*\*)\s*/u', '', $cleaned);
        }
        $cleaned = trim((string) preg_replace('/\s+/u', ' ', $cleaned));

        // 🚨 ถ้าตัดจนเหลือว่าง ต้องคืนชื่อเดิม ห้ามคืนสตริงว่าง
        //    Facebook generic template **ปฏิเสธทั้งการ์ด** ถ้า title ว่าง
        //    ⇒ ชื่อที่เป็นวงเล็บล้วน ("【ของแท้ 100%】") จะทำให้ทั้งกล่องหายเงียบ
        return $cleaned !== '' ? $cleaned : $original;
    }

    /**
     * คำบรรยายใต้ชื่อบนการ์ด Facebook — ราคา + ป้ายช่วงราคา
     */
    private function subtitle(MarketplaceProduct $p, string $slot): string
    {
        return $this->priceLabel($p).'  ·  '.$this->slotLabel($slot);
    }

    /**
     * ป้ายราคา
     */
    private function priceLabel(MarketplaceProduct $p): string
    {
        $price = (float) $p->price;

        // ราคาลงตัว → ไม่ต้องโชว์ทศนิยม ("฿92" ไม่ใช่ "฿92.00")
        // เทียบด้วย epsilon ไม่ใช่ == เพราะ decimal cast คืนค่าเป็น float
        $decimals = abs($price - round($price)) < 0.005 ? 0 : 2;

        return '฿'.number_format($price, $decimals);
    }

    /**
     * ป้ายบอกว่าเป็นตัวเลือกราคาต่ำหรือราคาสูง
     */
    private function slotLabel(string $slot): string
    {
        return match ($slot) {
            'high' => 'รุ่นพรีเมียม',
            // ใบที่ 3 = ของทั่วไปนอกสายมู ป้ายต้องไม่บอกเป็นนัยว่าเป็นเครื่องราง
            'extra' => 'แม่หมอคัดมาให้',
            default => 'เริ่มต้นประหยัด',
        };
    }
}
