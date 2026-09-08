<?php

namespace App\Services\Fortune;

use App\Models\FortuneGestureFloodStrike;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneBanService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎭 GestureFloodGuard — รู้ทัน / เงียบ / เตือน / ระงับ 7 วัน สำหรับพฤติกรรม "ยิงสติกเกอร์-อีโมจิรัว"
 *
 * เคสจริงที่ทำให้ต้องมีคลาสนี้ (prod 2026-09-08 21:40-21:57):
 *   PSID 26308980832136739 ("แสนที เล็ก") เปิดบิล FTU-260908-B4059 แล้วทิ้ง
 *   เปิดใหม่ FTU-260908-Y1018 (deep 39฿) แล้วไม่จ่ายอีก
 *   จากนั้นยิงข้อความ 113 ครั้งใน 15 นาที — 73 ใบเป็นสติกเกอร์/อีโมจิล้วน (`🕵️‍♂️🕵️‍♂️🕵️‍♂️`)
 *   บอทถูกลากตอบกลับ 94 ข้อความ (`action=waiting_payment` 156 ครั้ง) จ่ายจริง 0 บาท
 *
 * ทำไมด่านเดิมทั้ง 4 ตัวจับไม่ได้สักตัว:
 *   1. `sendGestureEngagementNudge` (FacebookWebhookController) — gate ที่ `! $hasActiveFortune`
 *      ⇒ พอ "มีบิลค้าง" เส้นนี้ไม่เคยถูกเรียกเลย
 *   2. sticker-ack ของ Celtic — gate ที่ `CELTIC_ACTIVE_STATUSES` ⇒ บิล `deep` ไม่เข้าข่าย
 *   3. `FortuneContactSignalService::record()` — `hasMediaAttachment()` ตัดสติกเกอร์ทิ้งตั้งแต่ต้น
 *      ⇒ `burst_streak` ของคนส่งสติกเกอร์ล้วน **เป็น 0 ตลอดกาล** ลดเกณฑ์เหลือ 1 ก็แบนไม่ได้
 *   4. `isUserSpamming()` — ปิดปากได้ก็จริง แต่ `action=filtered` ยังส่งข้อความออกอยู่ดี
 *
 * 🔑 บทเรียนที่คลาสนี้แก้: ทุกครั้งที่เจอเคสคนแก่ส่งสติกเกอร์ เราถอยด้วยการ
 *    "ยกเว้นสติกเกอร์ทั้งหมด" ไม่ใช่ "แยกแยะ" — เอา *สติกเกอร์ไม่ใช่หลักฐานว่าเป็นสแปม*
 *    ไปเท่ากับ *สติกเกอร์ไม่ใช่หลักฐานอะไรเลย*
 *
 * ขั้นบันได (เจ้าของสั่ง 2026-09-08: "บอทต้องรู้ และไม่ตอบมันทุกรูปก่อน แล้วเตือน ถ้าไม่หยุดคือแบน"):
 *   ขั้น 0  ตอบปกติ   ใบที่ 1-2 ในหน้าต่าง            → ปล่อยผ่าน (คนแก่ทักด้วยสติกเกอร์ต้องได้คำตอบ)
 *   ขั้น 1  เงียบ      ใบที่ 3-5                       → ไม่ตอบ (ไม่นับความผิด)
 *   ขั้น 2  เตือน      ครบ 6 ใบ/5 นาที                → เตือน + เงียบ 5 นาที + strike 1
 *   ขั้น 3  เตือนสุดท้าย แตะเกณฑ์อีกใน 24 ชม.          → เตือนชัดว่าครั้งหน้าระงับ + strike 2
 *   ขั้น 4  ระงับ      แตะเกณฑ์ครั้งที่ 3 ใน 24 ชม.    → FortuneBanService::ban(7 วัน)
 *
 * 🚨 กติกาที่ห้ามพลาด
 *   1. **ลูกค้าจ่ายเงิน/แจ้งโอน/ส่งสลิป ยกเว้นทุกขั้น** และล้าง strike ที่ค้างทิ้ง
 *      ([[rule_paid_customer_bypass_all_guards]])
 *   2. **"เปิดบิล" ไม่ใช่เกราะ** — บิลที่ยังไม่จ่ายคือสิ่งที่คนกวนใช้ซื้อภูมิคุ้มกันมาตลอด
 *      ([[2026-08-08 DIAGNOSIS — อุดม ศรีโปฎก]] รูเดียวกัน คนละราง)
 *   3. **พิมพ์ข้อความจริงแล้วปล่อยผ่าน แต่ห้ามล้าง strike** — ไม่งั้นแทรกคำเดียวคั่นทุก 5 ใบ
 *      ก็รีเซ็ตได้ตลอดกาล (นี่คือเหตุผลที่ `burst_streak` ของแสนที เป็น 0 ทั้งที่ยิง 113 ใบ)
 *      strike หมดอายุด้วย "เวลา" อย่างเดียว (24 ชม.)
 *   4. **ตัวนับความถี่ต้องเป็น list ของ timestamp** ห้ามใช้ counter + Cache::put ต่ออายุ TTL
 *      ไม่งั้นตัวนับไม่มีวันหมดอายุสำหรับคนที่ยิงถี่ต่อเนื่อง
 *   5. **strike อยู่ DB** — deploy.sh รัน cache:clear ทุกครั้ง ([[rule_cache_is_volatile_never_sole_store]])
 *   6. **ห้ามแบนถาวรอัตโนมัติ** — ส่งหน่วยเป็นนาทีเสมอ (null = ถาวร) ([[rule_quiz_gates_all_autoban]])
 *   7. **รูป/ลิงก์/สลิป ไม่ใช่งานของด่านนี้** — มีรางของตัวเองอยู่แล้ว (burst link/image)
 *      ถ้าเอามานับด้วยจะไปทับคนส่งสลิปซ้ำๆ
 */
