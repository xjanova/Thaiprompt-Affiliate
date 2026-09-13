<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;

/**
 * 📮 FortuneRecipient — แหล่งความจริงเดียวว่า "บิลใบนี้ต้องส่งออกช่องทางไหน ด้วย id ตัวไหน"
 *
 * ## ทำไมต้องมีคลาสนี้
 * `fortune_readings` **ไม่มีคอลัมน์ `line_user_id`** — LINE userId ถูกเก็บใน
 * `platform_user_id` และ/หรือ `facebook_user_id` (ชื่อคอลัมน์หลอก)
 * ⇒ ท่าข้างล่างนี้ผิดเสมอ และเป็นบั๊กที่กลับมาซ้ำแล้วซ้ำอีกเพราะถูกก็อปต่อ ๆ กันไป:
 *
 * ```php
 * // ❌ ลูกค้า LINE ตกเข้าสาขา FB ทุกครั้ง — facebook_user_id ไม่เคยว่าง
 * $platform = ! empty($reading->facebook_user_id) ? 'facebook' : 'line';
 * $userId   = $reading->facebook_user_id ?: $reading->line_user_id;  // ← คอลัมน์ขวาไม่มีอยู่จริง
 * ```
 *
 * เคสจริงที่ทำให้ต้องเขียนคลาสนี้ — reading 12537 / FTU-260907-C5731 (LINE · Celtic 99฿):
 *   กล่อง ping "กำลังคิด" 2 กล่องถูกยิงเข้า **Facebook Send API ด้วย LINE userId**
 *   → 400 `(#100) Param recipient[id] must be a valid ID string` ×2 retry → **หายถาวร**
 *   ลูกค้าที่จ่าย 99฿ ไปแล้วเจอความเงียบ 40 วินาทีระหว่าง AI ทำงาน
 *
 *   ⚠️ ท่าเดียวกันเป๊ะเคยพังที่ `sendCelticThinkingAck()` และถูกแก้ไปแล้ว 2026-08-31
 *      แต่โค้ด 2 จุดที่อยู่ห่างกันไม่กี่บรรทัด (dispatch ping) ก็อปชุดเดิมไว้และไม่ได้ถูกแก้
 *      ⇒ "แก้จุดเดียวไม่พอ" — คลาสนี้คือการยุบทุกจุดให้เหลือที่เดียว
 *
 * ## กติกา
 *   1. ตัวแยกช่องทางคือ `platform` เท่านั้น — ห้ามเดาจาก "มี facebook_user_id ไหม"
 *   2. `platform` ว่าง/เพี้ยน → เดาจาก **รูปทรงของ id** (LINE = `U` + hex 32 ตัว)
 *   3. `platform='facebook'` แต่ id เป็นทรง LINE → **เชื่อ id** (แถวเก่าที่ backfill ไม่ทัน /
 *      payload ของ job ที่เข้าคิวไว้ก่อน deploy) — ข้อนี้กันบั๊กเดิมได้แม้ caller ยังส่งค่าผิดมา
 *   4. ห้ามโยน exception — พังตรงนี้ต้องไม่ทำให้ flow ทำนายทั้งใบล่ม
 */
class FortuneRecipient
{
    public const PLATFORM_FACEBOOK = 'facebook';

    public const PLATFORM_LINE = 'line';

    public const PLATFORM_TELEGRAM = 'telegram';

    /**
     * LINE userId = 'U' + hex 32 ตัว
     *
     * PSID ของ Facebook เป็นตัวเลขล้วน ⇒ ชนกันไม่ได้ ใช้แยกช่องทางจาก id ล้วน ๆ ได้อย่างปลอดภัย
     */
    public const LINE_USER_ID_PATTERN = '/^U[0-9a-f]{32}$/i';

    /**
     * ✈️ (2026-09-13) id ลูกค้า Telegram ในระบบเรา = 'tg_' + เลข chat id ของ Telegram
     *
     * ⚠️ ทำไมต้องมีคำนำหน้า: id ของ Telegram เป็น "ตัวเลขล้วน" เหมือน PSID ของ Facebook
     *    ถ้าเก็บเลขเปล่า ๆ โค้ด ~112 จุดที่แยกช่องทางด้วยรูปทรง id จะตีเป็น facebook ทั้งหมด
     *    → ยิง Facebook Send API ด้วยเลข Telegram = ข้อความหายเงียบ (บั๊กตระกูลเดียวกับ reading 12537)
     *    และเลขสองระบบชนกันได้จริง (คนละ namespace) — คำนำหน้าทำให้ชนกันไม่ได้อีก
     *
     *    ตัวเลขจริงที่ส่งเข้า Telegram Bot API ต้องตัดคำนำหน้าออกก่อนเสมอ → telegramChatId()
     */
    public const TELEGRAM_ID_PREFIX = 'tg_';

