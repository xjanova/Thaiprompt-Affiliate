<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fortune Invite Message Model
 *
 * 💬 คลังข้อความ "ชวนดูดวงแบบเนียน" — สุ่มส่งใน DM กลับ
 * เมื่อลูกค้าได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว (ไม่ส่งรูปซ้ำ → ส่งข้อความแทน)
 *
 * ข้อความเป็นแนว ทริค/ทางออก/จังหวะชีวิต ไม่ขายตรง
 *
 * Placeholder ที่รองรับ (แทนที่อัตโนมัติตอนส่ง):
 *   - {name}        ชื่อลูกค้า
 *   - {{web_link}}  🔀 (2026-07-28) ลิงก์ดูดวงฟรีบนเว็บจันทรา — **สร้างต่อคน** (magic link)
 *   - {{line_link}} 🔀 (2026-07-28) ลิงก์เพิ่มเพื่อน LINE OA
 *     ⚠️ ฝังลิงก์ตายตัวใน DB ไม่ได้ เพราะ magic link ผูก PSID + วันหมดอายุรายคน
 *
 * @property int $id
 * @property string $message ข้อความเชิญชวน (รองรับ {name} / {{web_link}} / {{line_link}})
 * @property string $category หมวดหมู่
 * @property string $mode โหมดที่ใช้ข้อความนี้: all | classic | transfer | daily | web
 * @property int|null $hour_from ชั่วโมงเริ่มส่งได้ 0-23 (NULL = ทุกเวลา)
 * @property int|null $hour_to ชั่วโมงสุดท้ายที่ส่งได้ 0-23 (NULL = ทุกเวลา)
 * @property bool $is_active เปิดใช้งานหรือไม่
 * @property int $sort_order ลำดับ
 * @property int $send_count จำนวนครั้งที่ส่ง
 * @property \Carbon\Carbon|null $last_sent_at ส่งล่าสุดเมื่อ
 * @property int|null $created_by แอดมินที่สร้าง
 */
