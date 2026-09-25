<?php

namespace App\Support\Shop;

/**
 * ทำความสะอาด HTML ที่ผู้ขายกรอกเอง (รายละเอียดสินค้า / ท้ายร้าน) ก่อนแสดงบนหน้าสาธารณะ
 *
 * เดิมหน้าร้านใช้ strip_tags() อย่างเดียว ซึ่ง "ไม่ตัด attribute" →
 * <img src=x onerror=...> หรือ <a href="javascript:..."> หลุดไปทำงานบนโดเมนหลักได้ (stored XSS
 * ที่ขโมย session ผู้ซื้อ/แอดมินได้) จึงใช้ DOM ไล่ตัด attribute ทุกตัวที่ไม่อยู่ในรายการอนุญาต
 * และตรวจ protocol ของลิงก์/รูปหลังถอดรหัส entity แล้ว (กัน jav&#x09;ascript:)
 */
final class SafeHtml
{
    /** แท็กที่อนุญาต */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'h6',
        'a', 'img', 'blockquote', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'hr', 'del', 'sup', 'sub', 'span', 'div', 'figure', 'figcaption',
    ];

    /** attribute ที่อนุญาต (ต่อแท็กทุกตัว) */
    private const ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'title', 'width', 'height', 'colspan', 'rowspan'];

    /** แท็กที่ต้องลบทั้งก้อนพร้อมเนื้อหาข้างใน */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'noscript', 'template', 'form', 'textarea', 'select', 'button', 'input', 'link', 'meta', 'base'];

    private function __construct() {}

    /**
     * คืน HTML ที่ปลอดภัยสำหรับ {!! !!}
     *
     * @param  string|null  $html  HTML ดิบจากผู้ขาย
     * @param  int  $maxLength  ความยาวสูงสุดของผลลัพธ์
     */
    public static function clean(?string $html, int $maxLength = 50000): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (! class_exists(\DOMDocument::class)) {
            // ไม่มีส่วนขยาย DOM → แสดงเป็นข้อความล้วน (ปลอดภัยที่สุด)
            return nl2br(e(strip_tags($html)));
        }

        $previous = libxml_use_internal_errors(true);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="tp-safe-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('tp-safe-root');
        if (! $root) {
            return nl2br(e(strip_tags($html)));
        }

        self::sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return mb_substr(trim($out), 0, $maxLength);
    }

    /**
     * ไล่ทำความสะอาดลูกทุกตัวของ node (แบบวนซ้ำ)
     */
    private static function sanitizeChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // แท็กที่ไม่อนุญาต → เก็บเนื้อหาข้างใน แต่ถอดแท็กทิ้ง
                self::sanitizeChildren($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);

                if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                    $child->removeAttribute($attr->nodeName);

                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl((string) $attr->nodeValue, $name)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            if ($tag === 'a') {
                // ลิงก์ออกนอกเว็บ → เปิดแท็บใหม่ + ไม่ส่ง opener/ไม่ส่งคะแนน SEO
                $child->setAttribute('rel', 'nofollow noopener noreferrer');
                $child->setAttribute('target', '_blank');
            }

            if ($tag === 'img') {
                $child->setAttribute('loading', 'lazy');
            }

            self::sanitizeChildren($child);
        }
    }

    /**
     * URL ปลอดภัยหรือไม่ (ค่า attribute ที่ DOM ถอด entity ให้แล้ว)
     */
    private static function isSafeUrl(string $url, string $attribute): bool
    {
        // ตัดอักขระควบคุม/ช่องว่างที่เบราว์เซอร์มองข้าม (กัน "java\tscript:")
        $clean = strtolower((string) preg_replace('/[\x00-\x20\x7F]+/', '', $url));

        if ($clean === '' || str_starts_with($clean, '#') || str_starts_with($clean, '/')) {
            return true;
        }

        if (! str_contains($clean, ':')) {
            // path แบบ relative
            return true;
        }

        $allowed = $attribute === 'href' ? ['http:', 'https:', 'mailto:', 'tel:'] : ['http:', 'https:'];

        foreach ($allowed as $scheme) {
            if (str_starts_with($clean, $scheme)) {
                return true;
            }
        }

        return false;
    }
}