    public const TELEGRAM_USER_ID_PATTERN = '/^tg_\d{1,20}$/';

    /**
     * id ตัวนี้เป็น LINE userId หรือไม่ (ใช้เป็นด่านกันยิงผิดช่องทางด้วย)
     */
    public static function looksLikeLineUserId(?string $id): bool
    {
        $id = trim((string) $id);

        return $id !== '' && (bool) preg_match(self::LINE_USER_ID_PATTERN, $id);
    }

    /**
     * id ตัวนี้เป็นลูกค้า Telegram หรือไม่ ('tg_' + ตัวเลข)
     */
    public static function looksLikeTelegramUserId(?string $id): bool
    {
        $id = trim((string) $id);

        return $id !== '' && (bool) preg_match(self::TELEGRAM_USER_ID_PATTERN, $id);
    }

    /**
     * แปลง chat id ของ Telegram (ตัวเลข) → id ในระบบเรา ('tg_123')
     *
     * รับได้ทั้งเลขเปล่าและค่าที่มีคำนำหน้าอยู่แล้ว (idempotent) · ค่าที่ไม่ใช่ตัวเลขบวก → ''
     */
    public static function telegramUserId(int|string|null $chatId): string
    {
        $raw = trim((string) $chatId);
        if (str_starts_with($raw, self::TELEGRAM_ID_PREFIX)) {
            $raw = substr($raw, strlen(self::TELEGRAM_ID_PREFIX));
        }

        return preg_match('/^\d{1,20}$/', $raw) ? self::TELEGRAM_ID_PREFIX.$raw : '';
    }

    /**
     * id ในระบบเรา ('tg_123') → chat id ที่ส่งเข้า Telegram Bot API ('123')
     *
     * ไม่ใช่ id ของ Telegram → '' (caller ต้องข้าม ห้ามยิง API)
     */
    public static function telegramChatId(?string $userId): string
    {
        $userId = trim((string) $userId);

        return self::looksLikeTelegramUserId($userId)
            ? substr($userId, strlen(self::TELEGRAM_ID_PREFIX))
            : '';
    }

    /**
     * อีเมลภายในของบัญชีที่บอทสมัครให้ลูกค้าอัตโนมัติ (ใช้ทั้งตอนสร้างและตอนค้นหา — ต้องสูตรเดียวกัน)
     *
     *   line     → line_{uid}@thaiprompt.local
     *   telegram → tg_{chat id}@thaiprompt.local   (id ในระบบมี 'tg_' อยู่แล้ว — ไม่เติมซ้ำ)
     *   facebook → fb_{psid}@thaiprompt.local
     *
     * ⚠️ (2026-09-13) เดิมแต่ละที่เขียน `line ? 'line_' : 'fb_'` เอง → ลูกค้า Telegram ได้อีเมล fb_tg_…
     *    หรือถูกยัด id ลงคอลัมน์ line_user_id (identity ปนกันข้ามช่องทาง)
     */
    public static function localEmailFor(string $platform, string $platformUserId): string
    {
        $platformUserId = trim($platformUserId);

        if ($platform === self::PLATFORM_LINE) {
            return 'line_'.$platformUserId.'@thaiprompt.local';
        }

        if ($platform === self::PLATFORM_TELEGRAM || self::looksLikeTelegramUserId($platformUserId)) {
            // ถอด 'tg_' ออกก่อน (ถ้ามี) แล้วเติมกลับครั้งเดียว
            $chatId = (string) preg_replace('/^'.self::TELEGRAM_ID_PREFIX.'/', '', $platformUserId);

            return self::TELEGRAM_ID_PREFIX.$chatId.'@thaiprompt.local';
        }

        return 'fb_'.$platformUserId.'@thaiprompt.local';
    }

    /**
     * ช่องทางจาก "รูปทรงของ id" ล้วน ๆ — LINE / Telegram / อื่น ๆ = facebook
     *
     * ใช้แทน regex `^U[0-9a-f]{32}$ ? 'line' : 'facebook'` ที่ก็อปกันไปทั่ว (ซึ่งตีลูกค้า Telegram เป็น FB)
     */
    public static function platformFromUserId(?string $userId): string
    {
        if (self::looksLikeLineUserId($userId)) {
            return self::PLATFORM_LINE;
        }

        if (self::looksLikeTelegramUserId($userId)) {
            return self::PLATFORM_TELEGRAM;
        }

        return self::PLATFORM_FACEBOOK;
    }

