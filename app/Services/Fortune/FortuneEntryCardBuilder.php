<?php

namespace App\Services\Fortune;

use App\Services\FortuneChartService;
use Illuminate\Support\Facades\Log;

/**
 * FortuneEntryCardBuilder — การ์ด "ทางเข้า" ของแม่หมอ สำหรับ Facebook Messenger
 *
 * มี 2 ชุด:
 *   1. entry  — การ์ด 2 ใบ [🎁 รับดวงฟรีประจำวัน] [👑 ดู VIP ส่วนตัว มีค่าครู]
 *               ใช้เป็น "กล่องแรก" ของ DM ที่ยิงตอบคอมเมนต์/กดไลก์
 *   2. days   — การ์ด 7 ใบ วันอาทิตย์…วันเสาร์ (โผล่หลังลูกค้ากดปุ่มฟรี)
 *
 * 🚨 ทำไมต้องเป็นการ์ด ไม่ใช่ "ข้อความ + รูป"
 *   Facebook Send API: 1 message = `text` **หรือ** `attachment` อย่างใดอย่างหนึ่ง
 *   และ Private Reply (`recipient.comment_id`) ใช้ได้ **ครั้งเดียวต่อ 1 คอมเมนต์**
 *   ⇒ คนที่คอมเมนต์แต่ไม่เคยทักเพจ (prod 2026-08-09 วัดได้ 98%) ได้กล่องเดียวเท่านั้น
 *   ⇒ generic template คือทางเดียวที่ยัด "รูป + ตัวหนังสือ + ปุ่ม" ลงกล่องเดียวได้
 *
 * 🚨 กฎที่ห้ามลืมของฝั่ง Facebook (ตกหลุมมาแล้วทั้งคู่)
 *   1. payload ต้องห่อ `['attachment' => ['type' => 'template', 'payload' => ...]]` มาเอง
 *      — `sendButtonTemplate()`/`sendPrivateReplyTemplate()` ไม่ห่อให้ ลืมห่อ = กล่องหายเงียบ
 *   2. รูปต้องเป็น https และ **ต้องมีไฟล์อยู่จริง** — ส่ง URL รูปที่ 404 ไป
 *      FB ตอบ error ทั้งกล่อง ⇒ ลูกค้าไม่ได้อะไรเลย (แย่กว่าตกไปเมนูข้อความ)
 *
 * ⚠️ ต่างจาก FortunePackageCardBuilder ตรงที่ **รูปชุดนี้ไม่มีราคาพิมพ์ติดในรูป**
 *    ⇒ ไม่ต้องมี guard BAKED_* · แอดมินแก้ราคาในหลังบ้านแล้วการ์ดอัปเดตตามทันที
 */
class FortuneEntryCardBuilder
{
    /** ชื่อการ์ดบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_TITLE_MAX = 80;

    /** คำบรรยายใต้ชื่อบน Facebook ยาวได้ไม่เกิน 80 ตัวอักษร */
    private const FB_SUBTITLE_MAX = 80;

    /** ป้ายปุ่มบน Facebook ยาวได้ไม่เกิน 20 ตัวอักษร */
    private const FB_BUTTON_MAX = 20;

    /** รูปหัวการ์ด "รับดวงฟรีประจำวัน" (relative จาก public/) */
    private const IMAGE_FREE = 'images/fortune/entry/entry-free-daily.jpg';

    /** รูปหัวการ์ด "ดู VIP ส่วนตัว มีค่าครู" */
    private const IMAGE_VIP = 'images/fortune/entry/entry-vip.jpg';

    /** รูปการ์ดวันเกิด — jpg เพราะ LINE ไม่รับ webp (ต้นฉบับ webp อยู่ที่ images/horoscope/birth-days) */
    private const IMAGE_DAY_PATTERN = 'images/fortune/days/day-%d.jpg';

