<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;

/**
 * 🌐 (2026-11-03) FB web-only — CTA "ดูเรื่องใหม่อีก" ท้ายงานที่จ่ายแล้ว (design หัวข้อ 5 / งาน T12)
 *
 * ปัญหา: ข้อความปิดท้ายของงานที่จ่ายแล้ว (Celtic finale, Pro Session, ชวนจับไพ่ใหม่)
 *   ลงท้ายด้วย 'หากต้องการดูใหม่ พิมพ์ "ดูดวง"' = ชวนซื้อซ้ำในแชท Facebook
 *   ใต้ธง facebook_web_only ห้ามขาย/ออกบิลบน FB แล้ว → ต้องชวนเข้า "ห้องแม่หมอ" บนเว็บแทน
 *
 * จุดเดียวที่ตัดสินว่าใช้ CTA แบบไหน:
 *   - FB + ธงเปิด → WebHandoffCopy::reply(KIND_PAID_CTA) (ไม่มีราคา ไม่มี 'พิมพ์ ดูดวง')
 *   - อื่น ๆ ทั้งหมด (LINE, Telegram, ธงปิด) → ข้อความเดิมที่ผู้เรียกส่งมา (ห้ามเปลี่ยนพฤติกรรม LINE)
 *
 * ⚠️ ตัดสินผ่าน FbBillingGuard::appliesTo (= FortuneBotMode::fbWebOnlyFor) เท่านั้น
 *    platform ของบิลชนะ hint, PSID ตัวเลขล้วน = FB เสมอ, ตรวจพังในบริบท FB = ถือว่าเปิด (fail-closed)
 *
 * @example
 * $line = RepurchaseCta::repurchaseCta('facebook', $psid, '💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ');
 * $line = RepurchaseCta::forReading($reading);   // ใช้ platform + uid ของบิลเอง
 */
final class RepurchaseCta
{
    /** CTA เดิม (ก่อน web-only) — ใช้เป็นค่าเริ่มต้นเมื่อผู้เรียกไม่ส่งข้อความเดิมมา */
    public const LEGACY_TEXT = '💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ ✨';

    /**
     * ลูกค้าคนนี้ต้องได้ CTA แบบเว็บไหม (FB + ธง facebook_web_only เปิด)
     *
     * @param  string|null  $platform  ช่องทางที่ผู้เรียกคิดว่าเป็น (hint)
     * @param  string|null  $platformUserId  PSID / LINE uid / tg_…
     * @param  FortuneReading|null  $reading  บิลที่เพิ่งจบ (ถ้ามี — platform ของบิลชนะ hint)
     */
    public static function appliesTo(?string $platform, ?string $platformUserId, ?FortuneReading $reading = null): bool
    {
        try {
            return FbBillingGuard::appliesTo($reading, $platformUserId, $platform);
        } catch (\Throwable $e) {
            // ไม่ควรเกิด (FbBillingGuard กลืน exception เองแล้ว) — fail-closed เฉพาะบริบท FB
            return FortuneBotMode::resolvePlatform($platform, $platformUserId, $reading) === FortuneBotMode::FB_WEB_ONLY_PLATFORM;
        }
    }

    /**
     * ข้อความ CTA ซื้อซ้ำตามช่องทาง — **helper หลักของงาน T12**
     *
     * @param  string|null  $legacyText  ข้อความเดิมของจุดนั้น (null = LEGACY_TEXT)
     * @return string FB web-only = ข้อความชวนเข้าห้องแม่หมอ · อื่น ๆ = $legacyText ตามเดิมทุกตัวอักษร
     */
    public static function repurchaseCta(?string $platform, ?string $platformUserId, ?string $legacyText = null, ?FortuneReading $reading = null): string
    {
        if (self::appliesTo($platform, $platformUserId, $reading)) {
            return self::webText();
        }

        return $legacyText ?? self::LEGACY_TEXT;
    }

    /**
     * CTA ซื้อซ้ำของบิลใบนี้ (ใช้ platform + uid ของบิลเอง — เหมาะกับข้อความปิดท้ายที่ cron ส่ง)
     */
    public static function forReading(?FortuneReading $reading, ?string $legacyText = null): string
    {
        // ไม่มีบิล = ไม่รู้ช่องทาง → ข้อความเดิม (ห้ามเดาเป็น FB แล้วไปเปลี่ยนข้อความของ LINE)
        if ($reading === null) {
            return $legacyText ?? self::LEGACY_TEXT;
        }

        return self::repurchaseCta(null, null, $legacyText, $reading);
    }

    /**
     * ข้อความ CTA แบบเว็บ (design หัวข้อ 5: "CTA ปิดท้ายงานที่จ่ายแล้ว")
     */
    public static function webText(): string
    {
        return WebHandoffCopy::reply(WebHandoffCopy::KIND_PAID_CTA);
    }

    /**
     * quick reply ที่แนบท้าย CTA — FB web-only = ปุ่ม "🌙 เข้าห้องแม่หมอ" (WEB_ROOM) ปุ่มเดียว
     *
     * @param  array  $legacy  quick reply เดิมของจุดนั้น (คืนกลับตามเดิมเมื่อไม่ใช่ FB web-only)
     */
    public static function quickReplies(?string $platform, ?string $platformUserId, array $legacy = [], ?FortuneReading $reading = null): array
    {
        if (self::appliesTo($platform, $platformUserId, $reading)) {
            return WebHandoffCopy::webRoomQuickReply();
        }

        return $legacy;
    }
}
