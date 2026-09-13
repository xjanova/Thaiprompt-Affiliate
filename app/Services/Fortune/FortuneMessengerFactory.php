<?php

namespace App\Services\Fortune;

use App\Contracts\FortuneMessengerSender;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\TelegramFortuneService;

/**
 * ✈️ เลือก "ผู้ส่งทรง Messenger" ตามช่องทาง (2026-09-13)
 *
 * ## ทำไมต้องมี
 * โค้ดส่งข้อความนอก FortuneChannelManager (job / cron / service) เขียนแบบนี้ไว้หลายสิบจุด:
 *
 * ```php
 * // ❌ ลูกค้า Telegram ตกสาขา else → ยิง Facebook Graph API ด้วย id 'tg_…' = หายเงียบ
 * if ($platform === 'line') { $line->…; } else { (new FacebookWebhookService)->sendMessage(...); }
 * ```
 *
 * แทนที่สาขา else ด้วย `FortuneMessengerFactory::sender($platform)` → ได้บริการที่ถูกช่องทาง
 * โดยใช้เมธอดชุดเดิมของ FB ได้ทุกตัว (sendMessage / sendQuickReplies / sendImage / template …)
 *
 * ## กติกา
 *   - 'line' → null (LINE มีเส้นของตัวเอง: reply token / โควตา push — ห้ามรวมมาที่นี่)
 *   - id ทรง 'tg_…' ชนะค่า platform ที่ส่งมาเสมอ (payload job เก่า/ข้อมูลเพี้ยน)
 *   - ค่าอื่นทั้งหมด → Facebook (พฤติกรรมเดิม)
 */
final class FortuneMessengerFactory
{
    /**
     * ผู้ส่งตามช่องทาง — LINE คืน null
     */
    public static function sender(string $platform, ?string $userId = null, ?FortuneTellingSetting $settings = null): ?FortuneMessengerSender
    {
        $resolved = self::resolvePlatform($platform, $userId);

        return match ($resolved) {
            FortuneRecipient::PLATFORM_LINE => null,
            FortuneRecipient::PLATFORM_TELEGRAM => new TelegramFortuneService($settings),
            default => new FacebookWebhookService($settings),
        };
    }

    /**
     * ผู้ส่งจาก "รูปทรง id" ล้วน ๆ (ไม่มีค่า platform ให้) — LINE คืน null
     */
    public static function forUserId(string $userId, ?FortuneTellingSetting $settings = null): ?FortuneMessengerSender
    {
        return self::sender(FortuneRecipient::platformFromUserId($userId), $userId, $settings);
    }

    /**
     * ช่องทางจริงหลังซ่อม — id ทรง LINE/Telegram ชนะค่าที่ส่งมา
     */
    public static function resolvePlatform(string $platform, ?string $userId = null): string
    {
        if ($userId !== null && $userId !== '') {
            return FortuneRecipient::normalize($platform, $userId)['platform'];
        }

        $platform = strtolower(trim($platform));

        return in_array($platform, [FortuneRecipient::PLATFORM_LINE, FortuneRecipient::PLATFORM_TELEGRAM], true)
            ? $platform
            : FortuneRecipient::PLATFORM_FACEBOOK;
    }
}