class FortuneInviteMessage extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_invite_messages';

    /** โหมดที่ใช้ข้อความนี้ได้ */
    public const MODE_ALL = 'all';

    public const MODE_CLASSIC = 'classic';

    public const MODE_TRANSFER = 'transfer';

    /** 🌙 (2026-07-31) ชุดข้อความของโหมด DM ดูดวงรายวัน — ชวนบอกวันเกิดแลกคำทำนายฟรี */
    public const MODE_DAILY = 'daily';

    /**
     * 🌐 (2026-11-03) ชุดข้อความของ FB web-only — ชวน "เข้าห้องแม่หมอ" บนเว็บจันทรา
     *
     * ใต้ธง facebook_web_only ฝั่ง FB หยิบได้ **เฉพาะชุดนี้** (ห้ามตกไปชุด all — ชุดเดิม
     * ชวนดูดวงในแชท/บอกราคา) ชุดว่าง = pickActive() คืน null
     * ⚠️ ห้ามมีคำเรื่องเงิน/LINE/ฟรี และห้ามวาง URL ดิบ — ลิงก์อยู่ในปุ่มที่ตอบกลับเท่านั้น
     */
    public const MODE_WEB = 'web';

    /** โหมดทั้งหมดที่โมเดลรู้จัก (หน้าแอดมิน validate จากรายการนี้) */
    public const MODES = [
        self::MODE_ALL,
        self::MODE_CLASSIC,
        self::MODE_TRANSFER,
        self::MODE_DAILY,
        self::MODE_WEB,
    ];

    /** URL ดิบ (http/https, www., m.me, โดเมนเว็บจันทรา) — ห้ามอยู่ในข้อความชุด web */
    public const RAW_URL_REGEX = '#(https?://|www\.|m\.me/|จันทรา\.online|juntra)#iu';

    /** จำนวนแถวสูงสุดที่สุ่มมาตรวจคำต้องห้ามต่อรอบ (ชุด web) */
    protected const WEB_PICK_CANDIDATES = 20;

    /**
     * ⏰ (2026-08-08) ช่วงเวลาเริ่มต้นของข้อความเดิมที่ "เขียนผูกเวลา" ไว้
     *
     * [ชิ้นข้อความที่ใช้ระบุตัว, hour_from, hour_to]
     *
     * จับด้วยข้อความ (ไม่ใช่ id) เพราะ id ของแต่ละเครื่องไม่ตรงกัน —
     * migration บน prod กับ seeder ตอนติดตั้งใหม่ต้องได้ผลเหมือนกัน
     *
     * ⚠️ เติมเฉพาะแถวที่ยังไม่ได้ตั้งช่วงเวลา — ห้ามทับของที่แอดมินแก้เอง
     */
    public const DEFAULT_TIME_WINDOWS = [
        // ── เช้า
        ['อรุณสวัสดิ์', 5, 9],
        ['เช้านี้แม่หมอเปิดดวงประจำวัน', 5, 10],
        ['ก่อนออกจากบ้านวันนี้', 5, 9],
        ['ก่อนเริ่มวันใหม่', 4, 9],
        ['ไพ่เช้านี้', 5, 11],

        // ── หัวค่ำ / ดึก (21-2 = คร่อมเที่ยงคืน)
        ['ก่อนจะจบวันนี้', 18, 23],
        ['ดึกแล้วแต่แม่หมอยังเปิดตำรา', 21, 2],
    ];

    protected $fillable = [
        'message',
        'category',
        'mode',
        'hour_from',
        'hour_to',
        'is_active',
        'sort_order',
        'send_count',
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hour_from' => 'integer',
        'hour_to' => 'integer',
        'sort_order' => 'integer',
        'send_count' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    /**
     * Scope: เฉพาะที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 🎲 สุ่มข้อความที่เปิดใช้งาน 1 ข้อความ
     *
     * ใช้ DB-side random (inRandomOrder) — ไม่ต้องโหลดทั้ง 300 ข้อความเข้า memory
     *
     * 🗂️ (2026-06-07) เคารพ "หมวดที่ปิด" (invite_disabled_categories) — ไม่สุ่มหมวดที่แอดมินปิด
     *   เป็น chokepoint เดียวของทุก DM path (comment/reaction/welcome) → ปิดหมวดที่นี่ครอบทั้งหมด
     *
     * 🌐 (2026-11-03) FB web-only: ธงเปิด + ช่องทางเป็น facebook → หยิบเฉพาะชุด MODE_WEB
     *   ชุดว่าง / ยังไม่มีคอลัมน์ mode = null (ไม่ส่ง) **ห้ามตกไปชุด all เด็ดขาด**
     *   LINE/Telegram ไม่โดน (fbWebOnlyFor เช็คช่องทางก่อน)
     *
     * @param  string  $platform  ช่องทางที่จะส่ง ('facebook' | 'line' | 'telegram')
     * @return self|null null ถ้าไม่มีข้อความ active เลย (caller จะ fallback ไปส่งรูป/ทักทายปกติ)
     */
    public static function pickActive(string $platform = 'facebook'): ?self
    {
        // 🌐 ตัดสินก่อนอ่านค่าอื่น — อ่านธงพังในบริบท FB = ถือว่าเปิด (fail-closed)
        $webOnly = self::fbWebOnlyActive($platform);

        $settings = FortuneTellingSetting::getSettings();
        $disabled = $settings->getDisabledInviteCategories();

        // ⚠️ ระหว่าง deploy ไฟล์โค้ดขึ้นก่อน migrate เสมอ — ถ้าอ้างคอลัมน์ที่ยังไม่มี
        //    ทุก DM จะพังทั้งระบบในช่วงนั้น → ไม่มีคอลัมน์ = ทำตัวเหมือนก่อนมีฟีเจอร์นี้
        $hasModeColumn = self::hasModeColumn();
        $hasWindow = self::supportsTimeWindow();

        // ชั่วโมงปัจจุบันตามโซนเวลาแอป (Asia/Bangkok) — 0-23
        $hour = (int) now()->format('G');

        // $modes = ค่า mode ที่ยอมรับ (แถวที่ mode เป็น NULL ถือเป็น 'all' — กัน '!=' ใน SQL ตัด NULL ทิ้ง)
        $base = function (array $modes, bool $applyWindow = true) use ($disabled, $hasModeColumn, $hasWindow, $hour) {
            $q = self::active();

            // 🗂️ ตัดหมวดที่แอดมินปิด (ถ้ามี)
            if (! empty($disabled)) {
                $q->whereNotIn('category', $disabled);
            }

            if ($hasModeColumn) {
                $q->where(function ($sub) use ($modes) {
                    $sub->whereIn('mode', $modes);

                    if (in_array(self::MODE_ALL, $modes, true)) {
                        $sub->orWhereNull('mode');
                    }
                });
            }

            // ⏰ (2026-08-08) ตัดข้อความที่ผิดเวลาออก (เช่น "ดึกแล้ว...ก่อนนอน" ตอนเที่ยง)
            if ($applyWindow && $hasWindow) {
                self::applyHourWindow($q, $hour);
            }

            return $q;
        };

        // 🎲 สุ่ม 1 ข้อความ — กรองเวลาก่อน ถ้าชั่วโมงนี้ไม่เหลือของเลยค่อยสุ่มทั้งกอง
        //    ⚠️ DM เงียบ แย่กว่า DM ที่โทนเวลาเพี้ยน — ห้ามคืน null เพราะตัวกรองเวลา
        $pick = function (array $modes) use ($base, $hasWindow, $hour) {
            $row = $base($modes)->inRandomOrder()->first();

            if ($row !== null || ! $hasWindow) {
                return $row;
            }

            $fallback = $base($modes, false)->inRandomOrder()->first();

            // เตือนเฉพาะตอนที่ "ตัวกรองเวลา" เป็นต้นเหตุจริง ๆ
            // (กองนี้ว่างเปล่าอยู่แล้วเป็นคนละเรื่อง — มี log ของมันเองอยู่ข้างล่าง)
            if ($fallback !== null) {
                \Illuminate\Support\Facades\Log::warning(
                    '⏰ InviteMessage: ชั่วโมงนี้ไม่มีข้อความที่ตรงช่วงเวลาเลย → สุ่มทั้งกองแทน',
                    ['hour' => $hour, 'modes' => $modes]
                );
            }

            return $fallback;
        };

        // 🌐 FB web-only — ชุด web เท่านั้น (แถวที่มีคำต้องห้าม/URL ถูกข้าม) ว่าง = null
        if ($webOnly) {
            if (! $hasModeColumn) {
                \Illuminate\Support\Facades\Log::warning('🌐 InviteMessage: FB web-only แต่ยังไม่มีคอลัมน์ mode → ไม่ส่งข้อความชวน');

                return null;
            }

            return self::pickWebSafe($base, $hasWindow, $hour);
        }

        // ยังไม่ได้ migrate → สุ่มแบบเดิมทั้งกอง (ดีกว่า DM เงียบทั้งระบบ)
        if (! $hasModeColumn) {
            return $pick([]);
        }

        // 🔀 (2026-07-28) โหมด transfer — ใช้ชุดข้อความของโหมดนี้ก่อนเสมอ
        //    ข้อความชุดเดิมชวน "ทักมาดูดวงในแชท" = สวนทางกับกล่องที่พาไปเว็บ/LINE
        //    แต่ถ้ายังไม่มีใครเขียนชุด transfer ไว้เลย → ตกไปใช้ชุดเดิม (ห้ามเงียบ)
        $botMode = new \App\Services\Fortune\FortuneBotMode($settings);
        $mode = $botMode->mode();

        // ⏰ (2026-07-31) โหมด daily แต่วันนี้ยังไม่มีบทความ (ก่อน 06:00 / job พัง)
        //   → ใช้ชุดข้อความ classic ไปก่อน ไม่งั้นจะชวน "บอกวันเกิดรับดวงฟรี"
        //     ทั้งที่ยังส่งของให้ไม่ได้ (owner: "หลังเที่ยงคืน ต้องสลับกลับไปเป็น DM
        //     แบบเก่า จนกว่าจะ 6 โมง")
        //   ต้องสลับพร้อมกับปุ่มที่ FacebookWebhookController/ProcessCommentEngagement
        //   ไม่งั้นได้ข้อความชวนวันเกิด + ปุ่มแบบเก่า = สวนทางกันเอง
        //   🎁 (2026-08-28) เส้นเดียวกันนี้ครอบสวิตช์ `daily_free_horoscope_enabled` ด้วย
        //     — isDailyServing() คืน false เมื่อแอดมินปิดระบบชวนดวงฟรี ⇒ DM กลับไปใช้
        //     "ชุดข้อความชวนดูดวงชุดแรก" (all/classic) ตามที่เจ้าของสั่ง โดยไม่ต้องแก้ที่นี่
        if ($mode === \App\Services\Fortune\FortuneBotMode::MODE_DAILY && ! $botMode->isDailyServing()) {
            $mode = \App\Services\Fortune\FortuneBotMode::MODE_CLASSIC;
        }

        // 🌙 (2026-07-31) โหมด daily — ชุดข้อความชวน "บอกวันเกิดรับทำนายฟรี"
        //    ชุดเดิมชวน "ทักมาดูดวง" คนละเจตนากับโหมดนี้ (เราต้องการให้ลูกค้า *พิมพ์วันเกิด*)
        $dedicated = [
            \App\Services\Fortune\FortuneBotMode::MODE_TRANSFER => self::MODE_TRANSFER,
            \App\Services\Fortune\FortuneBotMode::MODE_DAILY => self::MODE_DAILY,
        ];

        if (isset($dedicated[$mode])) {
            $preferred = $pick([$dedicated[$mode]]);

            if ($preferred) {
                return $preferred;
            }

            \Illuminate\Support\Facades\Log::warning(
                '💬 InviteMessage: ไม่มีข้อความชุดของโหมดนี้เลย → ใช้ชุดกลาง (ข้อความอาจสวนทางกับโหมด)',
                ['mode' => $mode]
            );

            return $pick([self::MODE_ALL]);
        }

        // โหมด classic — ใช้ชุดกลาง + ชุดที่ทำไว้เฉพาะ classic (ตัดชุดของโหมดอื่นออก)
        return $pick([self::MODE_ALL, self::MODE_CLASSIC]);
    }

    /**
     * 🌐 หยิบข้อความชุด web 1 ข้อความที่ผ่านการตรวจคำต้องห้าม
     *
     * สุ่มมาทีละกอง (ไม่เกิน WEB_PICK_CANDIDATES) แล้วคืนแถวแรกที่ปลอดภัย —
     * กันเคสแอดมินแก้ข้อความชุด web แล้วเผลอใส่คำว่า "ฟรี"/"บาท"/ลิงก์
     * กรองช่วงเวลาก่อน ถ้าชั่วโมงนี้ไม่มีเลยค่อยสุ่มทั้งชุด web (ยังเป็นชุด web เสมอ)
     *
     * @param  \Closure  $base  ตัวสร้าง query จาก pickActive()
     */
    protected static function pickWebSafe(\Closure $base, bool $hasWindow, int $hour): ?self
    {
        $rounds = $hasWindow ? [true, false] : [false];

        foreach ($rounds as $applyWindow) {
            $candidates = $base([self::MODE_WEB], $applyWindow)
                ->inRandomOrder()
                ->limit(self::WEB_PICK_CANDIDATES)
                ->get();

            foreach ($candidates as $row) {
                if (self::isWebSafeText((string) $row->message)) {
                    return $row;
                }

                \Illuminate\Support\Facades\Log::critical('🌐 InviteMessage: ข้อความชุด web มีคำต้องห้าม/ลิงก์ → ข้าม (แก้ที่หน้าแอดมิน)', [
                    'invite_id' => $row->id,
                ]);
            }
        }

        \Illuminate\Support\Facades\Log::warning('🌐 InviteMessage: FB web-only แต่ไม่มีข้อความชุด web ที่ใช้ได้ → ไม่ส่ง (ไม่ตกไปชุด all)', [
            'hour' => $hour,
        ]);

        return null;
    }

    /**
     * 🌐 ข้อความนี้ปลอดภัยสำหรับ FB web-only ไหม
     *
     * ห้าม: คำเรื่องเงิน/LINE/ฟรี (WebHandoffCopy::FORBIDDEN_REGEX), URL ดิบ,
     * และ placeholder ลิงก์ ({{web_link}} / {{line_link}}) — ลิงก์ต้องอยู่ในปุ่มเท่านั้น
     *
     * @example FortuneInviteMessage::isWebSafeText('แม่หมอรออยู่ในห้องค่ะ 🌙'); // true
     */
    public static function isWebSafeText(?string $text): bool
    {
        $text = trim((string) $text);

        if ($text === '') {
            return false;
        }

        if (preg_match(\App\Services\Fortune\WebHandoffCopy::FORBIDDEN_REGEX, $text)) {
            return false;
        }

        if (str_contains($text, '{{web_link}}') || str_contains($text, '{{line_link}}')) {
            return false;
        }

        return ! preg_match(self::RAW_URL_REGEX, $text);
    }

    /**
     * 🌐 ธง FB web-only มีผลกับช่องทางนี้ไหม (จุดเดียวของโมเดลนี้)
     *
     * อ่านค่าพังในบริบท facebook = true (fail-closed) / ช่องทางอื่น = false เสมอ
     */
    public static function fbWebOnlyActive(string $platform = 'facebook'): bool
    {
        try {
            return (new \App\Services\Fortune\FortuneBotMode(FortuneTellingSetting::getSettings()))
                ->fbWebOnlyFor($platform, null);
        } catch (\Throwable $e) {
            return \App\Services\Fortune\FortuneBotMode::resolvePlatform($platform, null)
                === \App\Services\Fortune\FortuneBotMode::FB_WEB_ONLY_PLATFORM;
        }
    }

    /**
     * มีคอลัมน์ mode แล้วหรือยัง (memo ต่อ request — ถามทุกครั้งเปลืองเกินไป)
     *
     * ใช้กันช่วง deploy ที่โค้ดขึ้นก่อน migrate — อ้างคอลัมน์ที่ยังไม่มี = DM พังทั้งระบบ
     */
    protected static function hasModeColumn(): bool
    {
        // ⚠️ memo เฉพาะตอน "มีแล้ว" — ถ้ายังไม่มีต้องถามใหม่ทุกครั้ง
        //    ไม่งั้น queue worker ที่ยืนยาวจะจำค่า false ไว้ตั้งแต่ก่อน migrate
        //    แล้วไม่รู้จักคอลัมน์ใหม่จนกว่าจะ restart (self-heal ได้ดีกว่ารอคนกด)
        static $has = false;

        if ($has) {
            return true;
        }

        try {
            $has = \Illuminate\Support\Facades\Schema::hasColumn('fortune_invite_messages', 'mode');
        } catch (\Throwable $e) {
            $has = false;
        }

        return $has;
    }

    /**
     * ⏰ มีคอลัมน์ช่วงเวลา (hour_from / hour_to) แล้วหรือยัง
     *
     * เหตุผลเดียวกับ hasModeColumn() — ไฟล์โค้ดขึ้น prod ก่อน migrate เสมอ
     * อ้างคอลัมน์ที่ยังไม่มีในช่วงนั้น = DM พังทั้งระบบ
     *
     * memo เฉพาะตอน "มีแล้ว" — queue worker ที่ยืนยาวจะได้รู้จักคอลัมน์ใหม่เอง
     * โดยไม่ต้องรอ restart (ถ้า memo false ไว้จะค้างจนกว่าคนจะกด)
     */
    public static function supportsTimeWindow(): bool
    {
        static $has = false;

        if ($has) {
            return true;
        }

        try {
            $has = \Illuminate\Support\Facades\Schema::hasColumns(
                'fortune_invite_messages',
                ['hour_from', 'hour_to']
            );
        } catch (\Throwable $e) {
            $has = false;
        }

        return $has;
    }

    /**
     * ⏰ ใส่เงื่อนไข "ชั่วโมงนี้ส่งข้อความนี้ได้ไหม" ลงใน query
     *
     * กติกา (ตรงกับ migration + หน้าแอดมิน):
     *   - hour_from หรือ hour_to เป็น NULL = ส่งได้ทุกเวลา (แถวเดิมทั้งหมดอยู่กลุ่มนี้)
     *   - from <= to  → ช่วงในวันเดียว เช่น 5-9  = 05:00–09:59
     *   - from >  to  → ช่วงคร่อมเที่ยงคืน เช่น 21-2 = 21:00–02:59
     *
     * ⚠️ ตั้งมาข้างเดียวถือว่า "ไม่ได้ตั้ง" โดยตั้งใจ — หน้าต่างครึ่งใบตีความได้หลายแบบ
     *    ปล่อยให้ส่งได้ทุกเวลาปลอดภัยกว่าเดาแล้วบล็อกข้อความทิ้ง
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $hour  ชั่วโมงปัจจุบัน 0-23
     */
    protected static function applyHourWindow($query, int $hour): void
    {
        $query->where(function ($sub) use ($hour) {
            $sub->whereNull('hour_from')
                ->orWhereNull('hour_to')
                ->orWhere(function ($w) use ($hour) {
                    // ช่วงในวันเดียว
                    $w->whereColumn('hour_from', '<=', 'hour_to')
                        ->where('hour_from', '<=', $hour)
                        ->where('hour_to', '>=', $hour);
                })
                ->orWhere(function ($w) use ($hour) {
                    // ช่วงคร่อมเที่ยงคืน — อยู่ฝั่งหัวค่ำ หรือฝั่งเช้ามืด อย่างใดอย่างหนึ่ง
                    $w->whereColumn('hour_from', '>', 'hour_to')
                        ->where(function ($x) use ($hour) {
                            $x->where('hour_from', '<=', $hour)
                                ->orWhere('hour_to', '>=', $hour);
                        });
                });
        });
    }

    /**
     * ⏰ เติมช่วงเวลาให้ข้อความเดิมที่เขียนผูกเวลาไว้ (ตาม DEFAULT_TIME_WINDOWS)
     *
     * ใช้ทั้งจาก migration (เติมของ prod) และ seeder (ติดตั้งใหม่)
     * ⚠️ แตะเฉพาะแถวที่ยังไม่ได้ตั้งช่วงเวลา — ห้ามทับค่าที่แอดมินตั้งเอง
     *
     * @return int จำนวนแถวที่ถูกเติม
     */
    public static function applyDefaultTimeWindows(): int
    {
        if (! self::supportsTimeWindow()) {
            return 0;
        }

        $updated = 0;

        foreach (self::DEFAULT_TIME_WINDOWS as [$needle, $from, $to]) {
            $updated += self::query()
                ->whereNull('hour_from')
                ->whereNull('hour_to')
                ->where('message', 'like', '%'.$needle.'%')
                ->update([
                    'hour_from' => $from,
                    'hour_to' => $to,
                ]);
        }

        return $updated;
    }

    /**
     * 🏷️ ป้ายช่วงเวลาแบบอ่านง่าย สำหรับหน้าแอดมิน
     *
     * @return string|null null = ส่งได้ทุกเวลา (ไม่ต้องขึ้นป้าย)
     */
    public function timeWindowLabel(): ?string
    {
        if ($this->hour_from === null || $this->hour_to === null) {
            return null;
        }

        $label = sprintf('%02d:00–%02d:59', $this->hour_from, $this->hour_to);

        // คร่อมเที่ยงคืน — บอกให้ชัด ไม่งั้นแอดมินอ่านว่า "21 ถึง 2" แล้วงงว่าย้อนหลัง
        return $this->hour_from > $this->hour_to
            ? $label.' (ข้ามคืน)'
            : $label;
    }

    /**
     * 🚦 Gate กลาง — user คนนี้ "ควรงดส่งรูป" สัปดาห์นี้หรือไม่
     *
     * true เมื่อ: เปิดระบบ rotation (toggle) + ได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว
     * ใช้โดยทุก DM path (comment / reaction / welcome ทักเพจครั้งแรก) ให้ตัดสินใจตรงกัน
     *
     * @param  string  $userId  facebook_user_id / line_user_id
     * @param  string  $platform  'facebook' | 'line'
     */
    public static function shouldSuppressImage(string $userId, string $platform = 'facebook', ?string $channel = null): bool
    {
        $settings = FortuneTellingSetting::getSettings();

        if (! $settings->isInviteRotationEnabled()) {
            return false;
        }

        // (1) ได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว → ส่งข้อความแทน (ไม่ส่งรูปซ้ำ)
        if (FortuneUserCredit::hasReceivedImageThisWeek($userId, $platform)) {
            return true;
        }

        // (2) (2026-06-06) แบนเนอร์ของ channel นี้ปิดอยู่ → ไม่มีรูปจะส่งอยู่แล้ว
        //     → ใช้ข้อความชวน (100 ข้อ) เป็นเนื้อหา DM แทน "daily greeting" เดิม
        //     กันเคส USER: "ปิด banner แล้วแต่ข้อความที่ส่งไม่ใช่ 100 ข้อความที่เขียนไว้"
        if ($channel !== null) {
            $banner = new \App\Services\FortuneBannerService($settings);
            if (! $banner->isEnabledFor($channel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🎯 ตัดสินใจ "ส่งข้อความชวนแทนรูป" สำหรับ user คนนี้
     *
     * คืน message instance ที่จะส่งเป็นข้อความ (caller render + recordSend เอง)
     * หรือ null = ให้ส่งรูปแบนเนอร์ตามปกติ
     *
     * null เกิดเมื่อ: ปิด toggle / ยังไม่ได้รูปสัปดาห์นี้ / ไม่มีข้อความ active เลย
     *
     * @param  string  $userId  facebook_user_id / line_user_id
     * @param  string  $platform  'facebook' | 'line'
     */
    public static function resolveFor(string $userId, string $platform = 'facebook', ?string $channel = null): ?self
    {
        if (! self::shouldSuppressImage($userId, $platform, $channel)) {
            return null;
        }

        return self::pickActive($platform);
    }

    /**
     * 🔘 Quick Replies ที่แนบไปกับข้อความชวน (Facebook)
     *
     * 1. 🎁 ดูฟรีที่เว็บ    → INVITE_FREE_WEB  (ห้องแชทแม่หมอบนเว็บจันทรา — ฟรีตามโควตา)
     * 2. 🔮 ดูดวงเลย       → INVITE_READ_NOW  (เข้า flow ดูดวงในแชท + เคลียร์ opt-out)
     * 3. 🔕 พัก 7 วัน      → INVITE_SNOOZE_7D (พัก DM 7 วัน)
     * 4. 🚫 ไม่ต้องส่งอีก   → INVITE_OPTOUT    (หยุด DM ตาม comment/reaction ถาวร)
     *
     * 🎁 (2026-07-28) ปุ่มเว็บอยู่ตัวแรกโดยตั้งใจ — quick reply บนมือถือเลื่อนแนวนอน
     *    ปุ่มที่ 4 มักตกขอบจอ ถ้าอยากให้คนไปเว็บต้องอยู่หน้าสุด
     *    ขึ้นเฉพาะเมื่อเปิด `enable_web_fortune_button` — ปิดอยู่ = ปุ่มเดิม 3 ตัวเป๊ะ
     *    (ปุ่มที่กดแล้วไม่มีอะไรเกิดขึ้น แย่กว่าไม่มีปุ่ม)
     *
     * payload เป็น namespace ใหม่ (INVITE_*) ไม่ชนกับปุ่มเดิมในระบบ
     * route ผ่าน FacebookWebhookController::handleQuickReply()
     *
     * 🌐 (2026-11-03) FB web-only: [🌙 เข้าห้องแม่หมอ → WEB_ROOM] [🔕 พัก 7 วัน] [🚫 ไม่ต้องส่งอีก]
     *   ไม่มี INVITE_READ_NOW (ดูดวงในแชท) และไม่มี INVITE_FREE_WEB (คำว่า "ฟรี")
     *   กด WEB_ROOM แล้ว webhook ตอบด้วยกล่องลิงก์ที่สร้างจาก sender.id (PSID จริง) ทุกครั้ง
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function quickReplies(): array
    {
        // quick reply ชุดนี้ใช้กับ Facebook เท่านั้น (ทุก caller เป็นเส้น FB)
        if (self::fbWebOnlyActive('facebook')) {
            return self::webQuickReplies();
        }

        $buttons = [];

        try {
            if (app(\App\Services\FortuneWebLinkService::class)->isEnabled()) {
                $buttons[] = ['content_type' => 'text', 'title' => '🎁 ดูฟรีที่เว็บ', 'payload' => 'INVITE_FREE_WEB'];
            }
        } catch (\Throwable $e) {
            // เช็คสวิตช์ไม่ได้ → ไม่ใส่ปุ่ม (พฤติกรรมเดิม)
        }

        return array_merge($buttons, [
            ['content_type' => 'text', 'title' => '🔮 ดูดวงเลย', 'payload' => 'INVITE_READ_NOW'],
        ], static::optOutQuickReplies());
    }

    /**
     * 🌐 quick reply ของ DM ชวนแบบ web-only (ตัวเดียวกับ WebHandoffCopy — ห้ามเขียนซ้ำ)
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function webQuickReplies(): array
    {
        return \App\Services\Fortune\WebHandoffCopy::inviteQuickReplies();
    }

    /**
     * 🔕 ปุ่ม "ขอเลิกรับ DM" — ทางออกของลูกค้า
     *
     * 🚨 **DM ชวนทุกเส้นต้องมีปุ่มชุดนี้เสมอ** ไม่ว่าจะส่งเป็นข้อความหรือการ์ด
     *    ยิงคำชวนซ้ำ ๆ โดยไม่มีทางให้ปฏิเสธ = สัญญาณสแปมตรงตัว ซึ่งเป็นสิ่งที่
     *    ทำให้เพจเสี่ยงโดนลด reach / โดนแบน (ดูเหตุการณ์ 2026-08-09)
     *
     * แยกออกมาเป็นเมธอดของตัวเองเพราะสายการ์ด (FortuneEntryCardBuilder) เอา
     * ปุ่มขายไปไว้บนการ์ดแล้ว เหลือแค่ปุ่มปฏิเสธที่ต้องแนบเป็น quick reply
     * — ก็อปไปเขียนซ้ำเมื่อไหร่ มันจะดริฟต์กันทันที
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function optOutQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '🔕 พัก 7 วัน', 'payload' => 'INVITE_SNOOZE_7D'],
            ['content_type' => 'text', 'title' => '🚫 ไม่ต้องส่งอีก', 'payload' => 'INVITE_OPTOUT'],
        ];
    }

    /**
     * แทนที่ placeholder ทั้งหมดด้วยค่าจริงของลูกค้าคนนี้
     *
     * - {name} → ชื่อลูกค้า
     *   ชื่อ valid (เป็นชื่อคนจริง) → "คุณ{name}" → "คุณสมชาย"
     *   ชื่อ invalid/ว่าง (FACEBOOK-XXX, "คุณ", ว่าง) → "" เพื่อไม่ให้ได้ "คุณคุณ"
     * - {{web_link}} → magic link ดูดวงฟรีบนเว็บ (ผูก PSID รายคน + หมดอายุ)
     * - {{line_link}} → ลิงก์เพิ่มเพื่อน LINE OA
     *
     * ⚠️ ถ้าสร้างลิงก์ไม่ได้ (ปิดสวิตช์/ไม่มี id/ไม่ได้ตั้ง LINE OA) — **ตัดทั้งบรรทัด**
     *    ที่มี placeholder นั้นทิ้ง ไม่ใช่แทนด้วยค่าว่าง ไม่งั้นลูกค้าจะได้ข้อความ
     *    "กดที่นี่เลย 👉" ที่ไม่มีลิงก์ = ดูเหมือนบอทพัง
     *
     * @param  string|null  $name  ชื่อลูกค้าดิบ
     * @param  string|null  $platformUserId  PSID / LINE user id (ต้องมีถึงจะสร้าง web link ได้)
     * @param  string  $platform  'facebook' | 'line'
     * @return string ข้อความพร้อมส่ง
     */
    public function render(?string $name, ?string $platformUserId = null, string $platform = 'facebook'): string
    {
        // 🌐 (2026-11-03) ชุด web / FB web-only — ห้ามมีลิงก์ดิบ ใช้กติกาชื่อแบบเดียวกับ WebHandoffCopy
        //    LINE/Telegram ไม่เข้าเส้นนี้ (resolvePlatform ดูรูปทรง id ก่อน)
        if ($this->mode === self::MODE_WEB
            || (\App\Services\Fortune\FortuneBotMode::resolvePlatform($platform, $platformUserId) === 'facebook'
                && self::fbWebOnlyActive('facebook'))) {
            return $this->renderWeb($name);
        }

        $clean = FortuneUserCredit::isHumanLikeName($name) ? trim((string) $name) : '';

        $text = str_replace('{name}', $clean, (string) $this->message);
        $text = $this->replaceLinkPlaceholders($text, $platformUserId, $platform);

        // เก็บกวาด double space ที่เกิดจาก {name} ว่าง (เช่น "ของคุณ  จะ")
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);

        return trim($text);
    }

    /**
     * 🌐 render แบบ web-only — ไม่สร้างลิงก์ใด ๆ (ลิงก์ไปอยู่ในปุ่มที่ตอบหลังลูกค้ากด WEB_ROOM)
     *
     * - {name}: ชื่อคนจริงที่ไม่ยาวเกิน 20 ตัว ไม่งั้นใช้คำแทนตามข้อ (WebHandoffCopy::NAME_FALLBACK)
     *   ข้อความที่ไม่ได้มาจากชุดมาตรฐาน (แอดมินเขียนเอง) ใช้ "เจ้าชะตา"
     * - บรรทัดที่มี {{web_link}} / {{line_link}} / URL ดิบ ถูกตัดทิ้งทั้งบรรทัด
     */
    protected function renderWeb(?string $name): string
    {
        $display = FortuneUserCredit::isHumanLikeName($name)
            ? \App\Services\Fortune\WebHandoffCopy::displayName($name)
            : null;

        $fallback = \App\Services\Fortune\WebHandoffCopy::DEFAULT_NAME_FALLBACK;
        foreach (\App\Services\Fortune\WebHandoffCopy::INVITES as $n => $row) {
            if ($row['text'] === (string) $this->message) {
                $fallback = \App\Services\Fortune\WebHandoffCopy::NAME_FALLBACK['invite_'.$n] ?? $fallback;
                break;
            }
        }

        $text = str_replace('{name}', $display ?? $fallback, (string) $this->message);

        $kept = array_filter(
            preg_split('/\r\n|\r|\n/', $text) ?: [],
            fn ($line) => ! str_contains($line, '{{web_link}}')
                && ! str_contains($line, '{{line_link}}')
                && ! preg_match(self::RAW_URL_REGEX, $line)
        );

        $text = preg_replace('/[ \t]{2,}/u', ' ', implode("\n", $kept)) ?? '';

        return trim($text);
    }

    /**
     * 🔗 (2026-07-28) แทน {{web_link}} / {{line_link}} ด้วยลิงก์จริง
     *
     * สร้างต่อคนทุกครั้ง — magic link มี HMAC + วันหมดอายุ ฝังตายตัวใน DB ไม่ได้
     */
    protected function replaceLinkPlaceholders(string $text, ?string $platformUserId, string $platform): string
    {
        if (! str_contains($text, '{{web_link}}') && ! str_contains($text, '{{line_link}}')) {
            return $text;
        }

        $settings = FortuneTellingSetting::getSettings();

        // 🌐 magic link ดูดวงฟรีบนเว็บ (ปลายทาง /tarot/free)
        $webLink = null;
        if (! empty($platformUserId)) {
            try {
                $svc = app(\App\Services\FortuneWebLinkService::class);
                if ($svc->isEnabled()) {
                    $webLink = $svc->generateChatLink($platform, $platformUserId, '/tarot/free');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('💬 InviteMessage: สร้าง web link ไม่สำเร็จ', [
                    'err' => $e->getMessage(),
                ]);
            }
        }

        // 💚 LINE add friend
        $lineLink = null;
        $basicId = trim((string) ($settings->line_bot_basic_id ?? ''));
        if ($basicId !== '') {
            $lineLink = 'https://line.me/R/ti/p/'.(str_starts_with($basicId, '@') ? $basicId : '@'.$basicId);
        }

        foreach ([['{{web_link}}', $webLink], ['{{line_link}}', $lineLink]] as [$token, $url]) {
            if (! str_contains($text, $token)) {
                continue;
            }

            $text = $url !== null
                ? str_replace($token, $url, $text)
                : $this->dropLinesContaining($text, $token);
        }

        return $text;
    }

    /**
     * ตัดบรรทัดที่มี placeholder ซึ่งสร้างลิงก์ไม่ได้ออกทั้งบรรทัด
     */
    protected function dropLinesContaining(string $text, string $token): string
    {
        $kept = array_filter(
            preg_split('/\r\n|\r|\n/', $text) ?: [],
            fn ($line) => ! str_contains($line, $token)
        );

        // กันเคสข้อความมี placeholder บรรทัดเดียว → ตัดหมดเหลือว่าง
        $joined = trim(implode("\n", $kept));

        return $joined !== '' ? $joined : trim(str_replace($token, '', $text));
    }

    /**
     * บันทึกสถิติการส่ง
     */
    public function recordSend(): void
    {
        $this->increment('send_count');
        $this->update(['last_sent_at' => now()]);
    }
}