    /** ชื่อวันตามลำดับ dayOfWeek ของ Carbon (0 = อาทิตย์) */
    private const DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    /**
     * อีโมจิประจำวัน — ต้องเป็นชุดเดียวกับ dailyBirthdayQuickReplies()
     * ไม่งั้นลูกค้าที่เคยเห็นปุ่มแบบเก่าจะงงว่าคนละระบบ
     */
    private const DAY_EMOJIS = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];

    /** สัตว์พาหนะประจำวันตามคติไทย — ชุดเดียวกับที่ใช้บนการ์ดหน้าเว็บ (_birth-day-card.blade.php) */
    private const DAY_MOUNTS = ['ราชสีห์', 'ม้า', 'กระบือ', 'ช้าง', 'กวาง', 'โค', 'เสือ'];

    /**
     * 🎁👑 การ์ด 2 ใบ "ทางเข้า" สำหรับ Facebook — ฟรี / VIP
     *
     * @param  array<string,mixed>  $ctx
     *                                    - invite_text (string|null) ข้อความชวนที่หมุนมาจาก FortuneInviteMessage
     *                                    (render แล้ว — แทนที่ชื่อลูกค้าเรียบร้อย)
     *                                    - deep_price  (int|string|null) ราคาแพคเกจถูกที่สุด ใช้เขียนบนการ์ด VIP
     * @return array<string,mixed>|null null = ส่งการ์ดไม่ได้ (caller ต้องตกกลับไปข้อความเดิมเสมอ)
     */
    public function facebookEntry(array $ctx = []): ?array
    {
        $freeImage = $this->imageUrl(self::IMAGE_FREE);
        $vipImage = $this->imageUrl(self::IMAGE_VIP);

        // 🚨 ขาดรูปใบใดใบหนึ่ง = การ์ดชุดไม่ครบ → ตกไปข้อความเดิมทั้งชุด
        //    (โชว์ใบเดียวจาก 2 = ลูกค้าไม่เห็นอีกทางเลือกเลย ซึ่งแย่กว่าข้อความที่ครบ)
        if ($freeImage === null || $vipImage === null) {
            return null;
        }

        [$freeTitle, $freeSubtitle] = $this->splitInviteText($ctx['invite_text'] ?? null);

        $vipTitle = $this->vipTitle($ctx['deep_price'] ?? null);

        return $this->wrapGeneric([
            [
                // 🔘 ป้ายปุ่มต้องมีคำว่า "ฟรี" เสมอ — เจ้าของสั่งไว้ 2026-08-07 หลังลูกค้า
                //    เหมาว่าปุ่ม 7 วันเกิดคือแพคเกจ 39 บาทแล้วมาโวยว่าโดนหลอก
                //    (หัวข้อ/คำบรรยายบนการ์ดหมุนตามข้อความชวน จึงพึ่งไม่ได้ว่าจะมีคำว่าฟรี)
                'title' => $freeTitle,
                'subtitle' => $freeSubtitle,
                'image_url' => $freeImage,
                'buttons' => [
                    $this->postback('🎁 รับดวงฟรี', 'DAILY_FREE_START'),
                ],
            ],
            [
                'title' => $vipTitle,
                'subtitle' => 'แม่หมอคำนวณดาวจากวันเกิด เปิดไพ่จริง แล้วคุยตอบสดกับเจ้าชะตาเอง',
                'image_url' => $vipImage,
                'buttons' => [
                    // ป้ายเดียวกับ dailyUpgradeQuickReply() เป๊ะ — 20 ตัวพอดี ห้ามเติมอีโมจิ
                    $this->postback('ดูvipส่วนตัวมีค่าครู', 'DAILY_VIP_PACKAGES'),
                ],
            ],
        ]);
    }

    /**
     * 📅 การ์ด 7 วันเกิดสำหรับ Facebook — โผล่หลังลูกค้ากดปุ่มฟรี
     *
     * ปุ่มใช้ payload เดิม DAILY_BDAY_0..6 ⇒ ไม่ต้องแตะ state machine
     * (postback ตกลง default arm ของ handlePostback → handleQuickReply ที่รับ payload ชุดนี้อยู่แล้ว)
     *
     * @return array<string,mixed>|null null = รูปไม่ครบ 7 ใบ → caller ตกกลับไปปุ่ม quick reply เดิม
     */
    public function facebookDays(): ?array
    {
        $elements = [];

        foreach (self::DAY_NAMES as $index => $dayName) {
            $image = $this->imageUrl(sprintf(self::IMAGE_DAY_PATTERN, $index));

            if ($image === null) {
                // 🚨 ขาดใบเดียว = คนเกิดวันนั้นไม่มีปุ่มให้กด → ตกไป quick reply ที่ครบ 7 ปุ่ม
                Log::warning('การ์ดวันเกิด: รูปไม่ครบ ตกกลับไปปุ่มข้อความ', [
                    'missing_day' => $dayName,
                    'expected' => sprintf(self::IMAGE_DAY_PATTERN, $index),
                ]);

                return null;
            }

            $chaochana = FortuneChartService::CHAOCHANA[$index] ?? [];
            $mount = self::DAY_MOUNTS[$index] ?? '';
            $element = $chaochana['element'] ?? '';
            $luckyColor = $chaochana['lucky_color'] ?? '';

            $elements[] = [
                'title' => self::DAY_EMOJIS[$index].' วัน'.$dayName,
                'subtitle' => $this->daySubtitle($mount, $element, $luckyColor),
                'image_url' => $image,
                'buttons' => [
                    $this->postback(self::DAY_EMOJIS[$index].' '.$dayName, 'DAILY_BDAY_'.$index),
                ],
            ];
        }

        return $this->wrapGeneric($elements);
    }

    /**
     * ข้อความนำหน้าการ์ดวันเกิด — ใช้กับคนที่อยู่ในกรอบ 24 ชม. (ยิงกล่องที่ 2 ได้)
     *
     * คนนอกกรอบไม่ได้บรรทัดนี้ ตัวการ์ดจึงต้องอ่านรู้เรื่องด้วยตัวเอง
     */
    public function dayIntro(): string
    {
        return "🌙 ได้เลยค่ะ ดวงประจำวันนี้แม่หมอเปิดให้ฟรี ไม่มีค่าใช้จ่าย\n"
            .'เจ้าชะตาเกิดวันอะไรคะ เลื่อนดูการ์ดแล้วกดวันเกิดของตัวเองได้เลย ✨';
    }

    // ============================================================
    // ภายใน
    // ============================================================

    /**
     * ตัดข้อความชวน (~300 ตัว) ให้ลงการ์ด — บรรทัดแรกเป็นหัวข้อ ที่เหลือเป็นคำบรรยาย
     *
     * 🎯 เจ้าของเลือกทางนี้เอง (2026-08-26): "การ์ด + ย่อคำลงในการ์ด"
     *    ⇒ ได้ตัวหนังสือ 160 ตัว (80+80) พร้อมรูปในกล่องเดียว แทนที่จะเสียคำทั้งก้อน
     *
     * ⚠️ ห้ามใช้ trim() แบบใส่ charlist ตัดอักขระไทย — ตัดกลาง byte แล้วคำท้ายพัง
     *    (ดู rule: trim() charlist หลายไบต์ = ล้างข้อความไทยทิ้ง)
     *
     * @return array{0: string, 1: string} [หัวข้อ, คำบรรยาย]
     */
    private function splitInviteText(?string $raw): array
    {
        $text = trim((string) $raw);

        if ($text === '') {
            // ไม่มีข้อความชวน (สวิตช์ปิด / DB ว่าง) → ถ้อยคำสำรองที่บอกครบว่า "ฟรี"
            return [
                '🎁 ดวงประจำวันเกิดวันนี้ แม่หมอเปิดให้ฟรี',
                'ไม่มีค่าใช้จ่าย บอกแค่วันเกิด แล้วรับคำทำนายของวันนี้ได้เลยค่ะ',
            ];
        }

        // แยกเป็นบรรทัด แล้วทิ้งบรรทัดว่าง — ข้อความชวนส่วนใหญ่ขึ้นบรรทัดใหม่คั่นประโยค
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $text) ?: []),
            fn ($line) => $line !== ''
        ));

        $head = $lines[0] ?? $text;
        $rest = trim(implode(' ', array_slice($lines, 1)));

        // บรรทัดเดียวยาว ๆ → ผ่าเป็นสองท่อน เพื่อไม่ให้ subtitle ว่างเปล่า
        if ($rest === '' && mb_strlen($head) > self::FB_TITLE_MAX) {
            $cut = $this->safeCutPos($head, self::FB_TITLE_MAX);
            $rest = trim(mb_substr($head, $cut));
            $head = mb_substr($head, 0, $cut);
        }

        if ($rest === '') {
            $rest = 'กดปุ่มด้านล่างได้เลยค่ะ ไม่มีค่าใช้จ่าย ✨';
        }

        return [
            $this->clamp($head, self::FB_TITLE_MAX),
            $this->clamp($rest, self::FB_SUBTITLE_MAX),
        ];
    }

    /**
     * หัวข้อการ์ด VIP — ราคาอ่านจากหลังบ้าน ไม่ฝังเลขไว้ในโค้ด
     *
     * ⚠️ ฝังเลขไว้ = เพี้ยนทันทีที่แอดมินแก้ราคา (แพทเทิร์นเดียวกับ dailyUpgradeQuickReply)
     */
    private function vipTitle(mixed $rawPrice): string
    {
        $price = $this->priceInt($rawPrice);

        if ($price === null || $price <= 0) {
            return '👑 ดู VIP ส่วนตัว — มีค่าครู';
        }

        return "👑 ดู VIP ส่วนตัว — มีค่าครูเริ่ม {$price} บาท";
    }

    /**
     * คำบรรยายใต้ชื่อวัน — ธาตุ + สีมงคล + สัตว์พาหนะ (ข้อมูลชุดเดียวกับการ์ดหน้าเว็บ)
     */
    private function daySubtitle(string $mount, string $element, string $luckyColor): string
    {
        $parts = [];

        if ($mount !== '') {
            $parts[] = $mount.'เป็นพาหนะ';
        }

        if ($element !== '') {
            $parts[] = 'ธาตุ'.$element;
        }

        if ($luckyColor !== '') {
            $parts[] = 'สีมงคล'.$luckyColor;
        }

        $line = implode(' · ', $parts);

        return $this->clamp($line === '' ? 'กดเพื่อรับคำทำนายวันนี้ฟรี' : $line, self::FB_SUBTITLE_MAX);
    }

    /**
     * ห่อ elements เป็น generic template ที่ส่งเข้า Send API ได้ทันที
     *
     * @param  array<int,array<string,mixed>>  $elements
     * @return array<string,mixed>|null
     */
    private function wrapGeneric(array $elements): ?array
    {
        if (empty($elements)) {
            return null;
        }

        // Facebook รับได้สูงสุด 10 การ์ดต่อ 1 carousel — เกินแล้วปฏิเสธทั้งกล่อง
        if (count($elements) > 10) {
            $elements = array_slice($elements, 0, 10);
        }

        // 🔒 ต้องห่อ attachment เอง — ตัวส่งไม่ห่อให้ (เคยพังเงียบมาแล้ว 5 จุด)
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    // รูปทุกใบเป็น 1:1 — horizontal จะครอบบน-ล่างหาย เห็นแต่กลางภาพ
                    'image_aspect_ratio' => 'square',
                    'elements' => $elements,
                ],
            ],
        ];
    }

    /**
     * ปุ่ม postback บนการ์ด — ป้ายยาวเกิน 20 ตัว FB ตัดกลางคำให้เอง จึงตัดเองให้สวยกว่า
     *
     * @return array<string,string>
     */
    private function postback(string $label, string $payload): array
    {
        return [
            'type' => 'postback',
            'title' => mb_substr($label, 0, self::FB_BUTTON_MAX),
            'payload' => $payload,
        ];
    }

    /**
     * ตัดข้อความให้พอดีเพดาน — ตัดที่ช่องว่างท้ายสุดถ้าทำได้ ไม่งั้นตัดตรง ๆ แล้วเติม …
     *
     * ภาษาไทยเขียนติดกันไม่มีช่องว่างระหว่างคำ ⇒ ส่วนใหญ่จะตกไปทางตัดตรง
     * จึงต้องผ่าน safeCutPos() เสมอ ไม่งั้นสระ/วรรณยุกต์ขาดจากพยัญชนะ
     */
    private function clamp(string $text, int $max): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $this->safeCutPos($text, $max - 1));
        $lastSpace = mb_strrpos($cut, ' ');

        // ตัดที่ช่องว่างเฉพาะเมื่อไม่ทำให้ข้อความสั้นจนเสียความ (เหลืออย่างน้อย 60% ของเพดาน)
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return trim($cut).'…';
    }

    /**
     * ถอยจุดตัดให้พ้น "สระ/วรรณยุกต์ลอย" — คืนตำแหน่งที่ตัดแล้วพยางค์ไม่ขาด
     *
     * 🐛 ปัญหาที่แก้: mb_substr ตัดตาม "ตัวอักษร" ไม่ใช่ "พยางค์"
     *    "…ไม่มีค่าใช้จ่าย" ตัดที่ 80 พอดีกลางคำ ⇒ ได้ "…ไม่ม" กับ "ีค่าใช้จ่าย"
     *    สระอี ไปโผล่หัวคำบรรยาย = ลูกค้าอ่านแล้วนึกว่าระบบพัง
     *
     * วิธี: ถ้าตัวอักษร ณ จุดตัด (ตัวแรกของท่อนหลัง) เป็น \p{M} แปลว่าจุดตัดอยู่กลางพยางค์
     *       → ถอยจนกว่าจะเจอตัวฐาน แล้วให้ทั้งพยางค์ไหลไปท่อนหลังพร้อมกัน
     *
     * ⚠️ \p{M} ครอบ U+FE0F (Variation Selector-16) ที่ติดท้ายอีโมจิด้วย —
     *    ตั้งใจให้ครอบ เพราะอีโมจิที่ถูกตัดขาดจาก VS16 ก็เพี้ยนเหมือนกัน
     */
    private function safeCutPos(string $text, int $pos): int
    {
        $pos = max(0, min($pos, mb_strlen($text)));

        while ($pos > 0 && preg_match('/^\p{M}$/u', mb_substr($text, $pos, 1)) === 1) {
            $pos--;
        }

        return $pos;
    }

    /**
     * แปลงราคาที่อาจ number_format มาแล้ว ("1,099") กลับเป็น int
     *
     * ⚠️ ต้องตัด comma ก่อน ไม่งั้น (int) "1,099" = 1 → ราคาบนการ์ดเพี้ยน
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
     * เงื่อนไข 2 ข้อ:
     *   1. ต้องเป็น https (ทั้ง FB และ LINE ปฏิเสธ http)
     *   2. **ไฟล์ต้องมีอยู่จริง** — ส่ง URL ที่ 404 ไป FB ปฏิเสธทั้งกล่อง
     *      ⇒ ลูกค้าไม่ได้อะไรเลย ซึ่งแย่กว่าตกไปข้อความเดิม
     *      (จุดนี้ต่างจาก FortunePackageCardBuilder ที่เช็คแค่ https)
     *
     * @return string|null null = ใช้รูปนี้ไม่ได้
     */
    private function imageUrl(string $relativePath): ?string
    {
        if (! is_file(public_path($relativePath))) {
            return null;
        }

        $url = asset($relativePath);

        if (str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        return str_starts_with($url, 'https://') ? $url : null;
    }
}
