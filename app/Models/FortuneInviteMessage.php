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
 * @property string $mode โหมดที่ใช้ข้อความนี้: all | classic | transfer
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

    protected $fillable = [
        'message',
        'category',
        'mode',
        'is_active',
        'sort_order',
        'send_count',
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     * @return self|null null ถ้าไม่มีข้อความ active เลย (caller จะ fallback ไปส่งรูป/ทักทายปกติ)
     */
    public static function pickActive(): ?self
    {
        $settings = FortuneTellingSetting::getSettings();
        $disabled = $settings->getDisabledInviteCategories();

        // ⚠️ ระหว่าง deploy ไฟล์โค้ดขึ้นก่อน migrate เสมอ — ถ้าอ้างคอลัมน์ที่ยังไม่มี
        //    ทุก DM จะพังทั้งระบบในช่วงนั้น → ไม่มีคอลัมน์ = ทำตัวเหมือนก่อนมีฟีเจอร์นี้
        $hasModeColumn = self::hasModeColumn();

        // $modes = ค่า mode ที่ยอมรับ (แถวที่ mode เป็น NULL ถือเป็น 'all' — กัน '!=' ใน SQL ตัด NULL ทิ้ง)
        $base = function (array $modes) use ($disabled, $hasModeColumn) {
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

            return $q;
        };

        // ยังไม่ได้ migrate → สุ่มแบบเดิมทั้งกอง (ดีกว่า DM เงียบทั้งระบบ)
        if (! $hasModeColumn) {
            return $base([])->inRandomOrder()->first();
        }

        // 🔀 (2026-07-28) โหมด transfer — ใช้ชุดข้อความของโหมดนี้ก่อนเสมอ
        //    ข้อความชุดเดิมชวน "ทักมาดูดวงในแชท" = สวนทางกับกล่องที่พาไปเว็บ/LINE
        //    แต่ถ้ายังไม่มีใครเขียนชุด transfer ไว้เลย → ตกไปใช้ชุดเดิม (ห้ามเงียบ)
        $mode = (new \App\Services\Fortune\FortuneBotMode($settings))->mode();

        if ($mode === \App\Services\Fortune\FortuneBotMode::MODE_TRANSFER) {
            $preferred = $base([self::MODE_TRANSFER])->inRandomOrder()->first();

            if ($preferred) {
                return $preferred;
            }

            \Illuminate\Support\Facades\Log::warning(
                '💬 InviteMessage: โหมด transfer แต่ไม่มีข้อความชุด transfer เลย → ใช้ชุดกลาง (ข้อความอาจสวนทางกับกล่อง)'
            );

            return $base([self::MODE_ALL])->inRandomOrder()->first();
        }

        // โหมด classic — ใช้ชุดกลาง + ชุดที่ทำไว้เฉพาะ classic (ตัดชุด transfer ออก)
        return $base([self::MODE_ALL, self::MODE_CLASSIC])->inRandomOrder()->first();
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

        return self::pickActive();
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
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function quickReplies(): array
    {
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
            ['content_type' => 'text', 'title' => '🔕 พัก 7 วัน', 'payload' => 'INVITE_SNOOZE_7D'],
            ['content_type' => 'text', 'title' => '🚫 ไม่ต้องส่งอีก', 'payload' => 'INVITE_OPTOUT'],
        ]);
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
        $clean = FortuneUserCredit::isHumanLikeName($name) ? trim((string) $name) : '';

        $text = str_replace('{name}', $clean, (string) $this->message);
        $text = $this->replaceLinkPlaceholders($text, $platformUserId, $platform);

        // เก็บกวาด double space ที่เกิดจาก {name} ว่าง (เช่น "ของคุณ  จะ")
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);

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