    /**
     * ช่องทางของบิลใบนี้ — 'line' / 'telegram' / 'facebook' (ไม่มีค่าอื่น)
     */
    public static function platformOf(FortuneReading $reading): string
    {
        // รูปทรงของ id ชนะคอลัมน์ platform เสมอ — LINE uid / Telegram id ยิงเข้า FB ไม่ได้อยู่แล้ว
        // (ตัวที่ใช้ดูรูปทรง = platform_user_id ก่อน แล้วค่อย facebook_user_id — ลำดับเดิม)
        $candidate = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
        $byShape = self::platformFromUserId($candidate);
        if ($byShape !== self::PLATFORM_FACEBOOK) {
            return $byShape;
        }

        $platform = strtolower(trim((string) $reading->platform));
        // ⚠️ platform='telegram' แต่ id ไม่ใช่ทรง 'tg_' = ข้อมูลเพี้ยน → ห้ามเชื่อ (ยิงไปก็ไม่ถึง)
        //    ตกลงไปค่าเริ่มต้นเดิม (facebook) เหมือนแถวที่ platform ว่าง
        if (in_array($platform, [self::PLATFORM_LINE, self::PLATFORM_FACEBOOK], true)) {
            return $platform;
        }

        // ค่าเริ่มต้นของคอลัมน์คือ 'facebook' อยู่แล้ว — คงพฤติกรรมเดิมไว้
        return self::PLATFORM_FACEBOOK;
    }

    /**
     * user id ที่ใช้ส่งจริงตามช่องทางที่ resolve ได้ ('' = ส่งไม่ได้ caller ต้องข้าม)
     */
    public static function userIdOf(FortuneReading $reading): string
    {
        return match (self::platformOf($reading)) {
            self::PLATFORM_LINE => (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: ''),
            // Telegram: เอาเฉพาะคอลัมน์ที่เป็นทรง 'tg_' จริง (กันหยิบเลขอื่นที่ค้างในอีกคอลัมน์)
            self::PLATFORM_TELEGRAM => self::looksLikeTelegramUserId((string) $reading->facebook_user_id)
                ? (string) $reading->facebook_user_id
                : (self::looksLikeTelegramUserId((string) $reading->platform_user_id) ? (string) $reading->platform_user_id : ''),
            default => (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: ''),
        };
    }

    /**
     * คู่ (ช่องทาง, id) ของบิล — ใช้แทนโค้ด 4-6 บรรทัดที่เคยก็อปกันไปทั่ว
     *
     * @return array{platform:string,user_id:string}
     */
    public static function resolve(FortuneReading $reading): array
    {
        return [
            'platform' => self::platformOf($reading),
            'user_id' => self::userIdOf($reading),
        ];
    }

    /**
     * ซ่อมคู่ (ช่องทาง, id) ที่ได้มาจากที่อื่นแล้ว — เช่น payload ของ job ที่ serialize ไว้
     *
     * 🛟 จำเป็นตอน deploy: job ที่เข้าคิวไว้ **ก่อน** โค้ดใหม่ขึ้น ยังถือค่า platform ผิดติดตัวมา
     *    ถ้าไม่ซ่อมตรงนี้ ของค้างในคิวจะยังยิงผิดช่องทางต่ออีกหลายนาทีหลัง deploy
     *
     * @return array{platform:string,user_id:string}
     */
    public static function normalize(?string $platform, ?string $userId): array
    {
        $userId = trim((string) $userId);
        $platform = strtolower(trim((string) $platform));

        $byShape = self::platformFromUserId($userId);

        if ($byShape !== self::PLATFORM_FACEBOOK) {
            // id บอกช่องทางชัด (LINE / Telegram) → เชื่อ id เสมอ
            $platform = $byShape;
        } elseif (! in_array($platform, [self::PLATFORM_LINE, self::PLATFORM_FACEBOOK], true)) {
            // รวม platform='telegram' ที่ id ไม่ใช่ทรง 'tg_' — ยิงไป Telegram ไม่ถึงแน่นอน
            $platform = self::PLATFORM_FACEBOOK;
        }

        return ['platform' => $platform, 'user_id' => $userId];
    }
}
