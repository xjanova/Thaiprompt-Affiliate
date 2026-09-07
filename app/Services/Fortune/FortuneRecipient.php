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

    /**
     * LINE userId = 'U' + hex 32 ตัว
     *
     * PSID ของ Facebook เป็นตัวเลขล้วน ⇒ ชนกันไม่ได้ ใช้แยกช่องทางจาก id ล้วน ๆ ได้อย่างปลอดภัย
     */
    public const LINE_USER_ID_PATTERN = '/^U[0-9a-f]{32}$/i';

    /**
     * id ตัวนี้เป็น LINE userId หรือไม่ (ใช้เป็นด่านกันยิงผิดช่องทางด้วย)
     */
    public static function looksLikeLineUserId(?string $id): bool
    {
        $id = trim((string) $id);

        return $id !== '' && (bool) preg_match(self::LINE_USER_ID_PATTERN, $id);
    }

    /**
     * ช่องทางของบิลใบนี้ — 'line' หรือ 'facebook' (ไม่มีค่าอื่น)
     */
    public static function platformOf(FortuneReading $reading): string
    {
        // รูปทรงของ id ชนะคอลัมน์ platform เสมอ — LINE uid ยิงเข้า FB ไม่ได้อยู่แล้ว
        $candidate = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
        if (self::looksLikeLineUserId($candidate)) {
            return self::PLATFORM_LINE;
        }

        $platform = strtolower(trim((string) $reading->platform));
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
        return self::platformOf($reading) === self::PLATFORM_LINE
            ? (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '')
            : (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
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

        if (self::looksLikeLineUserId($userId)) {
            $platform = self::PLATFORM_LINE;
        } elseif (! in_array($platform, [self::PLATFORM_LINE, self::PLATFORM_FACEBOOK], true)) {
            $platform = self::PLATFORM_FACEBOOK;
        }

        return ['platform' => $platform, 'user_id' => $userId];
    }
}
