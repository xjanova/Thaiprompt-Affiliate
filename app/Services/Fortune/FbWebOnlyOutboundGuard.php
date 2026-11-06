<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * 🌐 (2026-11-03) ด่านงานขาออกเบื้องหลัง (job / cron) ใต้ FB web-only
 *
 * ปัญหา: job ที่ถูก dispatch "ก่อน" แอดมินสลับธง (เช่นทวงบิลที่หน่วงไว้ · การ์ดเสนอสินค้าหน่วง 1 ชม.
 * · กล่องอธิบายค่าครูหน่วง 4 วิ · คำทำนายจากคอมเมนต์ที่ค้างคิว) จะมาทำงาน "หลัง" สลับธง
 * ถ้าเช็คแค่ตอน dispatch = ยิงข้อความเรื่องเงิน/ขายของเข้า Messenger หลังเปิดธงไปแล้ว
 * ⇒ ทุก job/cron ต้องถามด่านนี้ใน handle() ทุกครั้ง (design 1.3 แถว "ข้อความขาออกที่เตือนหรือกระตุ้น")
 *
 * กติกา:
 *  - บล็อกเมื่อ FortuneBotMode::fbWebOnlyFor() = true เท่านั้น
 *    (ธงเปิด **และ** ช่องทางที่ resolve ได้คือ facebook) ⇒ LINE / Telegram ไม่โดนเด็ดขาด
 *  - fail-closed: สร้าง FortuneBotMode ไม่ได้ (อ่าน settings พัง) → บล็อกเฉพาะเมื่อช่องทางเป็น facebook
 *  - ด่านนี้ไม่ส่งอะไรเอง (ไม่ส่งกล่องพาไปเว็บ) — งานเบื้องหลังเป็นข้อความ "ไม่ได้ขอ"
 *    ส่งกล่องแทน = ยังเป็นการทักแบบ push อยู่ดี ⇒ เงียบดีที่สุด
 *
 * @example
 * if (FbWebOnlyOutboundGuard::blocks($platform, $userId, $reading, 'bill_reminder_job')) {
 *     return; // ไม่ส่งอะไรถึง FB
 * }
 */
final class FbWebOnlyOutboundGuard
{
    /**
     * งานขาออกนี้ต้องถูกระงับเพราะ FB web-only ไหม (true = ห้ามส่งอะไรถึง FB)
     *
     * @param  string|null  $platform  ช่องทางที่ caller คิดว่าเป็น (hint — platform ของบิลชนะ)
     * @param  string|null  $platformUserId  PSID / LINE uid / tg_…
     * @param  FortuneReading|null  $reading  บิลที่เกี่ยวข้อง (ถ้ามี)
     * @param  string  $source  ชื่องานสำหรับ log (เช่น 'bill_reminder_job')
     */
    public static function blocks(?string $platform, ?string $platformUserId, ?FortuneReading $reading = null, string $source = ''): bool
    {
        $resolved = FortuneBotMode::resolvePlatform($platform, $platformUserId, $reading);

        // ไม่ใช่ FB = ไม่เกี่ยวกับธงนี้เลย (LINE ต้องเหมือนเดิม 100%)
        if ($resolved !== FortuneBotMode::FB_WEB_ONLY_PLATFORM) {
            return false;
        }

        try {
            $blocked = (new FortuneBotMode)->fbWebOnlyFor($platform, $platformUserId, $reading);
        } catch (\Throwable $e) {
            // อ่าน settings ไม่ได้ในบริบท FB → บล็อกไว้ก่อน (ห้ามตกกลับไปทวงเงินบน FB)
            Log::critical('🌐 FB web-only: ด่านงานขาออกเช็คธงไม่ได้ — ระงับไว้ก่อน (fail-closed)', [
                'source' => $source,
                'platform_user_id' => $platformUserId,
                'reading_id' => $reading?->id,
                'error' => $e->getMessage(),
            ]);

            return true;
        }

        if ($blocked) {
            Log::info('🌐 FB web-only: ระงับงานขาออกเบื้องหลังถึง FB', [
                'source' => $source,
                'platform_user_id' => $platformUserId,
                'reading_id' => $reading?->id,
            ]);
        }

        return $blocked;
    }

    /**
     * ธงกลางเปิดอยู่ไหม (ไม่ดูลูกค้ารายคน) — ใช้กับงานที่ทั้งก้อนเป็นของ FB เช่นแคมเปญ target=facebook
     *
     * fail-closed: อ่านไม่ได้ = ถือว่าเปิด (FortuneBotMode::fbWebOnly ทำให้แล้ว)
     * ⚠️ ห้ามใช้ตัดสินงานที่อาจเป็น LINE — ใช้ blocks() ซึ่งเช็คช่องทางก่อนเสมอ
     */
    public static function flagOn(): bool
    {
        try {
            return (new FortuneBotMode)->fbWebOnly();
        } catch (\Throwable $e) {
            Log::critical('🌐 FB web-only: อ่านธงกลางไม่ได้ — ถือว่าเปิด (fail-closed)', [
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }
}
