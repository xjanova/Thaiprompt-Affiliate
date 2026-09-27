<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Support\Facades\Cache;

/**
 * 👉 ท่าทางของแม่หมอบน Facebook — ส่งสติกเกอร์ / กดหัวใจบนข้อความลูกค้า (2026-09-27)
 *
 * เจ้าของสั่ง: "ทำให้แม่หมอส่งสติกเกอร์กลับโต้ตอบได้ตามสถานการณ์ เช่น ใช้รูปมือแทนคำพูดเร่งให้เลือก
 * แพคเกจ หรืออีโมชั่นอื่นๆ ของเฟซบุ๊ค เพื่อให้เหมือนคน" → "ทำตามสมควร" · "ไลน์ไม่ต้องทำ"
 *
 * จุดที่ใช้ (FB เท่านั้น — Telegram ใช้ตัวเรนเดอร์ของ FB แต่ถูกกันด้วย instanceof):
 *   - ลูกค้าคุยเล่นตอนเลือกแพคเกจ → สติกเกอร์มือชี้ "แทน" บรรทัด TIER_WAITING_LINE
 *     (FortuneChannelManager → tier_choice_chitchat)
 *   - ลูกค้าบอกลา (farewell_blessing) → สติกเกอร์ไหว้ต่อท้ายคำอวยพร
 *   - ลูกค้าส่งสติกเกอร์ทักโดยไม่มีดวงค้าง → สติกเกอร์โบกมือก่อนคำทักทาย
 *   - ลูกค้าขอบคุณล้วน / ส่งสติกเกอร์ → กด ❤️ บนข้อความนั้น (FacebookWebhookController)
 *
 * กติกา:
 *   - ของประดับล้วน — ส่งไม่สำเร็จต้องไม่มีอะไรหาย และห้ามโยน exception เข้า flow ลูกค้า
 *   - ข้อความที่บอก "เหตุผล/ขั้นตอน" (เช่น เลือกแพคเกจก่อน แล้ว QR จะตามมา) คงเป็นตัวอักษรเสมอ
 *   - ทุกท่ามีคูลดาวน์ต่อคน (claim) — สติกเกอร์รัว = ดูเป็นบอทยิ่งกว่าเดิม
 *   - สติกเกอร์ต้องเป็นของฟรีจาก Sticker Catalog API ของ Meta เท่านั้น (ส่งของชุดอื่น = 400)
 */
class FortuneGestureSender
{
    /** มือเหลืองกวักนิ้ว "มานี่" — ใช้แทนประโยคเร่งเลือกแพคเกจ */
    public const STICKER_NUDGE_CHOOSE = 529234100872285;

    /** เด็กหญิงพนมมือไหว้ — ปิดท้ายคำอวยพรตอนลูกค้าลา */
    public const STICKER_FAREWELL = 780934928780101;

    /** มือเหลืองโบก — ตอบลูกค้าที่ส่งสติกเกอร์มาทัก */
    public const STICKER_HELLO = 529234040872291;

    /** อีโมจิที่กดบนข้อความลูกค้า */
    public const REACT_LOVE = '❤️';

    /**
     * บรรทัดเร่งเลือกแพคเกจในกล่องตอบระหว่างคุยเล่น — แหล่งเดียวของข้อความนี้
     * (CelticCrossConversationTrait สร้าง hint จากค่านี้ ⇒ แก้ถ้อยคำที่เดียว ตัวตัดไม่หลุด)
     */
    public const TIER_WAITING_LINE = '🙏 ยังรอเจ้าชะตาเลือกแพคเกจอยู่นะคะ';

    public const GESTURE_NUDGE_CHOOSE = 'nudge_choose';

    public const GESTURE_FAREWELL = 'farewell';

    public const GESTURE_HELLO = 'hello';

    public const GESTURE_REACT = 'react';

    /** คูลดาวน์ต่อคน (วินาที) — ครั้งถัดไปในช่วงนี้ได้ข้อความเดิมแทน */
    public const COOLDOWNS = [
        self::GESTURE_NUDGE_CHOOSE => 600,
        self::GESTURE_FAREWELL => 86400,
        self::GESTURE_HELLO => 21600,
        self::GESTURE_REACT => 60,
    ];

    public function __construct(private ?FortuneTellingSetting $settings = null) {}