class GestureFloodGuard
{
    /** ปล่อยผ่าน — ไม่ใช่ท่าทางล้วน / จ่ายเงินแล้ว / ยังไม่ถึงเกณฑ์ */
    public const ACTION_PASS = 'pass';

    /** เงียบ — ไม่ตอบอะไรเลย (ไม่นับความผิด) */
    public const ACTION_SILENT = 'silent';

    /** เตือน — ส่งข้อความเตือน 1 ครั้งแล้วเงียบ */
    public const ACTION_WARN = 'warn';

    /** ระงับแล้ว — FortuneBanService ลงมือไปเรียบร้อย */
    public const ACTION_BANNED = 'banned';

    /** หน้าต่างสะสมความผิด — ยิงรัวเป็นพฤติกรรมชั่ววูบ ไม่ควรสะสมข้ามหลายวัน */
    protected const STRIKE_WINDOW_HOURS = 24;

    /** ครบกี่ strike ถึงระงับ (ลอกจาก NavFloodGuard ที่ผ่านการใช้จริงแล้ว) */
    protected const MAX_STRIKES = 3;

    /**
     * ⚠️ จงใจ "ไม่มี" memo กันนับซ้ำแบบ NavFloodGuard
     *
     * NavFloodGuard ต้องมี เพราะการกดปุ่ม 1 ครั้งวิ่งผ่านด่านสองรอบ (postback → handleQuickReply)
     * ด่านนี้ไม่มีเส้นแบบนั้น — `processMessage()` ถูกเรียกครั้งเดียวต่อข้อความ
     * แต่ FB ส่ง webhook มาเป็น "ชุด" หลาย messaging ใน request เดียว
     * ⇒ ถ้าใส่ memo คีย์ที่ตัวลูกค้า สติกเกอร์ 10 ใบในชุดเดียวจะถูกนับเป็น 1 ใบ = ด่านตาบอด
     */
    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจข้อความขาเข้า 1 ข้อความ
     *
     * @param  string  $platform  facebook | line
     * @param  string  $userId  PSID / LINE userId
     * @param  string  $messageText  ข้อความ (อาจว่าง)
     * @param  array  $attachments  attachments ของ FB (LINE ส่ง [] แล้วใช้ $forceGesture แทน)
     * @param  string|\Closure|null  $displayName  ชื่อลูกค้า (snapshot ไว้ดูย้อนหลัง)
     *                                             ส่ง Closure ได้ — จะถูกเรียก "เฉพาะตอนบันทึกความผิด"
     *                                             เท่านั้น เพื่อไม่ให้ยิง Graph API ทุกข้อความขาเข้า
     *                                             (เจ้าของเคยหาตัวคนกวนไม่เจอเพราะเราไม่เคยเก็บชื่อ)
     * @param  bool  $forceGesture  บังคับว่าข้อความนี้เป็นท่าทางล้วน (LINE message type=sticker)
     * @return array{action:string,message:?string}
     */
    public function check(
        string $platform,
        string $userId,
        string $messageText,
        array $attachments = [],
        string|\Closure|null $displayName = null,
        bool $forceGesture = false,
    ): array {
        $pass = ['action' => self::ACTION_PASS, 'message' => null];

        if ($userId === '') {
            return $pass;
        }

        try {
            if (! (bool) ($this->settings->enable_gesture_flood_guard ?? false)) {
                return $pass;
            }

            // 💰 ลูกค้าจ่ายเงิน/แจ้งโอน/ส่งสลิป — ยกเว้นทุกขั้น + ล้างประวัติที่ค้าง
            //    ⚠️ ต้องเช็ค "ก่อน" นับราง volume — ไม่งั้นลูกค้าที่จ่ายแล้วคุยยาวๆ โดนนับไปด้วย
            if ($this->isPayingCustomer($platform, $userId)) {
                $this->clearStrikes($platform, $userId);

                return $pass;
            }

            $isGesture = $forceGesture || $this->isContentFree($messageText, $attachments);

            $result = $this->evaluate($platform, $userId, $messageText, $displayName, $isGesture);

            // 👁️ shadow mode — คำนวณครบ เขียน log ครบ แต่ไม่บล็อกใคร
            if ($this->mode() !== 'enforce' && $result['action'] !== self::ACTION_PASS) {
                Log::info('GestureFloodGuard: would_'.$result['action'], [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'sample' => $this->sample($messageText),
                ]);

                return $pass;
            }

            return $result;
        } catch (\Throwable $e) {
            // ด่านเสริมพัง ต้องไม่ทำให้แชททั้งระบบตาย
            Log::warning('GestureFloodGuard: ด่านล้ม (ปล่อยผ่าน)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $pass;
        }
    }

    /**
     * ข้อความนี้ "ไม่มีเนื้อหา" ไหม — สติกเกอร์ / อีโมจิ / สัญลักษณ์ล้วน
     *
     * 🧪 วิธีตรวจ: ลอกทุกอย่างที่ไม่ใช่ตัวอักษร (`\p{L}`) หรือตัวเลข (`\p{N}`) ออก
     *    เหลือศูนย์ = ไม่มีเนื้อหา
     *
     *    ครอบคลุมกับดักภาษาไทยทั้งชุดโดยอัตโนมัติ ([[rule_thai_text_matching_traps]]):
     *      • อีโมจิ = `\p{So}` · ZWJ = `\p{Cf}` · VS16 = `\p{Mn}` — ไม่มีตัวไหนเป็น L หรือ N
     *      • สระ/วรรณยุกต์ไทย = `\p{M}` แต่คำไทยทุกคำมีพยัญชนะ (`\p{L}`) อย่างน้อยหนึ่งตัว
     *      • "5555" = `\p{N}` ⇒ นับเป็นเนื้อหาจริง (หัวเราะแบบไทย ห้ามไปปิดปาก)
     *
     * ⚠️ รูป/วิดีโอ/ไฟล์/ลิงก์แชร์ → คืน false เสมอ (ไม่ใช่งานของด่านนี้ ดูกติกาข้อ 7)
     *    สติกเกอร์ FB มาเป็น `type=image` + `payload.sticker_id` จึงต้องเช็ค sticker_id ก่อน type
     */
    public function isContentFree(string $messageText, array $attachments = []): bool
    {
        $hasSticker = false;
        $hasOtherAttachment = false;

        foreach ($attachments as $att) {
            if (! is_array($att)) {
                continue;
            }

            if (($att['type'] ?? '') === 'sticker' || ! empty($att['payload']['sticker_id'])) {
                $hasSticker = true;

                continue;
            }

            $hasOtherAttachment = true;
        }

        // รูป/สลิป/ลิงก์แชร์/เสียง → ไม่ใช่ราง gesture
        if ($hasOtherAttachment) {
            return false;
        }

        $stripped = preg_replace('/[^\p{L}\p{N}]+/u', '', $messageText);

        // preg พัง (UTF-8 เสีย) → ถือว่ามีเนื้อหา ปลอดภัยไว้ก่อน ห้ามเสี่ยงปิดปากลูกค้า
        if ($stripped === null) {
            return false;
        }

        if ($stripped !== '') {
            return false;
        }

        // ถึงตรงนี้ = ไม่มีตัวอักษรจริงเลย → เป็นท่าทางล้วน (สติกเกอร์ หรือ อีโมจิ/สัญลักษณ์)
        // ⚠️ ข้อความว่าง + ไม่มี attachment เลย = ไม่มีอะไรให้ตัดสิน (webhook ping/echo) → ห้ามนับ
        return $hasSticker || trim($messageText) !== '';
    }

    /**
     * แกนตัดสิน — แยกออกมาให้ทดสอบง่ายและให้ shadow mode ห่อได้
     *
     * @return array{action:string,message:?string}
     */
    protected function evaluate(
        string $platform,
        string $userId,
        string $messageText,
        string|\Closure|null $displayName,
        bool $isGesture,
    ): array {
        $now = time();

        // ══ ราง 2: ปริมาณ — นับ "ทุกข้อความ" ไม่ว่าจะพิมพ์จริงหรือท่าทาง
        //   ต้องนับก่อน แล้วค่อย return ตามราง gesture — ไม่งั้นคนที่สลับพิมพ์จริงคั่น
        //   จะทำให้ตัวนับปริมาณขาดช่วง (เคสแสนที: พิมพ์จริง 38 คั่นท่าทาง 73)
        $volumeMax = (int) ($this->settings->gesture_flood_volume_max ?? 40);

        if ($volumeMax > 0) {
            $volumeWindow = max(60, (int) ($this->settings->gesture_flood_volume_window_sec ?? 600));
            $volumeHits = $this->bump('fortune:gesture:volume:'.$platform.':'.$userId, $volumeWindow, $now);

            if ($volumeHits >= $volumeMax) {
                return $this->applyStrike(
                    $platform,
                    $userId,
                    $displayName,
                    "ยิงรัว {$volumeHits} ใบ/{$volumeWindow}วิ",
                    $this->sample($messageText),
                    'volume',
                );
            }
        }

        // ══ ราง 1: ท่าทางล้วน — ข้อความที่มีเนื้อหาจริงจบแค่นี้ (ผ่าน แต่ถูกนับปริมาณไปแล้ว)
        //   ⚠️ ห้ามล้าง strike ตรงนี้ — ดูกติกาข้อ 3 ในหัวคลาส
        if (! $isGesture) {
            return ['action' => self::ACTION_PASS, 'message' => null];
        }

        $windowSec = max(30, (int) ($this->settings->gesture_flood_window_sec ?? 300));
        $freeReplies = max(1, (int) ($this->settings->gesture_flood_free_replies ?? 2));
        $maxHits = max($freeReplies + 1, (int) ($this->settings->gesture_flood_max ?? 6));

        $hits = $this->bump('fortune:gesture:rate:'.$platform.':'.$userId, $windowSec, $now);

        // ── ขั้น 0: ใบที่ 1-2 → ตอบตามปกติ
        //    คนแก่ทักด้วยสติกเกอร์แล้วบอทเงียบ = พลาดลูกค้า (บทเรียน 2026-07-06)
        if ($hits <= $freeReplies) {
            return ['action' => self::ACTION_PASS, 'message' => null];
        }

        // ── ขั้น 1: ใบที่ 3 ถึง (max-1) → เงียบเฉยๆ ไม่นับความผิด
        //    เจ้าของสั่ง: "ไม่ตอบมันทุกรูปก่อน เพื่อโชว์ความฉลาด"
        if ($hits < $maxHits) {
            Log::info('GestureFloodGuard: เงียบ (ท่าทางล้วนถี่เกิน)', [
                'platform' => $platform,
                'user_id' => $userId,
                'hits' => $hits,
                'window_sec' => $windowSec,
            ]);

            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        // ── ขั้น 2+: แตะเกณฑ์ → บวก strike
        return $this->applyStrike(
            $platform,
            $userId,
            $displayName,
            "ท่าทางล้วน {$hits} ใบ/{$windowSec}วิ",
            $this->sample($messageText),
            'gesture',
        );
    }

    /**
     * บวก strike + ตัดสินว่าจะเตือนหรือระงับ
     *
     * @return array{action:string,message:?string}
     */
    protected function applyStrike(
        string $platform,
        string $userId,
        string|\Closure|null $displayName,
        string $reason,
        string $sample,
        string $rail,
    ): array {
        if (! Schema::hasTable('fortune_gesture_flood_strikes')) {
            // ช่วง deploy ที่โค้ดขึ้นก่อน migrate — เงียบไปก่อน ดีกว่าปล่อยให้ยิงต่อ
            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $cooldownMin = max(1, (int) ($this->settings->gesture_flood_cooldown_minutes ?? 5));

        // 🔒 นับความผิด 1 ครั้งต่อคูลดาวน์ ไม่ใช่ทุกใบที่ยิงมา
        //    ไม่งั้นยิงรัว 20 ใบ = 20 strikes = ระงับทันทีโดยไม่มีโอกาสได้เห็นคำเตือน
        //    ⚠️ คูลดาวน์แยกตามราง — ไม่งั้นรางปริมาณจะกลืนคูลดาวน์ของรางท่าทาง
        $strikeLock = 'fortune:gesture:struck:'.$rail.':'.$platform.':'.$userId;
        $isNewStrike = Cache::add($strikeLock, true, $cooldownMin * 60);

        // 🛤️ แยกแถวตามราง — สติกเกอร์กับพิมพ์รัวเป็นคนละพฤติกรรม strike ห้ามบวกข้ามกัน
        //    (คอลัมน์ `rail` เพิ่งเพิ่ม — ระหว่าง deploy ที่ยังไม่ migrate ให้ถอยไปใช้แถวเดียว)
        $keys = ['platform' => $platform, 'platform_user_id' => $userId];
        if (Schema::hasColumn('fortune_gesture_flood_strikes', 'rail')) {
            $keys['rail'] = $rail;
        }

        $row = FortuneGestureFloodStrike::firstOrNew($keys);

        // หน้าต่างสะสมหมดอายุ → เริ่มนับใหม่
        if ($row->window_started_at === null
            || $row->window_started_at->lt(now()->subHours(self::STRIKE_WINDOW_HOURS))) {
            $row->window_started_at = now();
            $row->strikes = 0;
            $row->warned_count = 0;
        }

        $row->total_hits = (int) $row->total_hits + 1;
        $row->last_hit_at = now();
        $row->last_sample = mb_substr($sample, 0, 120);

        // 🏷️ ชื่อลูกค้า — resolve ตรงนี้เท่านั้น (จุดเดียวที่คุ้มค่ายิง Graph API)
        //    ถ้ามีชื่อเดิมอยู่แล้วก็ไม่ต้องถามซ้ำ
        if (empty($row->display_name)) {
            $resolved = $displayName instanceof \Closure
                ? $this->resolveName($displayName)
                : $displayName;

            if (is_string($resolved) && $resolved !== '') {
                $row->display_name = mb_substr($resolved, 0, 191);
            }
        }

        if (! $isNewStrike) {
            // อยู่ระหว่างคูลดาวน์ — เงียบสนิท ห้ามส่งข้อความซ้ำแม้จะยิงอีก 50 ใบ
            $row->save();

            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $row->strikes = (int) $row->strikes + 1;

        Log::warning('🎭 GestureFloodGuard: แตะเกณฑ์ยิงรัว', [
            'platform' => $platform,
            'user_id' => $userId,
            'rail' => $rail,
            'reason' => $reason,
            'sample' => $sample,
            'strikes' => $row->strikes,
        ]);

        $banDays = max(1, (int) ($this->settings->gesture_flood_ban_days ?? 7));

        // ── ขั้น 4: ระงับ
        if ($row->strikes >= self::MAX_STRIKES) {
            $row->banned_at = now();
            $row->save();

            try {
                app(FortuneBanService::class)->ban(
                    $platform,
                    $userId,
                    $banDays * 24 * 60,   // ⚠️ หน่วยเป็นนาที · null = แบนถาวร ห้ามส่ง
                    'gesture_flood['.$rail.']: '.$reason,
                    null,
                    $row->display_name,
                );
            } catch (\Throwable $e) {
                Log::error('GestureFloodGuard: สั่งระงับไม่สำเร็จ', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'action' => self::ACTION_BANNED,
                'message' => $this->buildBanMessage($banDays, $rail),
            ];
        }

        // ── ขั้น 2-3: เตือน (เพดานแข็ง 2 ข้อความ/หน้าต่าง)
        $maxWarnings = self::MAX_STRIKES - 1;

        if ($row->warned_count >= $maxWarnings) {
            $row->save();

            return ['action' => self::ACTION_SILENT, 'message' => null];
        }

        $row->warned_count = (int) $row->warned_count + 1;
        $row->last_warned_at = now();
        $row->save();

        return [
            'action' => self::ACTION_WARN,
            'message' => $row->warned_count === 1
                ? $this->buildFirstWarning($rail)
                : $this->buildFinalWarning($banDays, $rail),
        ];
    }

    /**
     * บวกตัวนับแบบ list-of-timestamps แล้วคืนจำนวนครั้งในหน้าต่าง
     *
     * ⚠️ ห้ามใช้ counter + Cache::put ต่ออายุ TTL — คนที่ยิงถี่ต่อเนื่อง
     *    จะทำให้ตัวนับไม่มีวันหมดอายุ ค้างยาวจนกว่าจะหยุดสนิท
     */
    protected function bump(string $key, int $windowSec, int $now): int
    {
        $log = Cache::get($key, []);

        if (! is_array($log)) {
            $log = [];
        }

        $log = array_values(array_filter($log, fn ($t) => ($now - (int) $t) < $windowSec));
        $log[] = $now;

        // เก็บเผื่อหน้าต่างอีกเท่าตัว กันขอบหาย แต่ไม่ให้ยาวไม่จำกัด
        Cache::put($key, array_slice($log, -200), $windowSec * 2);

        return count($log);
    }

    /**
     * ลูกค้าคนนี้ "จ่ายเงิน / แจ้งโอน / ส่งสลิป" แล้วหรือยัง
     *
     * ⚠️ "เปิดบิล" ไม่นับ — บิลที่ยังไม่จ่ายคือสิ่งที่คนกวนใช้ซื้อภูมิคุ้มกันมาตลอด
     *    (บทเรียนเดียวกับ [[2026-08-08 DIAGNOSIS — อุดม ศรีโปฎก]] คนละราง)
     *
     * เช็คไม่ได้ = ถือว่าจ่ายแล้ว (ปลอดภัยไว้ก่อน ห้ามเสี่ยงปิดปากคนจ่ายเงิน)
     */
    protected function isPayingCustomer(string $platform, string $userId): bool
    {
        $cacheKey = 'fortune:gesture:paying:'.$platform.':'.$userId;

        try {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return (bool) $cached;
            }

            $paying = FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
                ->where(function ($q) {
                    $q->where('is_paid', true)
                        ->orWhere('transfer_reported', true)
                        ->orWhereNotNull('paid_at')
                        ->orWhereNotNull('slip_received_at');
                })
                ->exists();

            // แคชสั้นๆ — คนเพิ่งจ่ายต้องหลุดเกราะเดิมภายใน 30 วิ
            Cache::put($cacheKey, $paying ? 1 : 0, 30);

            return $paying;
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function clearStrikes(string $platform, string $userId): void
    {
        try {
            if (! Schema::hasTable('fortune_gesture_flood_strikes')) {
                return;
            }

            FortuneGestureFloodStrike::where('platform', $platform)
                ->where('platform_user_id', $userId)
                ->delete();

            Cache::forget('fortune:gesture:rate:'.$platform.':'.$userId);
            Cache::forget('fortune:gesture:struck:'.$platform.':'.$userId);
        } catch (\Throwable $e) {
            // ไม่สำคัญพอจะทำให้ flow พัง
        }
    }

    protected function mode(): string
    {
        return (string) ($this->settings->gesture_flood_mode ?? 'log_only');
    }

    /**
     * เรียก resolver ชื่อลูกค้าแบบไม่ให้พังทั้งด่าน
     *
     * Graph คืน 400 รายคนได้ปกติ ไม่ได้แปลว่า token พัง ([[rule_fb_profile_400_is_per_account_not_token]])
     */
    protected function resolveName(\Closure $resolver): ?string
    {
        try {
            $name = $resolver();

            return is_string($name) && trim($name) !== '' ? trim($name) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ตัวอย่างสิ่งที่ยิงมา — ไว้ยืนยันย้อนหลังว่าไม่ใช่ false-positive
     */
    protected function sample(string $messageText): string
    {
        $text = trim($messageText);

        if ($text !== '') {
            return mb_substr($text, 0, 120);
        }

        return '[sticker]';
    }

    /**
     * ⚠️ ข้อความต้องบรรยาย "สิ่งที่เขาทำจริง" ให้ตรงราง
     *
     * ถ้าเตือนคนที่พิมพ์ข้อความรัวว่า "ส่งสติกเกอร์รัว" เขาจะงงและไม่รู้ว่าต้องหยุดอะไร
     * — คำเตือนที่ผิดพฤติกรรม = คำเตือนที่ใช้ไม่ได้ (และไม่ยุติธรรมพอจะเอาไปแบนต่อ)
     */
    protected function buildFirstWarning(string $rail): string
    {
        $what = $rail === 'volume'
            ? 'ส่งข้อความถี่มากในเวลาสั้นๆ'
            : 'ส่งสติกเกอร์/อีโมจิรัวๆ มาหลายใบ';

        return "🌙 แม่หมอเห็นนะคะว่าเจ้าชะตา{$what}แล้ว\n\n"
            ."แม่หมอจะขอพักไม่ตอบสักครู่นะคะ — ถ้าอยากให้ดูดวงจริงๆ\n"
            ."*พิมพ์เรื่องที่อยากรู้มาทีเดียว* แล้วรอแม่หมอตอบนะคะ\n\n"
            .'_ถ้ายังส่งแบบนี้ต่อ แม่หมอจำเป็นต้องระงับการคุยชั่วคราวนะคะ_';
    }

    protected function buildFinalWarning(int $banDays, string $rail): string
    {
        $what = $rail === 'volume'
            ? 'ยังส่งข้อความถี่มากอย่างต่อเนื่อง'
            : 'ยังส่งสติกเกอร์/อีโมจิรัวเข้ามาเรื่อยๆ โดยไม่พิมพ์อะไรเลย';

        return "⚠️ เตือนครั้งสุดท้ายนะคะ\n\n"
            ."เจ้าชะตา{$what}\n"
            ."ถ้ายังทำแบบนี้อีก แม่หมอจะระงับการคุย {$banDays} วันค่ะ\n\n"
            .'_อยากดูดวง — พิมพ์เรื่องที่อยากรู้มาได้เลย แม่หมอรออยู่ค่ะ_';
    }

    protected function buildBanMessage(int $banDays, string $rail): string
    {
        $what = $rail === 'volume'
            ? 'ยังส่งข้อความถี่มากต่อเนื่อง'
            : 'ยังส่งสติกเกอร์รัวเข้ามาโดยไม่พิมพ์อะไรเลย';

        return "🚫 แม่หมอขอระงับการคุยไว้ {$banDays} วันนะคะ\n\n"
            ."เตือนไปสองครั้งแล้ว แต่{$what}\n"
            .'ครบกำหนดแล้วกลับมาคุยกันใหม่ได้ค่ะ';
    }
}