    /**
     * สวิตช์หลังบ้าน `fortune_gestures_fb` (default เปิด) — อ่านสดทุกครั้ง ปิดแล้วมีผลทันที
     */
    public function enabled(): bool
    {
        try {
            // ⚠️ app(self::class) — container ฉีดโมเดลเปล่า (exists=false) มาแทน null
            //    อ่านจากตัวเปล่า = ได้ null → ?? true ⇒ สวิตช์ปิดไม่ได้ผล จึงต้องเช็ค exists
            $settings = ($this->settings && $this->settings->exists)
                ? $this->settings
                : FortuneTellingSetting::getSettings();

            return (bool) ($settings->fortune_gestures_fb ?? true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * จองสิทธิ์ใช้ท่านี้กับลูกค้าคนนี้ — true = สวิตช์เปิดและพ้นคูลดาวน์แล้ว (จองให้ทันที)
     *
     * ใช้ Cache::add (atomic) กันสองโปรเซสส่งท่าเดียวกันซ้อน · Cache หาย = แค่ได้ท่าซ้ำเร็วขึ้น ไม่มีอะไรเสีย
     */
    public function claim(string $psid, string $gesture): bool
    {
        if ($psid === '' || ! $this->enabled()) {
            return false;
        }

        $ttl = self::COOLDOWNS[$gesture] ?? 600;

        try {
            return Cache::add("fortune:gesture:{$gesture}:{$psid}", 1, $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ส่งสติกเกอร์ถ้าจองสิทธิ์ได้ — คืน true เมื่อส่งถึงจริง
     */
    public function sendStickerIfAllowed(object $fbService, string $psid, string $gesture, int $stickerId): bool
    {
        if (! $fbService instanceof FacebookWebhookService) {
            return false;
        }

        if (! $this->claim($psid, $gesture)) {
            return false;
        }

        return $fbService->sendSticker($psid, $stickerId);
    }

    /**
     * กด ❤️ บนข้อความลูกค้าที่เพิ่งเข้ามา ถ้าเป็นคำขอบคุณล้วนหรือสติกเกอร์ล้วน
     *
     * @param  bool  $stickerOnly  ข้อความนี้เป็นสติกเกอร์ไม่มีตัวอักษร
     */
    public function reactToInbound(object $fbService, string $psid, ?string $mid, string $text, bool $stickerOnly): bool
    {
        if (! $fbService instanceof FacebookWebhookService || empty($mid)) {
            return false;
        }

        if (! $stickerOnly && ! self::looksLikePureThanks($text)) {
            return false;
        }

        if (! $this->claim($psid, self::GESTURE_REACT)) {
            return false;
        }

        return $fbService->reactToMessage($psid, $mid, self::REACT_LOVE);
    }

    /**
     * เอาบรรทัด TIER_WAITING_LINE ออกจากกล่องตอบ — null = ไม่เจอบรรทัดนี้ หรือเอาออกแล้วเหลือแต่ความว่าง
     * (null = ห้ามใช้สติกเกอร์ ส่งข้อความเดิม)
     */
    public static function withoutWaitingLine(string $message): ?string
    {
        $lines = preg_split('/\R/u', $message);
        $found = false;
        $kept = [];

        foreach ($lines as $line) {
            if (! $found && trim($line) === self::TIER_WAITING_LINE) {
                $found = true;

                continue;
            }
            $kept[] = $line;
        }

        if (! $found) {
            return null;
        }

        // ยุบบรรทัดว่างที่เหลือซ้อนกันตรงรอยตัด
        $result = trim(preg_replace("/\n{3,}/u", "\n\n", implode("\n", $kept)));

        return $result === '' ? null : $result;
    }

    /**
     * ข้อความนี้เป็น "คำขอบคุณล้วน" ไหม — เช่น "ขอบคุณค่ะ", "ขอบคุณมากๆ ค่ะแม่หมอ 🙏", "สาธุ", "thank you"
     *
     * ต้องไม่มีเนื้อหาอื่นตามมา ("ขอบคุณค่ะ แล้วเรื่องงานล่ะ" = คำถาม ไม่ใช่คำขอบคุณ)
     */
    public static function looksLikePureThanks(string $text): bool
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 40) {
            return false;
        }

        // เก็บแค่ตัวอักษร + สระ/วรรณยุกต์ (อีโมจิ วรรคตอน ช่องว่าง ตัวเลข ทิ้งหมด)
        $core = mb_strtolower(preg_replace('/[^\p{L}\p{M}]+/u', '', $text));
        if ($core === '') {
            return false;
        }

        $thanks = 'ขอบ(?:พระ)?คุ[ณน]|ขอบใจ|สาธุ|thank(?:you|s)?|thx|ty';
        $extras = 'มาก|ๆ|นะ|ค่ะ|คะ|ค่า|ครับ|คับ|คร้าบ|จ้า|จ้ะ|จ๊ะ|ฮะ|เลย|แม่หมอ|แม่|หมอ|จันทรา|verymuch|somuch|alot';

        // เรียกชื่อนำหน้าได้ ("แม่หมอ ขอบคุณค่ะ") แต่ต้องมีคำขอบคุณเป็นแกนเสมอ
        return (bool) preg_match("/^(?:แม่หมอ|แม่|หมอ)?(?:{$thanks})(?:{$thanks}|{$extras})*$/u", $core);
    }
}
