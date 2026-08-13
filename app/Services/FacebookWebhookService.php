<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneReading;
use App\Services\Fortune\FortunePageContext;
use App\Models\FortuneResponseTemplate;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Facebook Webhook Service
 *
 * จัดการ Facebook Messenger Platform API (Graph API v21.0)
 * รับ webhook events, ส่ง messages, รูปภาพ, typing indicator
 *
 * รองรับ:
 * - ส่งข้อความยาวแบบแบ่ง (message splitting)
 * - ส่งรูปภาพผ่าน Messenger
 * - Typing indicator ระหว่างประมวลผล
 * - Webhook signature verification
 * - Quick replies buttons
 */
class FacebookWebhookService implements MessagingPlatformInterface
{
    /**
     * 🏷️ MESSAGE_TAG ยังใช้ส่งนอกกรอบ 24 ชม. ได้ไหม
     *
     * ⛔ 2026-08-13 — ยิงทดสอบจริงแล้ว (`fortune:fb-tag-probe --to=<PSID จริง>`) ผลชี้ขาด:
     *     CONFIRMED_EVENT_UPDATE  → subcode 1893061 "ไม่อนุญาตให้ใช้แท็กข้อความที่เลิกใช้แล้ว"
     *     ACCOUNT_UPDATE          → subcode 1893061 (เหมือนกัน)
     *     POST_PURCHASE_UPDATE    → subcode 1893061 (ตัวที่โค้ดนี้ใช้มาตลอด)
     *     HUMAN_AGENT             → subcode 2018276 "ต้องได้รับการอนุมัติก่อน" (ต้อง App Review)
     *   ⇒ **ไม่มีแท็กไหนใช้ได้เลยสำหรับเพจนี้**
     *
     * ผลกระทบก่อนแก้: retry MESSAGE_TAG 102 ครั้ง/วัน ที่ไม่มีทางสำเร็จ
     *   — ลูกค้าไม่ได้ข้อความ **และ** เสีย call ตกน้ำให้ Meta นับเป็นสัญญาณสแปม
     *
     * 🕰️ ของเดิมเชื่อผิดมา 2 เดือนเพราะคอมเมนต์ในโค้ดเขียนว่า
     *   "✅ POST_PURCHASE_UPDATE ใช้ได้ทันที" / "ใช้แทน HUMAN_AGENT ไม่ต้องขออนุมัติ"
     *   ซึ่งเคยจริงตอนเขียน แต่ Meta ยกเลิกไปแล้ว — **คอมเมนต์ไม่ใช่หลักฐาน ต้องยิงถามจริง**
     *
     * ✅ ทางที่ยังใช้ได้และกว้างกว่าเดิม: **Private Reply (`recipient.comment_id`) window 7 วัน**
     *    (MESSAGE_TAG เดิมครอบได้แค่คนที่เรามี PSID — Private Reply ครอบทุกคนที่คอมเมนต์)
     *
     * 🔁 วิธีเปิดกลับเมื่อ Meta อนุมัติ HUMAN_AGENT: เปลี่ยนเป็น true + ตั้ง MESSAGE_TAG_NAME = 'HUMAN_AGENT'
     *    แล้วรัน `php artisan fortune:fb-tag-probe --to=<PSID>` ยืนยันก่อน deploy
     */
    public const MESSAGE_TAG_USABLE = false;

    /** แท็กที่จะใช้เมื่อ MESSAGE_TAG_USABLE = true (ดูเหตุผลด้านบน) */
    public const MESSAGE_TAG_NAME = 'POST_PURCHASE_UPDATE';

    /**
     * subcode ที่แปลว่า "แท็กนี้ใช้ไม่ได้" — ห้าม retry ต่อ
     *
     * ⚠️ 1893061 (แท็กถูกยกเลิก) เคย **ตกลิสต์** ทำให้ warning ไม่เคยขึ้นเลย 2 เดือน
     *    ของเดิมดักแค่ 2018276 → เจอ 1893061 แล้วเงียบ ไม่มีใครรู้ว่าข้อความหาย
     */
    public const DEAD_TAG_SUBCODES = [1893061, 2018276];

    /** 🚦 กุญแจ circuit breaker ของการอ่านคอมเมนต์ (ดูเหตุผลที่ listCommentsForPost) */
    public const READ_COMMENTS_BREAKER_KEY = 'fb_read_comments_breaker';

    /** พักนานเท่าไรเมื่อ breaker อ่านคอมเมนต์ทำงาน (ชั่วโมง) */
    protected const READ_COMMENTS_BREAKER_HOURS = 6;

    protected $settings;

    protected $pageAccessToken;

    /**
     * 🏬 (2026-08-10) PSID ล่าสุดที่ย้อนหาสาขาให้แล้ว
     *
     * กันยิง query ซ้ำตอนส่งข้อความหลายก้อนให้คนเดิม (คำทำนายยาวถูกซอยเป็นหลายข้อความ)
     * แต่ยัง resolve ใหม่ทันทีที่เปลี่ยนคน
     */
    protected ?string $lastResolvedRecipient = null;

    /**
     * 🏬 context สาขาปัจจุบันถูก bind โดยตัว service นี้เอง (ไม่ใช่ webhook/queue)
     *
     * ใช้แยกว่า "ใครเป็นเจ้าของ context" — ของคนอื่นห้ามแตะ ของตัวเองเปลี่ยนได้
     */
    protected bool $lazyBoundContext = false;

    /**
     * ความยาวสูงสุดของข้อความ Messenger (Facebook กำหนด 2000 characters)
     */
    protected const MAX_MESSAGE_LENGTH = 2000;

    /**
     * Graph API version
     */
    // public เพื่อให้ผู้เรียก Graph API นอก service นี้ (เช่น FacebookLoginController)
    // อ้างเวอร์ชันจากจุดเดียว — bump ที่นี่ที่เดียวครบทุก call site
    public const GRAPH_API_VERSION = 'v21.0';

    /**
     * ชุด TLD ที่ถือว่าเป็น "ลิงก์ภายนอก" เมื่อเขียนแบบไม่มี protocol (เช่น abc.online)
     *
     * ⚠️ (2026-08-09) แหล่งความจริงเดียวของ TLD — แก้ที่นี่ที่เดียว
     *   ลิสต์เดิมมีแค่ com|net|io|me|co|xyz|info|biz|org|shop|store|app|tk|ml|ga|cf
     *   ทำให้เว็บพนัน/สแกมที่ใช้ .online .site .top .vip .club .cc .bet ทะลุด่านทั้งหมด
     *
     * @tip เรียงตัวยาวไว้ก่อนตัวสั้นที่เป็น prefix กัน (com ก่อน co) ไม่งั้น alternation
     *      จะ match ตัวสั้นแล้วตัด TLD จริงเพี้ยน
     */
    public const LINK_TLD_PATTERN = 'com|net|org|info|biz|shop|store|online|site|space|website|casino|games|game|club|live|link|asia|cyou|icu|vip|top|win|bet|fun|pro|app|xyz|io|me|co|cc|th|ru|tk|ml|ga|cf|gq';

    /**
     * TLD ที่ยอมให้ "เขียนเปล่าๆ ไม่มี http:// ไม่มี / ต่อท้าย" ถือเป็นลิงก์ได้
     *
     * 🚨 (2026-08-09) เกิดจากผลสแกนจริง 430,709 คอมเมนต์ → false positive 57%
     *   ตัวการคือ **เสียงพูดแปลงเป็นข้อความ** พ่น `.com` ออกมารัวๆ เช่น
     *   "No my hot hips.com alarm.com no I'm not pop numb rap Godน้อมรับครับ"
     *   และ **คนพิมพ์อีเมลตัวเอง** (auisirisomphone36@gmail.com)
     *   ถ้าปล่อยไว้ = บล็อกลูกค้าจริงทิ้งวันละคน
     *
     * กติกาใหม่: `คำ.com` เปล่าๆ ไม่นับเป็นลิงก์ ต้องมี protocol / www. / path ประกอบ
     *   ยกเว้น TLD กลุ่มเสี่ยงด้านล่าง (เว็บพนัน/สแกมใช้ประจำ) ที่เขียนเปล่าๆ ก็นับ
     *   → `huay-th.co` (บ้านหวยไทย) ยังจับได้ · `hips.com` ไม่จับแล้ว
     *
     * ⚠️ ห้ามใส่ com|net|org|info|biz|th|me|app — พวกนี้โผล่ในข้อความมั่วบ่อยเกินไป
     */
    public const LINK_TLD_BARE_RISKY = 'online|site|space|website|casino|games|game|club|live|link|asia|cyou|icu|vip|top|win|bet|fun|shop|store|xyz|co|cc|ru|tk|ml|ga|cf|gq';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        // 🐛 (2026-05-10) Laravel auto-DI inject empty model instance — ไม่ใช่ null
        //   ทำให้ ?? fallback ไม่ทำงาน → settings ทั้งหมดเป็น null
        //   แก้: เช็ค $settings->exists (true เมื่อ load จาก DB) ก่อน fallback
        $this->settings = ($settings && $settings->exists)
            ? $settings
            : FortuneTellingSetting::getSettings();
        $this->pageAccessToken = $this->settings->facebook_page_token;
    }

    // ============================================================
    // Facebook Graph API: การส่งข้อความ
    // ============================================================

    /**
     * ส่งข้อความผ่าน Messenger API (รองรับข้อความยาว)
     *
     * ถ้าข้อความยาวเกิน 2000 ตัวอักษร จะแบ่งส่งหลาย messages อัตโนมัติ
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $message  ข้อความที่ต้องการส่ง
     * @return bool สำเร็จหรือไม่
     */
    /**
     * 🌐 Default Quick Replies — แสดงในทุก DM ที่ไม่ได้ระบุ quick_replies
     *
     * 2 tier shortcuts (39฿ Deep / 99฿ Celtic) + เช็คสิทธิ์
     * Locale-aware (อ่านจาก FortuneLocaleService::current())
     * ราคาดึงจาก settings (admin override ได้)
     *
     * ⚠️ Title FB Quick Reply max 20 chars — ต้องสั้น
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public function getDefaultQuickReplies(): array
    {
        $isLao = FortuneLocaleService::current() === FortuneLocaleService::LOCALE_LO;
        // 🌙 (2026-05-23) Deep 39฿ ปิดเพราะลูกค้าสับสน — เช็ค toggle ก่อนโชว์ปุ่ม
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $deepPrice = (int) ($this->settings->deep_reading_price ?? 39);
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
        $celticPrice = 99;
        try {
            $celticPrice = (int) app(CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
            // ใช้ default 99
        }

        $items = [];

        // 🌙 (2026-05-23) ปุ่ม Deep — แสดงเฉพาะเมื่อเปิด toggle
        if ($deepEnabled) {
            $items[] = [
                'content_type' => 'text',
                'title' => $isLao ? "🔹 ດວງ {$deepPrice}฿" : "🔹 ดูดวง {$deepPrice}฿",
                'payload' => 'TIER_DEEP_39',
            ];
        }

        if ($celticEnabled) {
            $items[] = [
                'content_type' => 'text',
                'title' => $isLao ? "🔮 ໄພ່ 10 ໃບ {$celticPrice}฿" : "ดู vip ส่วนตัว {$celticPrice}บาท",
                'payload' => 'TIER_CELTIC_99',
            ];
        }

        $items[] = [
            'content_type' => 'text',
            'title' => $isLao ? '📋 ເມນູ' : '📋 เมนู',
            'payload' => 'MENU_OPEN',
        ];

        return $items;
    }

    /**
     * (2026-06-22) ข้อความนี้เป็นกลุ่ม "ทักทาย/ต้อนรับ/เปิดเมนูแพคเกจ" หรือไม่
     *
     * ใช้คัดเฉพาะข้อความที่ "ส่งซ้ำ = สแปม" และไม่ผูกกับ delivery tracking ให้เข้า dedup
     * จงใจไม่ครอบคำทำนาย / QR / เลขบัญชี / ยอดเงิน / redeliver (เนื้อหาเหล่านั้นไม่ซ้ำกัน + เป็น
     * path จ่ายเงิน/ส่งคำทำนายที่ห้ามถูกบล็อก) เพื่อความปลอดภัยสูงสุด
     *
     * @param  string  $message  ข้อความที่กำลังจะส่ง
     */
    protected function isThrottleableGreeting(string $message): bool
    {
        $m = ltrim($message);

        return str_starts_with($m, '🔮 สวัสดี')        // ทักทายเปิดบท / fallback greeting
            || str_contains($m, 'ยินดีต้อนรับ')          // ต้อนรับ + หัวเมนูแพคเกจ (presentTierChoice)
            || str_contains($m, 'เลือกแพคเกจอีกครั้ง')   // re-show เมนูตอนเลือกผิด (tier_choice_invalid)
            || str_contains($m, 'รอเจ้าชะตาเลือกแพคเกจ'); // step hint ตอนรอเลือกแพคเกจ
    }

    /**
     * 🏬 (2026-08-10) ระบบสาขา — กันส่งข้อความด้วย token ผิดเพจ
     *
     * เส้นทาง webhook/queue จะ bind สาขาไว้ให้แล้ว → เมธอดนี้คืนทันที ไม่เสีย query
     *
     * แต่เส้นทาง **คำสั่ง artisan** (fortune:check-pending, fortune:celtic-kick,
     * ตัว auto-finalize ฯลฯ รวมกันเกิน 20 คำสั่ง) ไม่มี context ติดมา
     * ถ้าไม่ดักตรงนี้ มันจะหยิบ token ของ "สาขาหลัก" ไปส่งให้ลูกค้าของสาขาอื่น
     * → Graph API ตอบ 400 แล้วบอทเงียบใส่คนที่จ่ายเงินมาแล้ว
     *
     * @tip PSID ของ Facebook เป็นของ "เพจ" ไม่ใช่ของคน → PSID หนึ่งตัวแปลว่าเพจเดียวเสมอ
     *      จึงย้อนหาสาขาจากบิลล่าสุดของ PSID นั้นได้อย่างแม่นยำ
     */
    protected function ensurePageContextForRecipient(?string $recipientId): void
    {
        if (empty($recipientId)) {
            return;
        }

        // 🔒 มีคนข้างนอก (webhook/queue) bind สาขาไว้แล้ว → เขาถูกกว่าเรา ห้ามแตะ
        //    เช็ค $lazyBoundContext ด้วย ไม่งั้นจะติดกับดักข้อถัดไป 👇
        if (FortunePageContext::current() && ! $this->lazyBoundContext) {
            return;
        }

        // ⚠️ กับดักที่เกือบพลาด: cron ตัวเดียววนส่งลูกค้าหลายคนจากคนละเพจ
        //    ถ้าเช็คแค่ "มี context แล้วหรือยัง" คนแรกจะ bind เพจ A ค้างไว้
        //    แล้วลูกค้าคนที่ 2-50 ของเพจ B จะถูกส่งด้วย token ของเพจ A ทั้งหมด
        //    → ต้องยอมให้ resolve ใหม่ทุกครั้งที่ "ผู้รับเปลี่ยนคน"
        if (($this->lastResolvedRecipient ?? null) === $recipientId) {
            return; // คนเดิม (คำทำนายยาวถูกซอยเป็นหลายข้อความ) — ไม่ต้อง query ซ้ำ
        }
        $this->lastResolvedRecipient = $recipientId;

        try {
            $pageId = FortuneReading::query()
                ->where('platform_user_id', $recipientId)
                ->whereNotNull('fortune_page_id')
                ->orderByDesc('id')
                ->value('fortune_page_id');

            // ลูกค้าใหม่ที่ยังไม่มีบิล → กลับไปใช้ค่ากลาง
            // (ห้ามค้างสาขาของคนก่อนหน้าไว้ ไม่งั้นส่งด้วย token ผิดเพจอยู่ดี)
            FortunePageContext::bindFromId($pageId ? (int) $pageId : null);
            $this->lazyBoundContext = true;

            // ⚠️ ต้องดึง settings ใหม่ — ตัวเดิมถูกจับไว้ตั้งแต่ตอน construct (ค่ากลาง)
            $this->settings = FortuneTellingSetting::getSettings();
            $this->pageAccessToken = $this->settings->facebook_page_token;
        } catch (\Throwable $e) {
            Log::warning('🏬 หาสาขาจากผู้รับไม่สำเร็จ ใช้ค่ากลางต่อ', [
                'recipient' => $recipientId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        // ตรวจสอบ Page Access Token ก่อนส่ง
        if (empty($this->pageAccessToken)) {
            Log::error('❌ ส่งข้อความไม่ได้: ไม่มี Page Access Token', [
                'recipient' => $recipientId,
            ]);

            return false;
        }

        // 🔇 (2026-06-22) กันสแปม "ทักทาย/ต้อนรับ/เมนูแพคเกจ" ส่งซ้ำ — เคส FTU-260622-R6853 (จุไร พิกุลแย้ม):
        //   บอทส่ง "🔮 สวัสดี คุณ... ✨" ซ้ำ 4 ครั้งใน 6 วิ (inbound รัวๆ / FB echo-retry / re-greet หลาย path)
        //   กันที่ choke point เดียวของข้อความ FB ทุก path — แต่ "จำกัดเฉพาะข้อความทักทาย/เมนู" (isThrottleableGreeting)
        //   ⚠️ จงใจไม่แตะ path คำทำนาย/จ่ายเงิน/QR/redeliver — paid-active ห้ามถูกบล็อกเด็ดขาด + retry 0.8s
        //      ของ Celtic Q&A ส่ง "คำทำนาย" (ไม่ใช่ทักทาย) จึงไม่โดน dedup (จับผีรีวิว 2026-06-22 ชี้จุดนี้)
        //   ข้อความทักทายเดียวกันเป๊ะ (sha1) ภายใน 15 วิ ตัวที่ 2+ ถูกระงับ / Cache::add atomic แบบ lock อื่นในระบบ
        //   opt-out: ส่ง $options['allow_duplicate'] = true หากจำเป็นต้องส่งซ้ำจริงๆ
        if (empty($options['allow_duplicate']) && $this->isThrottleableGreeting($message)) {
            $dedupKey = 'fortune:fb_greeting_dedup:'.$recipientId.':'.sha1($message);
            if (! Cache::add($dedupKey, true, 15)) {
                Log::info('🔇 FB: ระงับทักทาย/เมนูซ้ำ (เนื้อหาเดียวกันภายใน 15 วิ)', [
                    'recipient' => $recipientId,
                    'message_preview' => mb_substr($message, 0, 50),
                ]);

                return true; // ถือว่าสำเร็จ — ผู้ใช้เพิ่งได้รับข้อความทักทายนี้เมื่อครู่ (ไม่ผูก delivery tracking)
            }
        }

        // 🔒 (2026-08-12) ทางส่งปุ่มอีกเส้น — sendMessage(..., ['quick_replies' => [...]])
        //   ใช้อยู่ 3 จุด (ปุ่มเลือกภาษา / Celtic delayed / อื่น ๆ) ถ้าไม่ดักตรงนี้ด้วย
        //   ปุ่มพวกนั้นจะยังเป็น quick reply ที่ Meta AI แทรกได้อยู่ = แปลง "ไม่หมด" ตามที่สั่ง
        //   → โยนเข้า sendQuickReplies ซึ่งเป็นเจ้าของตรรกะแปลงเป็น postback ที่เดียว
        //   (ไม่วนซ้ำ: sendQuickReplies เรียก sendMessage กลับมาโดย **ไม่มี** คีย์ quick_replies)
        if (! empty($options['quick_replies']) && is_array($options['quick_replies'])) {
            $formatted = array_map(
                fn ($reply) => $this->normalizeButtonShape($reply),
                $options['quick_replies']
            );

            if ($this->shouldSendAsPostbackButtons($formatted, $options)) {
                return $this->sendQuickReplies($recipientId, $message, $options['quick_replies'], $options);
            }
        }

        $chunks = $this->splitLongMessage($message);
        $maxRetries = 2;

        // กำหนด messaging_type
        // - RESPONSE: ส่งภายใน 24 ชม. หลังผู้ใช้ส่งมา (default)
        // - MESSAGE_TAG: ส่งนอก 24 ชม. ต้องมี tag
        $messagingType = $options['messaging_type'] ?? 'RESPONSE';
        $messageTag = $options['message_tag'] ?? null;

        // เก็บ fallback tag ไว้ใช้กรณี RESPONSE ล้มเหลว (เกิน 24 ชม.)
        // ⛔ (2026-08-13) แท็กทุกตัวถูก Meta ยกเลิก — ดู self::MESSAGE_TAG_USABLE
        $fallbackTag = $messageTag ?? self::MESSAGE_TAG_NAME;

        // ถ้า force_tag → ใช้ MESSAGE_TAG ทันที (admin ส่งมือจริงๆ)
        // ⛔ ข้ามถ้าแท็กใช้ไม่ได้ — ไม่งั้นยิงทิ้งตั้งแต่ครั้งแรกโดยไม่ได้ลอง RESPONSE ด้วยซ้ำ
        if (! empty($options['force_tag']) && self::MESSAGE_TAG_USABLE) {
            $messagingType = 'MESSAGE_TAG';
            $messageTag = $messageTag ?? self::MESSAGE_TAG_NAME;
        }

        // ถ้า from_admin (ระบบส่งอัตโนมัติ) → ลอง RESPONSE ก่อน
        // เพราะส่วนใหญ่ลูกค้าเพิ่งคุยกับบอทใน 24 ชม.
        // ถ้าล้มเหลว จะ fallback เป็น MESSAGE_TAG + POST_PURCHASE_UPDATE
        if (! empty($options['from_admin']) && empty($options['force_tag'])) {
            $messagingType = 'RESPONSE';
        }

        foreach ($chunks as $chunkIndex => $chunk) {
            $sent = false;

            // ลองส่งแต่ละ chunk สูงสุด 2 ครั้ง
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $messagePayload = ['text' => $chunk];

                    // ✅ เพิ่ม Quick Reply buttons ใน chunk สุดท้าย (เฉพาะที่ caller ตั้งใจ pass มา)
                    //
                    // 🚫 (2026-05-21) เลิก auto-inject Default QR "ดูดวง 39฿ / ไพ่ 10 ใบ 99฿ / เมนู"
                    //   ใต้ทุก DM — เปลี่ยนเป็น chatbot mode (เฟรนลี่ ไม่ฮาร์ดเซล)
                    //   ลูกค้าบอก: ปุ่มราคาติดท้ายทุกข้อความ → vending machine ไม่ใช่แม่หมอ
                    //   หลังทำนายจบ ลูกค้าควรพิมพ์ "ดูดวง" เองตามที่ closing message บอก
                    //   `no_default_qr` flag กับ getDefaultQuickReplies() ยังคงไว้กันโค้ดเก่าแตก
                    $isLastChunk = ($chunkIndex === count($chunks) - 1);
                    if ($isLastChunk && ! empty($options['quick_replies'])) {
                        $messagePayload['quick_replies'] = array_map(function ($reply) {
                            return [
                                'content_type' => 'text',
                                'title' => mb_substr($reply['label'] ?? $reply['title'] ?? '', 0, 20),
                                'payload' => $reply['text'] ?? $reply['payload'] ?? $reply['label'] ?? '',
                            ];
                        }, array_slice($options['quick_replies'], 0, 13));
                    }

                    $payload = [
                        'recipient' => ['id' => $recipientId],
                        'message' => $messagePayload,
                        'messaging_type' => $messagingType,
                        'access_token' => $this->pageAccessToken,
                    ];

                    // เพิ่ม tag ถ้ามี
                    if ($messagingType === 'MESSAGE_TAG' && $messageTag) {
                        $payload['tag'] = $messageTag;
                    }

                    $response = Http::timeout(30)
                        ->post($this->graphUrl('/me/messages'), $payload);

                    // เช็ค HTTP status อย่างละเอียด
                    if ($response->successful()) {
                        $sent = true;
                        break;
                    }

                    // Facebook API error - log รายละเอียด
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? $response->body();
                    $errorCode = $errorBody['error']['code'] ?? $response->status();
                    $errorSubcode = $errorBody['error']['error_subcode'] ?? 0;

                    Log::error("❌ Facebook API Error (ครั้งที่ {$attempt})", [
                        'recipient' => $recipientId,
                        'http_status' => $response->status(),
                        'error_code' => $errorCode,
                        'error_subcode' => $errorSubcode,
                        'error_message' => $errorMsg,
                        'messaging_type' => $messagingType,
                        'tag' => $messageTag,
                        'token_prefix' => substr($this->pageAccessToken, 0, 10).'...',
                        'chunk' => $chunkIndex + 1,
                    ]);

                    // ถ้าใช้ RESPONSE แล้ว error (outside 24hr) → ลองใหม่ด้วย MESSAGE_TAG
                    // ⛔ (2026-08-13) ปิดทาง retry เมื่อแท็กใช้ไม่ได้ — retry = call ตกน้ำเปล่า
                    //    ลูกค้าไม่ได้ข้อความอยู่ดี แต่เสียสัญญาณสแปมให้ Meta ฟรี ๆ
                    if (self::MESSAGE_TAG_USABLE
                        && $messagingType === 'RESPONSE'
                        && in_array($errorSubcode, [2018278, 2018065])) {
                        Log::info('🔄 เกิน 24 ชม. ลองใหม่ด้วย MESSAGE_TAG', [
                            'recipient' => $recipientId,
                            'fallback_tag' => $fallbackTag,
                        ]);
                        $messagingType = 'MESSAGE_TAG';
                        $messageTag = $fallbackTag;

                        continue; // retry ด้วย tag ใหม่
                    }

                    // ถ้าใช้ MESSAGE_TAG แล้วยัง error (tag ตาย/ไม่ได้รับอนุมัติ) → log แจ้งเตือน
                    if ($messagingType === 'MESSAGE_TAG' && in_array($errorSubcode, self::DEAD_TAG_SUBCODES)) {
                        Log::warning("⚠️ Facebook MESSAGE_TAG '{$messageTag}' ใช้ไม่ได้ (subcode {$errorSubcode})", [
                            'recipient' => $recipientId,
                            'tag' => $messageTag,
                            'hint' => '1893061 = Meta ยกเลิกแท็กนี้ · 2018276 = ต้องผ่าน App Review',
                        ]);

                        return false;
                    }

                    // 🔦 (2026-08-13) subcode แปลกหน้าตอนใช้ MESSAGE_TAG → ต้องดังไว้ก่อน
                    //    บทเรียน: ของเดิมดักแค่ whitelist [2018276] พอ Meta เปลี่ยนเป็น 1893061
                    //    ก็เงียบสนิท 2 เดือน — "ไม่รู้จัก" ต้องไม่แปลว่า "ไม่ต้องบอก"
                    if ($messagingType === 'MESSAGE_TAG' && $errorSubcode) {
                        Log::warning('🔦 MESSAGE_TAG ล้มด้วย subcode ที่ยังไม่รู้จัก — ตรวจสอบด่วน', [
                            'recipient' => $recipientId,
                            'tag' => $messageTag,
                            'subcode' => $errorSubcode,
                            'error' => mb_substr((string) $errorMsg, 0, 200),
                        ]);
                    }

                    // ถ้าเป็น token error (190) หรือ permission error (10) ไม่ต้อง retry
                    if (in_array($errorCode, [190, 10, 200])) {
                        Log::error('❌ Facebook Token/Permission Error - หยุด retry', [
                            'error_code' => $errorCode,
                            'message' => $errorMsg,
                        ]);

                        return false;
                    }

                    // ✅ ผู้ใช้ block/ปิด DM/ลบบัญชี (#551 subcode 1545041) หรือ user not found (2018108)
                    //    → retry ไม่มีประโยชน์ เสีย worker time (~2s/user) — short-circuit ทันที
                    if (in_array($errorSubcode, [1545041, 2018108])) {
                        Log::info('ℹ️ ผู้ใช้ไม่พร้อมรับข้อความ — ข้าม retry', [
                            'recipient' => $recipientId,
                            'subcode' => $errorSubcode,
                            'message' => $errorMsg,
                        ]);

                        return false;
                    }

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาทีก่อน retry
                    }

                } catch (Exception $e) {
                    Log::error("❌ ส่งข้อความไม่สำเร็จ (ครั้งที่ {$attempt}): ".$e->getMessage(), [
                        'recipient' => $recipientId,
                        'chunk' => $chunkIndex + 1,
                    ]);

                    if ($attempt < $maxRetries) {
                        usleep(1000000); // รอ 1 วินาที
                    }
                }
            }

            if (! $sent) {
                Log::error('❌ ส่งข้อความล้มเหลวหลังลอง '.$maxRetries.' ครั้ง', [
                    'recipient' => $recipientId,
                    'chunk_text_preview' => mb_substr($chunk, 0, 100),
                ]);

                return false;
            }
        }

        Log::info('✅ ส่งข้อความสำเร็จ', [
            'recipient' => $recipientId,
            'chunks' => count($chunks),
        ]);

        return true;
    }

    /**
     * ส่งรูปภาพผ่าน Messenger API
     *
     * 🆕 (2026-05-07) รองรับ Private Replies endpoint สำหรับ comment engagement
     *   ถ้า $options['comment_id'] ระบุไว้ → ลอง /me/messages + recipient.comment_id ก่อน
     *   bypass 24hr window + แก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน"
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $imageUrl  URL ของรูปภาพ (ต้องเป็น HTTPS public URL)
     * @param  string|null  $previewUrl  ข้อความกำกับรูป (ส่งแยก message ถ้ามี)
     * @param  array  $options  options เพิ่มเติม:
     *                          - comment_id: string|null  ใช้ Private Replies endpoint (bypass 24hr/551)
     *                          - skip_unreachable_cache: bool  ข้าม per-user 551 cache (สำหรับ test command)
     * @return bool สำเร็จหรือไม่
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        $commentId = $options['comment_id'] ?? null;
        $skipUnreachableCache = $options['skip_unreachable_cache'] ?? false;

        // 🔒 (2026-05-07) Per-user 551-cache — skip user ที่รู้แล้วว่ารับ DM ไม่ได้วันนี้
        //   ลด log spam + ลด API calls ที่ fail แน่ๆ
        //   ยกเว้นกรณีมี comment_id → Private Replies bypass 24hr window ได้
        $unreachableKey = "fb_user_unreachable:{$recipientId}:".now()->format('Y-m-d');
        if (! $skipUnreachableCache && empty($commentId) && Cache::has($unreachableKey)) {
            Log::info('sendImage: skip — user marked unreachable today (no comment_id)', [
                'recipient' => $recipientId,
            ]);

            return false;
        }

        // 🎯 (2026-05-07) ถ้ามี comment_id → ลอง Private Replies endpoint ก่อน
        //   รองรับ 7 วันหลังคอมเม้นต์ + bypass error 551 สำหรับ user ที่ไม่เคยทักเพจ
        if (! empty($commentId)) {
            $sentViaPR = $this->sendPrivateReplyImage($commentId, $imageUrl);
            if ($sentViaPR) {
                if (! empty($previewUrl)) {
                    $this->sendMessage($recipientId, $previewUrl);
                }

                return true;
            }
            // ถ้า Private Replies fail → fallback ไป /me/messages ปกติ (อาจ work ถ้า user อยู่ใน 24hr window)
            Log::info('sendImage: Private Replies fail → fallback /me/messages', [
                'recipient' => $recipientId,
                'comment_id' => $commentId,
            ]);
        }

        // 🩹 (2026-05-07) Auto-fallback ถ้า RESPONSE fail (error 551 / 24hr window expired)
        //   เคสจริง: banner welcome push หลังลูกค้าเก่าทักอีกครั้ง — บางครั้ง 24hr window ปิดแล้ว
        //   FB error_subcode 1545041 → RESPONSE rejected → ต้อง MESSAGE_TAG=POST_PURCHASE_UPDATE
        // ⛔ (2026-08-13) แท็กถูกยกเลิกหมดแล้ว → เหลือ RESPONSE อย่างเดียว (ดู MESSAGE_TAG_USABLE)
        $messagingTypes = self::MESSAGE_TAG_USABLE ? ['RESPONSE', 'MESSAGE_TAG'] : ['RESPONSE'];
        $lastSubcode = 0;
        $lastErrMsg = '';

        foreach ($messagingTypes as $msgType) {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $imageUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'messaging_type' => $msgType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($msgType === 'MESSAGE_TAG') {
                $payload['tag'] = 'POST_PURCHASE_UPDATE';
            }

            try {
                $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $payload);

                if ($response->successful()) {
                    if (! empty($previewUrl)) {
                        $this->sendMessage($recipientId, $previewUrl);
                    }

                    Log::info('ส่งรูปภาพสำเร็จ', [
                        'recipient' => $recipientId,
                        'image_url' => $imageUrl,
                        'messaging_type' => $msgType,
                    ]);

                    return true;
                }

                $errBody = $response->json();
                $errSubcode = $errBody['error']['error_subcode'] ?? 0;
                $errMsg = $errBody['error']['message'] ?? $response->body();
                $lastSubcode = $errSubcode;
                $lastErrMsg = $errMsg;

                Log::warning('ส่งรูปภาพ '.$msgType.' fail', [
                    'recipient' => $recipientId,
                    'http_status' => $response->status(),
                    'error_subcode' => $errSubcode,
                    'error_message' => $errMsg,
                ]);

                // ถ้า RESPONSE fail ด้วย 24hr-window error → ลอง MESSAGE_TAG ต่อ
                // 1545041 = "บุคคลนี้ไม่พร้อมใช้งาน" (24hr expired)
                // 2018278/2018065 = window expired
                if ($msgType === 'RESPONSE' && in_array($errSubcode, [1545041, 2018278, 2018065])) {
                    continue; // fallback to MESSAGE_TAG
                }

                // Token/permission errors → หยุด ไม่ retry
                $errCode = $errBody['error']['code'] ?? 0;
                if (in_array($errCode, [190, 10, 200])) {
                    Log::error('ส่งรูปภาพ: token/permission error — หยุด retry', [
                        'recipient' => $recipientId,
                        'error_code' => $errCode,
                    ]);

                    return false;
                }

                // ถ้า MESSAGE_TAG ก็ fail แล้ว → return false
                if ($msgType === 'MESSAGE_TAG') {
                    Log::error('ส่งรูปภาพล้มเหลว ทั้ง RESPONSE และ MESSAGE_TAG', [
                        'recipient' => $recipientId,
                        'image_url' => $imageUrl,
                        'last_error' => $errMsg,
                    ]);
                    break; // ไป cache mark unreachable ข้างล่าง
                }
            } catch (Exception $e) {
                Log::error('ส่งรูปภาพ '.$msgType.' exception: '.$e->getMessage(), [
                    'recipient' => $recipientId,
                ]);
                if ($msgType === 'MESSAGE_TAG') {
                    break;
                }
            }
        }

        // 🔒 (2026-05-07) Mark user unreachable วันนี้ ถ้าเป็น 24hr-window error
        //   1545041 / 2018278 / 2018065 = user ไม่อยู่ใน 24hr window — ส่งภาพไม่ได้แน่ๆ จนกว่าจะทักก่อน
        //   cache กัน log spam + กัน API calls สิ้นเปลือง
        if (in_array($lastSubcode, [1545041, 2018278, 2018065])) {
            $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now(), absolute: true));
            Cache::put($unreachableKey, true, $secondsUntilMidnight);
            Log::info('sendImage: mark user unreachable today', [
                'recipient' => $recipientId,
                'subcode' => $lastSubcode,
                'ttl_seconds' => $secondsUntilMidnight,
            ]);
        }

        return false;
    }

    /**
     * ส่งภาพผ่าน Private Replies endpoint
     *
     * 🆕 (2026-05-07) แก้ปัญหา banner ส่งไม่ได้ผ่าน comment engagement
     *   เดิม: sendImage ใช้ /me/messages + recipient.id → fail 551 ถ้า user ไม่เคยทักเพจ
     *   ใหม่: ใช้ /me/messages + recipient.comment_id → bypass 24hr + รองรับ 7 วันหลังคอมเม้นต์
     *
     * @param  string  $commentId  Comment ID ที่จะตอบ
     * @param  string  $imageUrl  URL ของรูปภาพ
     */
    public function sendPrivateReplyImage(string $commentId, string $imageUrl): bool
    {
        try {
            $payload = [
                'recipient' => ['comment_id' => $commentId],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $imageUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'access_token' => $this->pageAccessToken,
            ];

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $payload);

            if ($response->successful()) {
                Log::info('✅ ส่งภาพผ่าน Private Reply สำเร็จ', [
                    'comment_id' => $commentId,
                    'image_url' => $imageUrl,
                ]);

                return true;
            }

            $err = $response->json();
            Log::warning('Private Reply image ล้ม', [
                'comment_id' => $commentId,
                'http_status' => $response->status(),
                'error_code' => $err['error']['code'] ?? null,
                'error_subcode' => $err['error']['error_subcode'] ?? null,
                'error_message' => $err['error']['message'] ?? $response->body(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('Private Reply image exception: '.$e->getMessage(), [
                'comment_id' => $commentId,
            ]);

            return false;
        }
    }

    /**
     * 🎯 (2026-05-25 v2) Option G — ส่งภาพ + Quick Replies ใน Private Reply call เดียว
     *
     * แก้ race condition #10900 "Activity already replied" — FB Reels comment_id ใช้ครั้งเดียว
     * ปกติเราส่ง image (1 call) + text+QR (1 call) → 2 calls = ติด race
     * วิธีนี้: 1 call เดียว image attachment + quick_replies → atomic, no race possible
     *
     * @param  string  $imageUrl  banner image URL (https)
     * @param  array  $quickReplies  pre-formatted FB structure: [{content_type, title, payload}]
     * @return bool true ถ้าส่งสำเร็จ
     */
    public function sendPrivateReplyImageWithQuickReplies(string $commentId, string $imageUrl, array $quickReplies): bool
    {
        try {
            // FB จำกัด 13 quick replies + title 20 chars
            $formatted = array_map(function ($qr) {
                // รองรับทั้ง pre-formatted และ raw {title, payload}
                if (isset($qr['content_type'])) {
                    return [
                        'content_type' => $qr['content_type'],
                        'title' => mb_substr((string) ($qr['title'] ?? ''), 0, 20),
                        'payload' => (string) ($qr['payload'] ?? $qr['title'] ?? ''),
                    ];
                }

                return [
                    'content_type' => 'text',
                    'title' => mb_substr((string) ($qr['title'] ?? ''), 0, 20),
                    'payload' => (string) ($qr['payload'] ?? $qr['title'] ?? ''),
                ];
            }, array_slice($quickReplies, 0, 13));

            $payload = [
                'recipient' => ['comment_id' => $commentId],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $imageUrl,
                            'is_reusable' => true,
                        ],
                    ],
                    'quick_replies' => $formatted,
                ],
                'access_token' => $this->pageAccessToken,
            ];

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $payload);

            if ($response->successful()) {
                Log::info('✅ Private Reply image+QR สำเร็จ (Option G atomic)', [
                    'comment_id' => $commentId,
                    'qr_count' => count($formatted),
                ]);

                return true;
            }

            $err = $response->json();
            Log::info('Private Reply image+QR (Option G) ล้ม', [
                'comment_id' => $commentId,
                'http_status' => $response->status(),
                'error_code' => $err['error']['code'] ?? null,
                'error_subcode' => $err['error']['error_subcode'] ?? null,
                'error_message' => $err['error']['message'] ?? $response->body(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('Private Reply image+QR exception: '.$e->getMessage(), [
                'comment_id' => $commentId,
            ]);

            return false;
        }
    }

    /**
     * 🎙️ (2026-05-08) ส่ง audio attachment ไป Facebook Messenger
     *
     * FB Audio Attachment API:
     *   - attachment.type = 'audio'
     *   - payload.url ต้องเป็น HTTPS + .mp3/.m4a (max 25MB)
     *   - is_reusable = true → cache attachment_id ใช้ซ้ำได้
     *   - รองรับทั้ง RESPONSE (24hr window) และ MESSAGE_TAG (POST_PURCHASE_UPDATE)
     *
     * Reference: https://developers.facebook.com/docs/messenger-platform/send-messages#audio
     *
     * @param  string  $recipientId  Facebook PSID
     * @param  string  $audioUrl  HTTPS URL ของ mp3 file
     * @param  array  $options  ['comment_id' => ?, 'message_tag' => 'POST_PURCHASE_UPDATE'|null]
     */
    public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        $commentId = $options['comment_id'] ?? null;
        $messageTag = $options['message_tag'] ?? null;

        // ลองผ่าน Private Replies (comment_id) ก่อนถ้ามี
        if (! empty($commentId)) {
            try {
                $prPayload = [
                    'recipient' => ['comment_id' => $commentId],
                    'message' => [
                        'attachment' => [
                            'type' => 'audio',
                            'payload' => [
                                'url' => $audioUrl,
                                'is_reusable' => true,
                            ],
                        ],
                    ],
                    'access_token' => $this->pageAccessToken,
                ];

                $prResponse = Http::timeout(30)->post($this->graphUrl('/me/messages'), $prPayload);
                if ($prResponse->successful()) {
                    Log::info('✅ ส่ง audio ผ่าน Private Reply สำเร็จ', [
                        'comment_id' => $commentId,
                    ]);

                    return true;
                }
                Log::info('sendAudio: Private Reply fail → fallback /me/messages', [
                    'comment_id' => $commentId,
                ]);
            } catch (Exception $e) {
                Log::warning('sendAudio: Private Reply exception', ['error' => $e->getMessage()]);
            }
        }

        // 🛡️ (2026-06-20) RESPONSE-first เสมอ — FB เลิกแท็ก POST_PURCHASE_UPDATE แล้ว (subcode 1893061)
        //   เดิม: มี message_tag → ['MESSAGE_TAG'] อย่างเดียว → ส่ง audio ด้วย tag ที่ตายแล้ว → 400 reject
        //   → ลูกค้า FB กด/พิมพ์ "อ่านให้ฟัง" ไม่เคยได้ไฟล์เสียง (LINE ได้เพราะไม่ใช้ tag)
        //   ลูกค้าขอเสียงหลังทำนาย = อยู่ใน 24 ชม.เสมอ → RESPONSE ผ่าน. tag เหลือเป็น fallback นอก 24 ชม.
        // ⛔ (2026-08-13) ยืนยันแล้วว่าแท็กตายทุกตัว → ตัด fallback ทิ้ง (ดู MESSAGE_TAG_USABLE)
        //    หมายเหตุ: บรรทัดบนนี้รู้เรื่อง 1893061 มาตั้งแต่ 2026-06-20 แต่แก้แค่ audio ตัวเดียว
        //    เมธอดอื่นอีก 4 ตัวใช้แท็กที่ตายแล้วต่อมาอีก 2 เดือน — รอบนี้อุดพร้อมกันทั้งหมด
        $messagingTypes = self::MESSAGE_TAG_USABLE ? ['RESPONSE', 'MESSAGE_TAG'] : ['RESPONSE'];

        foreach ($messagingTypes as $msgType) {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'audio',
                        'payload' => [
                            'url' => $audioUrl,
                            'is_reusable' => true,
                        ],
                    ],
                ],
                'messaging_type' => $msgType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($msgType === 'MESSAGE_TAG') {
                $payload['tag'] = $messageTag ?: 'POST_PURCHASE_UPDATE';
            }

            try {
                $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $payload);

                if ($response->successful()) {
                    Log::info('✅ ส่ง audio สำเร็จ', [
                        'recipient' => $recipientId,
                        'audio_url' => $audioUrl,
                        'messaging_type' => $msgType,
                    ]);

                    return true;
                }

                $errBody = $response->json();
                $errSubcode = $errBody['error']['error_subcode'] ?? 0;
                $errMsg = $errBody['error']['message'] ?? $response->body();

                Log::warning('sendAudio '.$msgType.' fail', [
                    'recipient' => $recipientId,
                    'http_status' => $response->status(),
                    'error_subcode' => $errSubcode,
                    'error_message' => $errMsg,
                ]);

                // RESPONSE fail ด้วย 24hr expired → ไป MESSAGE_TAG
                if ($msgType === 'RESPONSE' && in_array($errSubcode, [1545041, 2018278, 2018065])) {
                    continue;
                }

                // permission error → หยุด
                $errCode = $errBody['error']['code'] ?? 0;
                if (in_array($errCode, [190, 10, 200])) {
                    return false;
                }
            } catch (Exception $e) {
                Log::warning('sendAudio exception: '.$e->getMessage(), [
                    'recipient' => $recipientId,
                    'messaging_type' => $msgType,
                ]);
            }
        }

        return false;
    }

    /**
     * ส่ง typing indicator (แสดงจุดสามจุดว่ากำลังพิมพ์)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  bool  $on  เปิด/ปิด typing indicator
     */
    public function sendTypingIndicator(string $recipientId, bool $on = true): void
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            Http::timeout(10)
                ->post($this->graphUrl('/me/messages'), [
                    'recipient' => ['id' => $recipientId],
                    'sender_action' => $on ? 'typing_on' : 'typing_off',
                    'access_token' => $this->pageAccessToken,
                ]);
        } catch (Exception $e) {
            // ไม่ต้อง throw error ถ้า typing indicator ส่งไม่ได้
            Log::debug('ส่ง typing indicator ไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * 🔒 (2026-08-12) ปุ่มที่ "ห้ามเป็น Quick Reply" — ต้องส่งเป็น postback button เท่านั้น
     *
     * Quick Reply ของ FB อยู่ติดช่องพิมพ์ → บางเครื่อง/บางเวอร์ชันการกดปุ่มวิ่งผ่านช่องพิมพ์
     * ทำให้ FB ยัด mention **"@Meta AI"** ติดมา แล้ว `quick_reply.payload` **หายทั้งก้อน**
     * (เหลือแค่ `message.text` = ป้ายปุ่ม) → บอทไม่รู้ว่านี่คือการกดปุ่ม → ตกไปหา AI chat
     *
     * วัดจริง 2026-08-12 (laravel.log วันเดียว): ปุ่ม `🎁 รับดวงฟรีประจำวัน`
     * payload มาถึงปกติ 7 ครั้ง / **หลุดเป็นข้อความ 7 ครั้ง** = หลุดครึ่งหนึ่ง
     *
     * postback button อยู่ "ในกล่องข้อความ" ไม่ผ่านช่องพิมพ์เลย → Meta AI แทรกไม่ได้
     *
     * ⚠️ ข้อจำกัด FB ที่กำหนดรูปแบบการส่ง:
     *   - button template รับได้ **สูงสุด 3 ปุ่ม/กล่อง** → ชุดที่เกิน (เช่น 7 วันเกิด)
     *     **แตกเป็นหลายกล่องส่งไปให้ครบทีเดียว** ไม่ใช่ตกกลับไปเป็น quick reply
     *   - text ของ template ยาวได้ 640 ตัวอักษร (ยาวกว่านั้นต้องแยกกล่อง)
     * ⚠️ ปุ่ม postback **ค้างอยู่ในประวัติแชท** กดย้อนหลังได้ (ต่างจาก quick reply ที่หายไป
     *    ทันทีที่กด) — ไม่ใช่ของใหม่ในระบบนี้: การ์ด Rich Template (เมนูต้อนรับ / การ์ดชวนดูดวง)
     *    ใช้ปุ่ม postback มาตั้งแต่ต้นอยู่แล้ว ด่านของแต่ละ flow จึงรับมือปุ่มเก่าอยู่แล้ว
     * ✅ routing ไม่ต้องแก้: `processPostback()` มี `default => handleQuickReply()` อยู่แล้ว
     *
     * 🔁 (2026-08-12 รอบ 2, เจ้าของสั่ง "เปลี่ยนให้หมดในฝั่ง fb")
     *    เลิกใช้บัญชีรายชื่อปุ่ม → **แปลงทุกชุดที่แปลงได้** เหลือไว้เป็น quick reply เฉพาะชุดที่
     *    FB ไม่ยอมให้ทำ (เกิน 3 ปุ่ม / ต้องไปพร้อมรูปในข้อความเดียว)
     *    ถ้าวันหน้าเจอปุ่มที่ "ห้ามค้างในประวัติ" ให้ตัดออกที่ shouldSendAsPostbackButtons() จุดเดียว
     */

    /** FB จำกัดความยาว text ของ button template */
    protected const BUTTON_TEMPLATE_TEXT_LIMIT = 640;

    /** FB จำกัดจำนวนปุ่มใน button template — ชุดที่มากกว่านี้แปลงไม่ได้ ต้องคงเป็น quick reply */
    protected const MAX_TEMPLATE_BUTTONS = 3;

    /**
     * ส่งข้อความพร้อม Quick Reply buttons
     *
     * รองรับ options:
     * - messaging_type: 'RESPONSE' (default) หรือ 'MESSAGE_TAG'
     * - force_quick_replies: true = ห้ามแปลงเป็น postback button (กันวนซ้ำตอน fallback)
     * - message_tag: tag สำหรับ MESSAGE_TAG เช่น 'POST_PURCHASE_UPDATE'
     * - from_comment_engagement: true เมื่อเรียกจาก comment engagement
     *   → จะ fallback เป็น MESSAGE_TAG อัตโนมัติถ้า RESPONSE ล้มเหลว
     * - comment_id: ถ้ามี + from_comment_engagement=true จะลองส่งผ่าน Private Replies
     *   endpoint (/{comment-id}/private_replies) ก่อน เพื่อ bypass 24hr window และ
     *   error 551 "บุคคลนี้ไม่พร้อมใช้งาน" (user ไม่เคยทักเพจมาก่อน)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string  $message  ข้อความหลัก
     * @param  array  $quickReplies  ปุ่ม quick reply [['title' => 'ข้อความ', 'payload' => 'DATA']]
     * @param  array  $options  ตัวเลือกเพิ่มเติม
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            $formattedReplies = array_map(
                fn ($reply) => $this->normalizeButtonShape($reply),
                array_slice($quickReplies, 0, 13) // Facebook จำกัด 13 quick replies
            );

            // 🚨 (2026-05-14) Bug fix — message + quick_replies > 2000 chars → FB reject 400
            //   user report: "AI เหมือนจะตอบแต่เงียบ"
            //   เคสจริง reading 2537: message_length=2874 + quick_replies → HTTP 400 → ข้อความไม่ถึงลูกค้า
            //   Fix: ถ้ายาว → split + ส่ง body ผ่าน sendMessage (มี chunking) + last chunk แนบ quick_replies
            //   Threshold: 1800 chars (เผื่อ JSON overhead + quick_replies metadata ~150 chars)
            if (mb_strlen($message) > 1800) {
                $allChunks = $this->splitLongMessage($message);
                $lastChunk = array_pop($allChunks);

                // ส่ง chunks 1..N-1 เป็น plain message (sendMessage มี chunking + retry เอง)
                foreach ($allChunks as $chunk) {
                    $this->sendMessage($recipientId, $chunk, [
                        'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
                        'message_tag' => $options['message_tag'] ?? null,
                        'no_default_qr' => true,
                        'from_admin' => $options['from_admin'] ?? false,
                    ]);
                    usleep(300000); // 0.3s gap between chunks
                }

                // แทนที่ message ด้วย last chunk + ส่งต่อด้านล่าง (จะมี quick_replies แนบ)
                $message = $lastChunk;
            }

            $messagingType = $options['messaging_type'] ?? 'RESPONSE';
            $messageTag = $options['message_tag'] ?? null;
            $fromCommentEngagement = $options['from_comment_engagement'] ?? false;
            $commentId = $options['comment_id'] ?? null;
            $fromAdmin = $options['from_admin'] ?? false;

            // 🔧 (2026-05-03) ถ้า from_admin + ระบุ message_tag → ใช้ MESSAGE_TAG ตั้งแต่แรก
            //   เคสหลัก: SMS payment confirmation push หลัง 24hr (ลูกค้าจ่ายแล้วเงียบ)
            //
            // 🚨 (2026-08-13) จุดนี้อันตรายที่สุดในไฟล์ — เส้นทางของ **ลูกค้าที่จ่ายเงินแล้ว**
            //   ผู้เรียกที่ส่ง message_tag มา ~20 จุด ล้วนเป็น push หลังชำระเงิน:
            //   FortuneCelticRedeliver · ProcessDeepFortuneReadingJob · FortuneProSessionNudge ·
            //   VerifySlipFallbackJob · FortuneRecoverPaidNoBirthdate · FortuneCheckPendingReadings
            //
            //   ของเดิมตั้ง MESSAGE_TAG **ตั้งแต่ครั้งแรก** = ไม่เคยลอง RESPONSE เลย
            //   พอแท็กถูก Meta ยกเลิก (1893061) → ล้มทันที และ retry ข้างล่างก็ไม่ทำงาน
            //   เพราะเงื่อนไข retry เช็ค `$messagingType === 'RESPONSE'` ซึ่งไม่มีวันจริงในเคสนี้
            //   ⇒ ลูกค้าจ่ายเงินแล้วไม่ได้คำทำนาย โดยไม่มี fallback ใด ๆ
            //
            //   RESPONSE-first จึงดีกว่าเสมอ: คนที่เพิ่งจ่าย/เพิ่งคุย = อยู่ในกรอบ 24 ชม. → ผ่าน
            //   (แนวเดียวกับที่แก้ sendAudio ไปแล้วเมื่อ 2026-06-20 แต่ตอนนั้นแก้แค่จุดเดียว)
            if ($fromAdmin && ! empty($messageTag) && self::MESSAGE_TAG_USABLE) {
                $messagingType = 'MESSAGE_TAG';
            }

            // ถ้าเป็น comment engagement + มี comment_id → ลอง Private Replies ก่อน
            // Private Replies รองรับ 7 วันหลังคอมเม้นต์ และไม่ต้องอยู่ใน conversation window
            // แก้ปัญหา error 551 "บุคคลนี้ไม่พร้อมใช้งาน"
            if ($fromCommentEngagement && $commentId) {
                $privateReplySuccess = $this->sendPrivateReply($commentId, $message, $formattedReplies);
                if ($privateReplySuccess) {
                    return true;
                }
                // ถ้า Private Replies ล้มเหลว → fallback ไปยัง /me/messages ข้างล่าง
                Log::info('🔄 Private Replies ล้มเหลว → fallback ไปยัง /me/messages', [
                    'comment_id' => $commentId,
                    'recipient' => $recipientId,
                ]);
            }

            // ═══════════════════════════════════════════════════════════════
            // 🔒 (2026-08-12) ชุดปุ่มที่ห้ามเป็น Quick Reply → ส่งเป็น postback button
            // ═══════════════════════════════════════════════════════════════
            //   เหตุผลเต็มอยู่ที่ const BUTTON_TEMPLATE_TEXT_LIMIT ด้านบน
            //   (สรุป: quick reply อยู่ติดช่องพิมพ์ → Meta AI แทรก → payload หายทั้งก้อน)
            //
            //   ⚠️ ต้องอยู่ **หลัง** Private Reply — สาย comment engagement ต้องได้ลอง
            //      recipient.comment_id ก่อน เพราะเป็นทางเดียวที่ข้ามกรอบ 24 ชม.ได้
            //   ⚠️ ล้มแล้วไหลลงไปใช้ quick reply เดิม — ลูกค้าไม่ได้ปุ่มเลย แย่กว่าปุ่มที่อาจหลุด
            if ($this->shouldSendAsPostbackButtons($formattedReplies, $options)) {
                if ($this->sendPostbackButtons($recipientId, $message, $formattedReplies, $options)) {
                    return true;
                }

                Log::warning('🔒 postback button ล้ม → ตกกลับไปใช้ quick reply ตามเดิม', [
                    'recipient' => $recipientId,
                    'payloads' => array_column($formattedReplies, 'payload'),
                ]);
            }

            // 🩹 (2026-05-04) Defensive guard — ถ้า quick_replies ว่าง (caller ตั้งใจไม่มีปุ่ม)
            //    Facebook reject empty array ใน quick_replies key
            //    → ส่งข้อความปกติแทน (ไม่ใส่ quick_replies key) ผ่าน sendMessage
            //    เพื่อรักษา 24hr-fallback + chunk-split ใน sendMessage
            if (empty($formattedReplies)) {
                return $this->sendMessage($recipientId, $message, [
                    'messaging_type' => $messagingType,
                    'message_tag' => $messageTag,
                    'no_default_qr' => true,
                    'from_admin' => $fromAdmin,
                ]);
            }

            // 🔍 (2026-08-12) มาถึงตรงนี้ = ยังส่งเป็น quick reply อยู่ = **@Meta AI ยังแทรกได้**
            //   หลังแปลงยกระบบแล้วตรงนี้ควรเงียบเกือบสนิท — log ไว้เพื่อ "เห็นของจริง" ว่าเหลืออะไร
            //   แทนการเดาจากการอ่านโค้ด (ชุดปุ่มหลายชุดประกอบตอน runtime ดูจากโค้ดนิ่ง ๆ ไม่ครบ)
            //   วิธีตรวจ: grep "ยังเป็น quick reply" ใน storage/logs/laravel.log บน prod
            $this->logRemainingQuickReply($recipientId, $formattedReplies, $options);

            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'text' => $message,
                    'quick_replies' => $formattedReplies,
                ],
                'messaging_type' => $messagingType,
                'access_token' => $this->pageAccessToken,
            ];

            if ($messagingType === 'MESSAGE_TAG' && $messageTag) {
                $payload['tag'] = $messageTag;
            }

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $payload);

            if ($response->successful()) {
                return true;
            }

            // ตรวจสอบ error เพื่อ fallback
            $errorBody = $response->json();
            $errorSubcode = $errorBody['error']['error_subcode'] ?? 0;
            $errorCode = $errorBody['error']['code'] ?? 0;

            // กรณี 24hr expired → ลองใหม่ด้วย MESSAGE_TAG + POST_PURCHASE_UPDATE
            // 🔧 (2026-05-03) ขยายเงื่อนไข — ไม่ต้องเป็นแค่ comment engagement
            //   เคสที่กันตอนนี้: SMS payment confirmation push หลัง 24hr (ลูกค้า paid แล้วเงียบ)
            // 🚨 (2026-08-13) เพิ่ม 1545041 (#551) ให้ตรงกับ sendMessage/sendButtonTemplate
            //   เดิมตกหล่น → ยิง quick reply ไม่ผ่าน → throw → catch → sendMessage()
            //   = ลูกค้าได้ข้อความ **แบบไม่มีปุ่มเลย** (แย่กว่าปุ่มที่อาจโดน Meta AI แทรก)
            $is24hrError = in_array($errorSubcode, [2018278, 2018065, 2018001, 1545041]);
            // ⛔ (2026-08-13) แท็กตายทุกตัว → retry คือ call ตกน้ำ (วัดจาก prod 57 ครั้ง/วันจากจุดนี้จุดเดียว)
            if (self::MESSAGE_TAG_USABLE && $is24hrError && $messagingType === 'RESPONSE') {
                Log::info('🔄 Quick Replies: RESPONSE ล้มเหลว (24hr expired) → ลองใหม่ด้วย MESSAGE_TAG', [
                    'recipient' => $recipientId,
                    'error_subcode' => $errorSubcode,
                    'from_comment_engagement' => $fromCommentEngagement,
                    'from_admin' => $fromAdmin,
                ]);

                $payload['messaging_type'] = 'MESSAGE_TAG';
                $payload['tag'] = $messageTag ?? self::MESSAGE_TAG_NAME;

                $retryResponse = Http::timeout(30)
                    ->post($this->graphUrl('/me/messages'), $payload);

                if ($retryResponse->successful()) {
                    return true;
                }

                Log::warning('⚠️ Quick Replies: MESSAGE_TAG fallback ล้มเหลวเช่นกัน', [
                    'recipient' => $recipientId,
                    'error' => $retryResponse->json()['error']['message'] ?? $retryResponse->body(),
                ]);
            }

            // Token/Permission error → ไม่ต้อง fallback
            if (in_array($errorCode, [190, 10, 200])) {
                Log::error('❌ Quick Replies: Token/Permission Error', [
                    'recipient' => $recipientId,
                    'error_code' => $errorCode,
                    'error' => $errorBody['error']['message'] ?? '',
                ]);

                return false;
            }

            // throw เพื่อให้ catch ข้างล่าง fallback เป็นข้อความธรรมดา
            $response->throw();

            return true;
        } catch (Exception $e) {
            Log::error('ส่ง quick replies ไม่สำเร็จ: '.$e->getMessage());

            // Fallback: ส่งข้อความธรรมดา
            return $this->sendMessage($recipientId, $message);
        }
    }

    /**
     * 🔒 (2026-08-12) ชุดปุ่มนี้ต้องส่งเป็น postback button แทน Quick Reply ไหม
     *
     * นโยบาย (เจ้าของสั่งรอบ 2): **แปลงทุกชุดที่แปลงได้** — เหลือเป็น quick reply เฉพาะที่ FB
     * ไม่ยอมให้ทำเท่านั้น เพราะ quick reply คือช่องทางเดียวที่ Meta AI แทรกแล้ว payload หาย
     *
     * คงเป็น quick reply เมื่อ:
     *   - ถูกสั่งบังคับ (`force_quick_replies`) — ใช้ตอน fallback กันวนซ้ำ
     *   - ชุดว่าง
     *   - มีปุ่มที่ payload ว่าง → เป็น postback แล้ว **ปุ่มตาย** (`processPostback` return ทันที
     *     เมื่อ payload ว่าง) ส่วน quick reply ยัง fallback ไปใช้ title เป็น payload ได้
     *
     * 📦 (2026-08-12 รอบ 3, เจ้าของ) **เกิน 3 ปุ่มไม่ใช่เหตุให้ตกกลับไปเป็น quick reply อีกแล้ว**
     *    → แตกเป็นหลายกล่อง กล่องละ ≤3 ปุ่ม **ส่งไปให้ครบทีเดียว** (ดู sendPostbackButtons)
     *    เจ้าของเสนอเองว่า "สร้างกล่องส่งไปให้ครบดีกว่า" แทนปุ่ม "ถัดไป" ทีละหน้า — ถูกแล้ว:
     *      • ไม่ต้องกดเพิ่มเพื่อเห็นตัวเลือกที่เหลือ (ลูกค้าส่วนใหญ่เป็นผู้สูงอายุ)
     *      • ไม่ต้องจำ state หน้าถัดไปไว้ใน Cache ซึ่ง deploy สั่ง cache:clear ทุกครั้ง
     *        → ปุ่ม "ถัดไป" ที่ค้างในแชทจะกลายเป็นปุ่มตายทันทีที่ deploy
     *
     * @param  array<int, array>  $formattedReplies  ปุ่มที่ format แล้ว
     */
    protected function shouldSendAsPostbackButtons(array $formattedReplies, array $options = []): bool
    {
        if (! empty($options['force_quick_replies'])) {
            return false;
        }

        if (count($formattedReplies) < 1) {
            return false;
        }

        foreach ($formattedReplies as $reply) {
            if (trim((string) ($reply['payload'] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * 🔧 (2026-08-12) ปรับรูปปุ่มให้เป็นทรงเดียวกันก่อนใช้งาน — **ตัวแปลงตัวเดียวของฝั่ง FB**
     *
     * ปุ่มในระบบนี้ถูกประกาศ 2 ทรงปนกัน เพราะบางจุดเขียนให้ LINE ก่อนแล้วส่ง FB ด้วยชุดเดียวกัน:
     *   ทรง FB   : ['title' => ..., 'payload' => ...]
     *   ทรง LINE : ['label' => ..., 'text' => ...]     ← ไม่มี title/payload เลย
     *
     * 🐛 เคสจริงที่เจอตอนไล่ตรวจ (2026-08-12): ปุ่ม "📜 เลิกทำนายและสรุปผล" ที่ส่งพร้อม
     *    คำทำนาย Celtic (SendCelticDelayedPrediction) ประกาศเป็นทรง LINE ล้วน → ตัวเช็คเดิม
     *    อ่าน title/payload ไม่เจอ → มองว่า "payload ว่าง" → **ไม่แปลงเป็น postback**
     *    = ปุ่มกลางคำทำนาย ซึ่งเป็นจังหวะที่ลูกค้าใช้บ่อยที่สุด ยังโดน @Meta AI แทรกได้อยู่
     *    (ปุ่มใบนี้เคยพังมาแล้วรอบหนึ่งเพราะ FB ส่ง title กลับมาแทน payload — 2026-05-29)
     *
     * ลำดับ payload: `payload` (ของ FB โดยตรง) → `text` (คีย์เวิร์ดที่ LINE ส่ง) → `title`/`label`
     * ทั้ง 3 ทางปลายทางเดียวกัน — handleQuickReply ที่ไม่มี case จะส่งค่านั้นเป็นข้อความลูกค้าต่อ
     *
     * @param  array<string, mixed>  $reply
     * @return array{content_type: string, title: string, payload: string}
     */
    protected function normalizeButtonShape(array $reply): array
    {
        $title = (string) ($reply['title'] ?? $reply['label'] ?? '');
        $payload = (string) ($reply['payload'] ?? $reply['text'] ?? $reply['title'] ?? $reply['label'] ?? '');

        return [
            'content_type' => 'text',
            'title' => mb_substr($title, 0, 20),
            'payload' => $payload,
        ];
    }

    /**
     * 🔍 (2026-08-12) บันทึกชุดปุ่มที่ยัง "หลุด" ออกไปเป็น quick reply
     *
     * เจ้าของสั่งว่าฝั่ง FB ต้องไม่เหลือปุ่มแบบที่ Meta AI แทรกได้เลย — ตัวนี้คือหลักฐาน
     * ว่าเหลือจริงไหม/ชุดไหน แทนการไล่อ่านโค้ดแล้วเดา (ชุดปุ่มหลายชุดประกอบตอน runtime)
     *
     * @param  array<int, array>  $formattedReplies
     */
    protected function logRemainingQuickReply(string $recipientId, array $formattedReplies, array $options = []): void
    {
        $reason = 'unknown';

        if (! empty($options['force_quick_replies'])) {
            $reason = 'fallback_หลัง_postback_ล้ม'; // ตั้งใจ — กันวนซ้ำ ไม่ใช่ของหลุด
        } else {
            foreach ($formattedReplies as $reply) {
                if (trim((string) ($reply['payload'] ?? '')) === '') {
                    $reason = 'payload_ว่าง';
                    break;
                }
            }
        }

        Log::info('🔍 FB: ชุดปุ่มนี้ยังเป็น quick reply (Meta AI แทรกได้)', [
            'recipient' => $recipientId,
            'reason' => $reason,
            'count' => count($formattedReplies),
            'titles' => array_column($formattedReplies, 'title'),
            'payloads' => array_column($formattedReplies, 'payload'),
        ]);
    }

    /**
     * 📦 (2026-08-12) แตกปุ่มเป็นกล่อง ๆ ละไม่เกิน 3 ปุ่ม — **กระจายให้สมดุล** ไม่ใช่ตัด 3-3-เศษ
     *
     * 7 ปุ่ม → 3+2+2 (ไม่ใช่ 3+3+1 ที่ทิ้งกล่องสุดท้ายมีปุ่มเดียวโดด ๆ)
     * 4 ปุ่ม → 2+2 · 13 ปุ่ม → 3+3+3+2+2 · ลำดับปุ่มเดิมคงไว้ทุกกรณี (วันจันทร์…อาทิตย์ห้ามสลับ)
     *
     * @param  array<int, array>  $buttons
     * @return array<int, array<int, array>>
     */
    protected function chunkButtonsForTemplates(array $buttons): array
    {
        $buttons = array_values($buttons);
        $total = count($buttons);

        if ($total <= self::MAX_TEMPLATE_BUTTONS) {
            return [$buttons];
        }

        $boxCount = (int) ceil($total / self::MAX_TEMPLATE_BUTTONS);
        $base = intdiv($total, $boxCount);
        $extra = $total % $boxCount; // กล่องแรก ๆ รับปุ่มเกินไปคนละ 1

        $boxes = [];
        $cursor = 0;

        for ($i = 0; $i < $boxCount; $i++) {
            $size = $base + ($i < $extra ? 1 : 0);
            $boxes[] = array_slice($buttons, $cursor, $size);
            $cursor += $size;
        }

        return $boxes;
    }

    /**
     * 🔘 format ปุ่มดิบ {title, payload} → ปุ่ม postback ของ FB
     *
     * @param  array<int, array>  $buttons
     * @return array<int, array<string, string>>
     */
    protected function formatPostbackButtons(array $buttons): array
    {
        return array_map(function ($button) {
            return [
                'type' => 'postback',
                'title' => mb_substr((string) ($button['title'] ?? ''), 0, 20),
                'payload' => (string) ($button['payload'] ?? ''),
            ];
        }, array_values($buttons));
    }

    /**
     * 🔘 (2026-08-12) ส่งข้อความ + ปุ่มแบบ postback (button template)
     *
     * ปุ่มอยู่ในกล่องข้อความ ไม่ผ่านช่องพิมพ์ → Meta AI แทรกไม่ได้ payload ไม่หาย
     *
     * 📦 เกิน 3 ปุ่ม → **แตกเป็นหลายกล่อง ส่งต่อกันไปให้ครบในทีเดียว**
     *    (เจ้าของเลือกทางนี้แทนปุ่ม "ถัดไป" ทีละหน้า — ลูกค้าไม่ต้องกดเพิ่มเพื่อเห็นตัวเลือก
     *     ที่เหลือ และไม่ต้องเก็บ state หน้าถัดไปไว้ใน Cache ที่ deploy ล้างทิ้งทุกครั้ง)
     *
     * @param  array<int, array>  $buttons  ปุ่มรูป {title, payload}
     * @return bool true = ลูกค้าได้รับข้อความแล้ว (ไม่ต้อง fallback ต่อ)
     */
    protected function sendPostbackButtons(string $recipientId, string $message, array $buttons, array $options = []): bool
    {
        $boxes = $this->chunkButtonsForTemplates($buttons);
        $boxTotal = count($boxes);

        $templateOptions = [
            'from_admin' => $options['from_admin'] ?? false,
            'message_tag' => $options['message_tag'] ?? null,
        ];

        $cta = '🌙 กดปุ่มด้านล่างได้เลยค่ะ';
        $leadText = $message;
        $somethingDelivered = false;

        // ข้อความยาวเกิน 640 → เนื้อความไปก่อน (sendMessage มี chunking + 24hr fallback ครบ)
        // แล้วค่อยตามด้วยกล่องปุ่ม — ยัดลง template ตรง ๆ จะโดนตัดกลางคำทำนาย
        if (mb_strlen($message) > self::BUTTON_TEMPLATE_TEXT_LIMIT) {
            $bodySent = $this->sendMessage($recipientId, $message, [
                'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
                'message_tag' => $options['message_tag'] ?? null,
                'no_default_qr' => true,
                'from_admin' => $options['from_admin'] ?? false,
            ]);

            // เนื้อความยังไม่ถึงลูกค้า → ยังไม่มีอะไรส่งซ้ำ ให้ผู้เรียกตกกลับไปทาง quick reply ได้
            if (! $bodySent) {
                return false;
            }

            usleep(300000); // 0.3s — ให้กล่องเนื้อความขึ้นก่อนกล่องปุ่มเสมอ
            $leadText = $cta;
            $somethingDelivered = true;
        }

        foreach ($boxes as $index => $boxButtons) {
            // กล่องแรกถือข้อความจริง · กล่องถัด ๆ ไปบอกว่ายังมีตัวเลือกต่อ + เลขหน้า
            // (ต้องมีเลขหน้า ไม่งั้นลูกค้าเห็น 3 ปุ่มแรกแล้วนึกว่ามีให้เลือกแค่นั้น)
            $text = $index === 0
                ? $leadText
                : '🌙 ตัวเลือกเพิ่มเติม ('.($index + 1)."/{$boxTotal}) 👇";

            $sent = $this->sendButtonTemplate(
                $recipientId,
                $this->buildButtonTemplatePayload($text, $this->formatPostbackButtons($boxButtons)),
                $templateOptions
            );

            if ($sent) {
                $somethingDelivered = true;

                if ($index + 1 < $boxTotal) {
                    usleep(300000); // เว้นจังหวะให้กล่องเรียงตามลำดับเสมอ
                }

                continue;
            }

            // ── กล่องนี้ส่งไม่ผ่าน ──
            // ยังไม่มีอะไรถึงลูกค้าเลย → คืน false ให้ผู้เรียก fallback เป็น quick reply ทั้งชุด
            if (! $somethingDelivered) {
                return false;
            }

            // 🚨 ส่งไปแล้วบางส่วน → ห้ามคืน false (ผู้เรียกจะส่งข้อความ + ปุ่มซ้ำทั้งก้อน)
            //    ยิง "ปุ่มที่เหลือ" เป็น quick reply ปิดท้าย ดีกว่าปล่อยให้ปุ่มหายไปเงียบ ๆ
            $remaining = array_merge(...array_slice($boxes, $index));

            Log::warning('🔒 กล่องปุ่ม postback ล้มกลางทาง → ยิงปุ่มที่เหลือเป็น quick reply แทน', [
                'recipient' => $recipientId,
                'box_index' => $index,
                'box_total' => $boxTotal,
                'payloads' => array_column($remaining, 'payload'),
            ]);

            //    ตัด comment_id ทิ้งด้วย — Private Reply ถูกลองไปแล้วและล้มก่อนจะมาถึงตรงนี้
            //    ยิงซ้ำได้แค่ error #10900 (Reels comment_id ใช้ครั้งเดียว) เปล่า ๆ
            $this->sendQuickReplies($recipientId, $cta, $remaining, array_merge($options, [
                'force_quick_replies' => true,
                'from_comment_engagement' => false,
                'comment_id' => null,
            ]));

            return true;
        }

        return true;
    }

    /**
     * 🧱 ประกอบ payload ของ button template (FB: text ≤ 640 ตัวอักษร, ปุ่ม ≤ 3)
     *
     * @param  array<int, array>  $templateButtons  ปุ่มที่ format เป็น postback แล้ว
     * @return array<string, mixed>
     */
    protected function buildButtonTemplatePayload(string $text, array $templateButtons): array
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($text, 0, self::BUTTON_TEMPLATE_TEXT_LIMIT),
                    'buttons' => $templateButtons,
                ],
            ],
        ];
    }

    /**
     * ตอบกลับในคอมเมนต์
     *
     * @param  string  $commentId  Comment ID
     * @param  string  $message  ข้อความตอบกลับ
     */
    public function replyToComment(string $commentId, string $message): bool
    {
        try {
            Http::timeout(30)
                ->post($this->graphUrl("/{$commentId}/comments"), [
                    'message' => mb_substr($message, 0, 8000), // Facebook comment limit
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('ตอบคอมเมนต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            // HTTP 403 = token ไม่มี pages_manage_engagement permission
            // ต้องไปขออนุมัติ App Review ที่ Facebook Developer Console
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ตอบคอมเมนต์ไม่สำเร็จ: '.$msg, [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Access Token ขาด pages_manage_engagement scope — เช็คที่ Facebook Dev Console'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 💗 ส่ง reaction (LIKE/LOVE/HAHA/WOW/SAD/ANGRY) ให้ comment
     *
     * ใช้ Graph API: POST /{comment-id}/reactions?type=LOVE
     *
     * ผลพลอยได้:
     * - FB algorithm เห็นว่า Page engage กับ comment → boost reach
     * - User รู้สึกว่าเพจ active + ใส่ใจ
     * - ลูกค้าจะ react กลับมาเยอะขึ้น (ส่งสัญญาณดีให้ FB)
     *
     * @param  string  $type  LIKE | LOVE | HAHA | WOW | SAD | ANGRY (default: LOVE ❤️)
     */
    public function reactToComment(string $commentId, string $type = 'LOVE'): bool
    {
        try {
            Http::timeout(15)
                ->post($this->graphUrl("/{$commentId}/reactions"), [
                    'type' => $type,
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('💗 React comment สำเร็จ', [
                'comment_id' => $commentId,
                'type' => $type,
            ]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            // 100/3 = comment ไม่พบหรือถูกลบ
            // 403 = token ขาด pages_manage_engagement
            Log::warning('React comment ล้มเหลว: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'type' => $type,
            ]);

            return false;
        }
    }

    /**
     * 🙈 ซ่อนคอมเม้นต์ (ผู้คอมยังเห็นเอง คนอื่นไม่เห็น)
     *
     * ใช้ Graph API: POST /{comment-id} body is_hidden=true
     * ต้องการ scope: pages_manage_engagement
     *
     * แนะนำใช้แทน deleteComment เพราะ:
     * - ผู้คอมไม่รู้ตัวว่าโดนซ่อน → ไม่กลับมาแก้แค้น
     * - กลับคืนได้ (set is_hidden=false)
     * - ไม่กระทบ FB algorithm signal เหมือน delete
     */
    public function hideComment(string $commentId): bool
    {
        try {
            Http::timeout(15)
                ->post($this->graphUrl("/{$commentId}"), [
                    'is_hidden' => 'true',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('🙈 ซ่อนคอมเม้นต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ซ่อนคอมเม้นต์ไม่สำเร็จ: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Token ขาด pages_manage_engagement scope'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 🗑️ ลบคอมเม้นต์ (ลบถาวร — irreversible)
     *
     * ใช้ Graph API: DELETE /{comment-id}
     * ต้องการ scope: pages_manage_engagement
     *
     * ⚠️ ใช้เฉพาะกรณีจำเป็น (สแปมหนักๆ ที่ใส่หาก) — ปกติแนะนำ hideComment กว่า
     */
    public function deleteComment(string $commentId): bool
    {
        try {
            Http::timeout(15)
                ->delete($this->graphUrl("/{$commentId}"), [
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            Log::info('🗑️ ลบคอมเม้นต์สำเร็จ', ['comment_id' => $commentId]);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $is403 = str_contains($msg, '403');
            Log::error('ลบคอมเม้นต์ไม่สำเร็จ: '.mb_substr($msg, 0, 200), [
                'comment_id' => $commentId,
                'hint' => $is403
                    ? '⚠️ HTTP 403 → Page Token ขาด pages_manage_engagement scope'
                    : null,
            ]);

            return false;
        }
    }

    /**
     * 🔍 ตรวจว่าข้อความมีลิงค์ภายนอก (ที่ไม่อยู่ใน whitelist) หรือไม่
     *
     * จับรูปแบบทั่วไป:
     * - URL: http://, https://, www.xxx
     * - Shorteners: bit.ly, t.me, t.co, lin.ee/<id>, m.me/<id>
     * - Lazy domain: xxx.com, xxx.net, xxx.io (ที่ไม่มี protocol)
     * - Dot-evasion: "thaiprompt dot online" → ก็จับ
     *
     * @param  array<string>  $whitelistDomains  โดเมนที่อนุญาต (ไม่ตรวจ scheme)
     * @return bool true = พบลิงค์ที่ไม่ใช่ whitelist
     */
    public function containsExternalLink(string $message, array $whitelistDomains = []): bool
    {
        return $this->firstExternalDomain($message, $whitelistDomains) !== null;
    }

    /**
     * 🔍 คืน "โดเมนภายนอกตัวแรก" ที่เจอในข้อความ (null = ไม่มี/whitelist ทั้งหมด)
     *
     * แยกออกมาจาก containsExternalLink() เพื่อให้หน้าแอดมินบันทึกได้ว่า
     * โดนตัดเพราะโดเมนไหน — เดิมรู้แค่ true/false เลยตรวจย้อนหลังไม่ได้
     *
     * @param  array<string>  $whitelistDomains  โดเมนที่อนุญาต (ไม่ตรวจ scheme)
     * @return string|null โดเมนที่ไม่อยู่ใน whitelist เช่น 'families.com'
     *
     * @example $svc->firstExternalDomain('ดูเลย abc.online', ['thaiprompt.online']);
     * // ผลลัพธ์: 'abc.online'
     */
    public function firstExternalDomain(string $message, array $whitelistDomains = []): ?string
    {
        $normalized = mb_strtolower($message);

        // Normalize "dot" / "จุด" evasion → "."
        $normalized = preg_replace('/\s+(dot|จุด)\s+/u', '.', $normalized);

        // 🚨 (2026-08-09) ตัด "อีเมล" ทิ้งก่อนเสมอ — xxx@gmail.com ไม่ใช่ลิงก์
        //   ลูกค้าพิมพ์อีเมลตัวเองใส่คอมเมนต์บ่อยมาก (เจอ 3 รายในสแกนย้อนหลัง)
        //   ถ้าไม่ตัด = โดนบล็อกเพราะให้ช่องทางติดต่อ
        $normalized = preg_replace('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/u', ' ', $normalized) ?? $normalized;

        // สองชั้น — แยกตาม "ความน่าเชื่อถือของสัญญาณ"
        //   ชั้น 1 มี protocol / www. / path → เป็นลิงก์แน่ ใช้ TLD ชุดเต็ม
        //   ชั้น 2 เขียนโดเมนเปล่าๆ → เชื่อได้เฉพาะ TLD กลุ่มเสี่ยง (ดู LINK_TLD_BARE_RISKY)
        //     เพราะ "คำ.com" เปล่าๆ ส่วนใหญ่คือเสียงพูดแปลงข้อความมั่ว ไม่ใช่ลิงก์
        $patterns = [
            '/(?:https?:\/\/|www\.)[a-z0-9.-]+/i',
            '/[a-z0-9-]+\.(?:'.self::LINK_TLD_PATTERN.')\//i',
            '/[a-z0-9-]+\.(?:'.self::LINK_TLD_BARE_RISKY.')\b/i',
        ];

        $hits = [];
        foreach ($patterns as $p) {
            if (preg_match_all($p, $normalized, $m)) {
                $hits = array_merge($hits, $m[0]);
            }
        }

        if (empty($hits)) {
            return null;
        }

        // ทุก match → strip ออกมาเป็น domain เพียงๆ → เช็ค whitelist
        foreach ($hits as $hit) {
            $domain = $this->extractDomain($hit);
            if (empty($domain)) {
                continue;
            }

            $isWhitelisted = false;
            foreach ($whitelistDomains as $allowed) {
                $allowed = mb_strtolower(trim($allowed));
                if (empty($allowed)) {
                    continue;
                }
                // exact หรือ subdomain match
                if ($domain === $allowed || str_ends_with($domain, '.'.$allowed)) {
                    $isWhitelisted = true;
                    break;
                }
            }

            if (! $isWhitelisted) {
                return $domain; // พบลิงค์ที่ไม่ใช่ whitelist
            }
        }

        return null;
    }

    /**
     * 🔗 ดึงลิงก์ภายนอกจาก payload ของคอมเมนต์ (กรณีคอมเมนต์ "ไม่มีข้อความเลย")
     *
     * ปิดรูรั่วคู่แฝดของเคส DM ที่อุดไปแล้วใน commit 11bdc7bde — คนแชร์โพสต์/ลิงก์
     * เข้ามาในคอมเมนต์โดยไม่พิมพ์อะไรเลย ทำให้ moderateLinkComment() คืน false ทันที
     * (เพราะเช็คแต่ $message) = มองไม่เห็นสแปมตั้งแต่ต้น ไม่มีแม้แต่ log
     *
     * ⚠️ อ่านเฉพาะ 'link' และ attachment ชนิด share/fallback เท่านั้น
     *    **ห้ามอ่าน photo / video** — นั่นคือ CDN ของรูปที่ลูกค้าแนบ (เช่นสลิปโอนเงิน)
     *    จะกลายเป็น false-positive ทันที (บทเรียนเดียวกับฝั่ง DM)
     *
     * @param  array<string, mixed>  $comment  payload จาก webhook field=feed item=comment
     * @return string|null URL ภายนอกตัวแรกที่เจอ
     */
    public function firstExternalLinkFromComment(array $comment, array $whitelistDomains = []): ?string
    {
        $candidates = [];

        // FB ส่ง link ของคอมเมนต์ที่แชร์ลิงก์มาเป็น string ตรงๆ
        if (! empty($comment['link']) && is_string($comment['link'])) {
            $candidates[] = $comment['link'];
        }

        // บางเวอร์ชันส่งเป็น attachments (โครงเดียวกับ Messenger)
        foreach ($comment['attachments']['data'] ?? [] as $att) {
            if (! is_array($att)) {
                continue;
            }

            // ✅ เฉพาะ share/fallback — photo/video คือรูปแนบ ห้ามแตะ
            $type = (string) ($att['type'] ?? '');
            if (! in_array($type, ['share', 'fallback'], true)) {
                continue;
            }

            foreach ([$att['unshimmed_url'] ?? null, $att['url'] ?? null, $att['target']['url'] ?? null] as $url) {
                if (! empty($url) && is_string($url)) {
                    $candidates[] = $url;
                }
            }
        }

        foreach ($candidates as $url) {
            if ($this->firstExternalDomain($url, $whitelistDomains) !== null) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Helper: แยก domain จากชิ้น match (best-effort)
     */
    protected function extractDomain(string $hit): string
    {
        $hit = mb_strtolower(trim($hit));
        $hit = preg_replace('#^https?://#', '', $hit);
        $hit = preg_replace('#^www\.#', '', $hit);
        // ตัด path ทิ้ง
        $hit = explode('/', $hit, 2)[0];

        return rtrim($hit, '.');
    }

    /**
     * 📜 ดึงโพสของเพจย้อนหลัง (สำหรับ scan คอมเก่า)
     *
     * ใช้ /{page}/published_posts (ครอบคลุมกว่า /posts) — รวม Reels ใน Graph v17+
     *
     * @param  int  $sinceTimestamp  unix timestamp ตั้งแต่เมื่อไหร่
     * @param  int  $limit  จำนวนโพสสูงสุด (default 100)
     * @return array<array{id:string, created_time:string}>
     */
    public function listRecentPosts(int $sinceTimestamp, int $limit = 100): array
    {
        return $this->fetchPagedFeed('/published_posts', $sinceTimestamp, $limit);
    }

    /**
     * 🎬 ดึง Reels ของเพจย้อนหลัง (สแปมในคลิปไวรัลมักเยอะ)
     *
     * Graph API endpoint /{page}/video_reels — แยกจาก /published_posts บางครั้ง
     *
     * @return array<array{id:string, created_time:string}>
     */
    public function listRecentReels(int $sinceTimestamp, int $limit = 100): array
    {
        return $this->fetchPagedFeed('/video_reels', $sinceTimestamp, $limit);
    }

    /**
     * Helper: paginate Graph API feed-style endpoint
     */
    protected function fetchPagedFeed(string $relPath, int $sinceTimestamp, int $limit): array
    {
        $this->lastFetchError = null; // reset
        $pageId = $this->settings->facebook_page_id ?? null;
        if (empty($pageId)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_id ใน FortuneTellingSetting';

            return [];
        }
        if (empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_token ใน FortuneTellingSetting';

            return [];
        }

        $items = [];
        $url = $this->graphUrl("/{$pageId}{$relPath}");
        $params = [
            'access_token' => $this->pageAccessToken,
            'fields' => 'id,created_time',
            'since' => $sinceTimestamp,
            'limit' => 25,
        ];

        try {
            while (count($items) < $limit && $url) {
                $resp = Http::timeout(20)->get($url, $params);
                if (! $resp->successful()) {
                    $errorMsg = $resp->json('error.message') ?? 'HTTP '.$resp->status();
                    $errorCode = $resp->json('error.code');
                    $errorType = $resp->json('error.type');
                    $this->lastFetchError = "Graph API {$relPath} → HTTP {$resp->status()} | code={$errorCode} | type={$errorType} | {$errorMsg}";
                    Log::warning("fetchPagedFeed ล้มเหลว ({$relPath})", [
                        'status' => $resp->status(),
                        'error' => $resp->json('error'),
                        'page_id' => $pageId,
                    ]);
                    break;
                }
                $data = $resp->json('data', []);
                foreach ($data as $row) {
                    $items[] = $row;
                    if (count($items) >= $limit) {
                        break;
                    }
                }
                $url = $resp->json('paging.next');
                $params = []; // pagination URL มี params ในตัวแล้ว
            }
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();
            Log::warning("fetchPagedFeed exception ({$relPath}): ".$e->getMessage());
        }

        return $items;
    }

    /** Last error message from fetchPagedFeed — for command-level diagnostics */
    public ?string $lastFetchError = null;

    /**
     * 🏬 กุญแจ breaker แยกตามสาขา — เพจคนละใบถือ token คนละสิทธิ์
     *
     * ⚠️ ถ้าใช้กุญแจกลางตัวเดียว เพจที่ไม่มี `pages_read_user_content` จะตัดเพจอื่นไปด้วย
     */
    protected function readCommentsBreakerKey(): string
    {
        return self::READ_COMMENTS_BREAKER_KEY.':'.(FortunePageContext::currentId() ?? 'default');
    }

    /**
     * 🚦 ตัดการอ่านคอมเมนต์ชั่วคราวเมื่อ Graph ปฏิเสธเพราะสิทธิ์
     *
     * log เป็น warning **ครั้งเดียวตอนตัดวงจร** — ไม่ใช่ทุกโพสต์
     * (ของเดิม warning ทุกโพสต์ = 50 บรรทัดต่อรอบ กลบสัญญาณอื่นจนหาไม่เจอ)
     *
     * @param  mixed  $errorCode  code จาก Graph (10 / 200 / 190)
     * @param  string  $errorMsg  ข้อความ error สำหรับให้แอดมินอ่านออก
     */
    protected function tripReadCommentsBreaker($errorCode, string $errorMsg): void
    {
        if (Cache::add($this->readCommentsBreakerKey(), 1, now()->addHours(self::READ_COMMENTS_BREAKER_HOURS))) {
            Log::warning('🚦 ตัดการอ่านคอมเมนต์ผ่าน Graph ชั่วคราว — สิทธิ์ไม่พอ', [
                'error_code' => $errorCode,
                'error' => mb_substr($errorMsg, 0, 200),
                'พักถึง' => now()->addHours(self::READ_COMMENTS_BREAKER_HOURS)->toDateTimeString(),
                'hint' => 'ต้องมี pages_read_user_content — ระหว่างนี้ realtime webhook ยังกันสแปมได้ปกติ',
                'fortune_page_id' => FortunePageContext::currentId(),
            ]);
        }
    }

    /**
     * 💬 ดึงคอมเม้นต์ทั้งหมดของโพสหนึ่ง (paginated)
     *
     * @return array<array{id:string, message:string, from?:array}>
     */
    public function listCommentsForPost(string $postId, int $limit = 200): array
    {
        $this->lastFetchError = null;
        if (empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ pageAccessToken';

            return [];
        }

        // 🚦 (2026-08-13) CIRCUIT BREAKER — สิทธิ์อ่านคอมเมนต์ถูกปฏิเสธทั้งเพจ
        //   วัดจาก prod: cron `fortune:scan-old-comments` (รายชั่วโมง) ยิงจุดนี้ 57 ครั้ง/วัน
        //   และ **ล้ม 100%** ด้วย (#10) ต้องมี pages_read_user_content / (#200) Missing Permissions
        //   ⇒ toggle `auto_hide_link_comments` เปิดอยู่ + cron เดินครบ + ได้ผลลัพธ์ศูนย์
        //     = ภาพลวงว่าเพจมีเกราะกันสแปม ทั้งที่ไม่มี
        //
        //   ⚠️ ไม่ได้ทำให้ระบบกันสแปมอ่อนลง — ด่านจริงคือ realtime webhook
        //      (moderateLinkComment อ่านข้อความจาก payload ตรง ๆ ไม่ต้องใช้ scope เลย)
        //      ตัวนี้เป็นแค่ตัวไล่เก็บย้อนหลัง ซึ่งอ่านอะไรไม่ได้อยู่แล้ว
        //
        //   เจอครั้งแรก = พัก 6 ชม. แล้วลองใหม่เอง (ถ้าวันหลังได้ scope จะกลับมาทำงานเอง)
        if (Cache::has($this->readCommentsBreakerKey())) {
            $this->lastFetchError = 'ข้ามชั่วคราว — สิทธิ์อ่านคอมเมนต์ถูกปฏิเสธ (circuit breaker)';

            return [];
        }

        $comments = [];
        $url = $this->graphUrl("/{$postId}/comments");
        $params = [
            'access_token' => $this->pageAccessToken,
            'fields' => 'id,message,from,is_hidden,created_time',
            // ❌ filter=stream เคยทำให้ comments หายในบางเคส — ใช้ default (toplevel)
            'limit' => 50,
        ];

        try {
            while (count($comments) < $limit && $url) {
                $resp = Http::timeout(20)->get($url, $params);
                if (! $resp->successful()) {
                    $errorMsg = $resp->json('error.message') ?? 'HTTP '.$resp->status();
                    $errorCode = $resp->json('error.code');
                    $this->lastFetchError = "comments {$postId} → HTTP {$resp->status()} | code={$errorCode} | {$errorMsg}";
                    Log::warning('listCommentsForPost ล้มเหลว', [
                        'post_id' => $postId,
                        'status' => $resp->status(),
                        'error' => $resp->json('error'),
                    ]);

                    // 🚦 permission error = ทั้งเพจอ่านไม่ได้ ไม่ใช่โพสต์นี้โพสต์เดียว
                    //    → ตัดวงจรทันที ไม่งั้นวนยิงต่ออีก 199 โพสต์ที่เหลือแล้วล้มเหมือนกันหมด
                    if (in_array((int) $errorCode, [10, 200, 190], true)) {
                        $this->tripReadCommentsBreaker($errorCode, $errorMsg);
                    }

                    break;
                }
                $data = $resp->json('data', []);
                foreach ($data as $row) {
                    $comments[] = $row;
                    if (count($comments) >= $limit) {
                        break;
                    }
                }
                $url = $resp->json('paging.next');
                $params = [];
            }
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();
            Log::warning('listCommentsForPost exception: '.$e->getMessage(), [
                'post_id' => $postId,
            ]);
        }

        return $comments;
    }

    /**
     * ส่งข้อความ Private Reply ตอบคอมเม้นต์ (ไป DM/Messenger)
     *
     * ใช้ endpoint POST /{comment-id}/private_replies ซึ่ง:
     * - รองรับการส่ง DM ตอบกลับคอมเม้นต์ได้ 7 วันหลังคอมเม้นต์
     * - ไม่ต้องอยู่ใน 24-hour conversation window
     * - แก้ปัญหา error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ
     *
     * Flow:
     * 1. ลอง structured message (text + quick_replies) ก่อน
     * 2. ถ้า FB ปฏิเสธ structure → fallback เป็น text-only
     *
     * @param  string  $commentId  Comment ID ที่จะตอบ
     * @param  string  $message  ข้อความที่จะส่งไป DM
     * @param  array  $quickReplies  Formatted quick replies (optional)
     */
    public function sendPrivateReply(string $commentId, string $message, array $quickReplies = []): bool
    {
        try {
            // 🎯 (2026-05-04) ลอง Send API + recipient.comment_id ก่อนเสมอ (รวมเคส empty quick_replies)
            //   เหตุผล: endpoint นี้รองรับ Reels comment ที่ /private_replies endpoint ไม่รองรับ
            //   (regression จาก commit 67bbbd4da ที่ลบปุ่ม Quick Reply → fall back ไป /private_replies
            //    → Reels comment_id error 100 "Object does not exist")
            //   ส่ง message เป็น array (ไม่ json_encode) เพราะ Laravel Http::post serialize ให้
            $messagePayload = ['text' => $message];
            if (! empty($quickReplies)) {
                $messagePayload['quick_replies'] = $quickReplies;
            }

            $structuredPayload = [
                'recipient' => ['comment_id' => $commentId],
                'message' => $messagePayload,
                'access_token' => $this->pageAccessToken,
            ];

            $response = Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), $structuredPayload);

            if ($response->successful()) {
                Log::info('✅ Private Reply สำเร็จ (Send API + recipient.comment_id)', [
                    'comment_id' => $commentId,
                    'has_quick_replies' => ! empty($quickReplies),
                ]);

                return true;
            }

            Log::info('Private Reply (Send API) ล้มเหลว → fallback text-only', [
                'comment_id' => $commentId,
                'error' => $response->json()['error']['message'] ?? $response->body(),
            ]);

            // Fallback: text-only ผ่าน /{comment-id}/private_replies
            $textResponse = Http::timeout(30)
                ->post($this->graphUrl("/{$commentId}/private_replies"), [
                    'message' => $message,
                    'access_token' => $this->pageAccessToken,
                ]);

            if ($textResponse->successful()) {
                Log::info('✅ Private Reply สำเร็จ (text-only)', [
                    'comment_id' => $commentId,
                ]);

                return true;
            }

            $errorBody = $textResponse->json();
            Log::warning('Private Reply ล้มเหลว', [
                'comment_id' => $commentId,
                'http_status' => $textResponse->status(),
                'error_code' => $errorBody['error']['code'] ?? null,
                'error_subcode' => $errorBody['error']['error_subcode'] ?? null,
                'error_message' => $errorBody['error']['message'] ?? $textResponse->body(),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('Private Reply exception: '.$e->getMessage(), [
                'comment_id' => $commentId,
            ]);

            return false;
        }
    }

    // ============================================================
    // Facebook Graph API: การดึงข้อมูลผู้ใช้
    // ============================================================

    /**
     * ดึงข้อมูลโปรไฟล์จาก Facebook Graph API
     */
    public function getUserProfile(string $facebookUserId): ?array
    {
        // 🚀 (2026-05-06) Cache 24hr — กัน sync HTTP call ทุกข้อความ (200-800ms)
        //   FB profile name ไม่เปลี่ยนบ่อย — 24hr TTL พอ
        //   ถ้า cache hit → return ทันที (ไม่ทำ HTTP call)
        $cacheKey = "fb:user_profile:{$facebookUserId}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['name'])) {
            return $cached;
        }

        try {
            // ดึงข้อมูลพื้นฐาน + ลอง fields เพิ่มเติม (gender, birthday, locale)
            // หมายเหตุ: gender/birthday/locale อาจไม่ได้รับจาก PSID เนื่องจาก Facebook API restrictions
            // แต่ใส่ไว้เผื่อ App ได้รับ permission แล้ว
            //
            // 🩹 (2026-05-08) Try minimal fields first — ลด FB privacy 400 error
            //   FB Graph 2018+ privacy: full fields list อาจ trigger 400 ถ้า App ไม่มี advanced perms
            //   ลำดับ: name+first_name+last_name (สำคัญสุด) → fallback ถ้ายัง fail
            $response = Http::timeout(15)
                ->get($this->graphUrl("/{$facebookUserId}"), [
                    'fields' => 'id,name,first_name,last_name,profile_pic',
                    'access_token' => $this->pageAccessToken,
                ]);

            // 🩹 ถ้า 400 — ลอง minimal fields (id+first_name+last_name อย่างเดียว)
            if (! $response->successful() && $response->status() === 400) {
                Log::info('FB getUserProfile: HTTP 400 — retry with minimal fields', [
                    'user_id' => $facebookUserId,
                    'first_attempt_body' => mb_substr($response->body(), 0, 300),
                ]);
                $response = Http::timeout(15)
                    ->get($this->graphUrl("/{$facebookUserId}"), [
                        'fields' => 'first_name,last_name',
                        'access_token' => $this->pageAccessToken,
                    ]);
            }

            if (! $response->successful()) {
                throw new Exception('FB Graph HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300));
            }

            $profile = $response->json();

            // ⚠️ FB Graph API Privacy (2018+): field `name` บางครั้งไม่ return
            // แต่ `first_name` / `last_name` มักจะได้ → compose ให้ always มี `name` field
            if (empty($profile['name'])) {
                $composed = trim(
                    ($profile['first_name'] ?? '').' '.($profile['last_name'] ?? '')
                );
                if ($composed !== '') {
                    $profile['name'] = $composed;
                }
            }

            // ถ้าได้ birthday มา → คำนวณอายุ
            if (! empty($profile['birthday'])) {
                try {
                    $birthDate = Carbon::parse($profile['birthday']);
                    $profile['age'] = $birthDate->age;
                    $profile['birth_date_formatted'] = $birthDate->format('Y-m-d');
                } catch (Exception $e) {
                    // birthday format อาจเป็นแค่ MM/DD
                }
            }

            Log::info('ดึงโปรไฟล์ผู้ใช้สำเร็จ', [
                'user_id' => $facebookUserId,
                'has_name' => ! empty($profile['name']),
                'has_first_name' => ! empty($profile['first_name']),
                'has_gender' => ! empty($profile['gender']),
                'has_birthday' => ! empty($profile['birthday']),
                'has_locale' => ! empty($profile['locale']),
            ]);

            // 💾 cache profile 24hr (เฉพาะตอนได้ name) — กัน HTTP roundtrip ทุก message
            if (! empty($profile['name'])) {
                Cache::put($cacheKey, $profile, now()->addHours(24));
            }

            return $profile;
        } catch (Exception $e) {
            // 🩹 (2026-05-08) เพิ่ม error body + token snippet ใน log — debug FB privacy/token issues
            //    เคสที่พบ: HTTP 400 ทุก call → เคส page token expired หรือ permission revoked
            Log::warning('ไม่สามารถดึงโปรไฟล์ผู้ใช้ได้', [
                'user_id' => $facebookUserId,
                'error' => $e->getMessage(),
                'token_first_8' => mb_substr($this->pageAccessToken ?? '', 0, 8),
                'token_len' => strlen($this->pageAccessToken ?? ''),
                'hint' => 'HTTP 400 ที่ User Profile API = Facebook จำกัด (ต้อง Advanced Access / App Review) — ไม่ใช่ token หมดอายุ และไม่บล็อกการส่ง DM (Messenger Send ทำงานปกติ). ตรวจ token จริงเฉพาะเมื่อ /me ก็ fail ด้วย',
            ]);

            return null;
        }
    }

    /**
     * ดึงโพสล่าสุดของผู้ใช้ (ถ้ามี permission)
     *
     * @param  int  $limit  จำนวนโพสที่ต้องการ
     */
    public function getUserPosts(string $facebookUserId, int $limit = 3): ?array
    {
        try {
            $response = Http::timeout(15)
                ->get($this->graphUrl("/{$facebookUserId}/posts"), [
                    'fields' => 'message,story,created_time',
                    'limit' => $limit,
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            $data = $response->json();

            return $data['data'] ?? [];
        } catch (Exception $e) {
            Log::warning('ไม่สามารถดึงโพสของผู้ใช้ได้: '.$e->getMessage());

            return null;
        }
    }

    /**
     * ดึงรูปภาพจาก Messenger attachment
     *
     * @param  array  $attachments  Facebook message attachments array
     * @return string|null URL ของรูปภาพ
     */
    public function extractImageFromAttachments(array $attachments): ?string
    {
        foreach ($attachments as $attachment) {
            if (($attachment['type'] ?? '') === 'image') {
                return $attachment['payload']['url'] ?? null;
            }
        }

        return null;
    }

    // ============================================================
    // การแยกประเภทคำขอและข้อความ
    // ============================================================

    /**
     * ตรวจสอบว่าเป็นคำขอดูดวงเชิงลึกหรือไม่
     *
     * รูปแบบ: "ดูดวงละเอียด", "ดูดวงเชิงลึก", "ดูดวงแบบละเอียด", "ดูดวงdeep"
     */
    public function isDeepReadingRequest(string $message): bool
    {
        $trimmed = trim($message);

        return (bool) preg_match('/^ดูดวง(ละเอียด|เชิงลึก|แบบละเอียด|deep)/u', $trimmed);
    }

    /**
     * แยกคำถามจากข้อความ
     *
     * รูปแบบ: "ดูดวง เรื่องความรัก เรื่องการเงิน เรื่องสุขภาพ"
     * หรือ: "ดูดวงละเอียด เรื่องความรัก, เรื่องการเงิน"
     */
    public function parseQuestions(string $message): ?array
    {
        // ตรวจสอบว่าข้อความขึ้นต้นด้วย "ดูดวง" หรือไม่
        if (! preg_match('/^ดูดวง/u', trim($message))) {
            return null;
        }

        // ลบคำว่า "ดูดวง" พร้อมคำขยาย
        $text = preg_replace('/^ดูดวง(ละเอียด|เชิงลึก|แบบละเอียด|deep)?\s*/u', '', trim($message));

        // ถ้าไม่มีคำถาม ใช้คำถามเริ่มต้น
        $text = trim($text);
        if (empty($text)) {
            return ['ดูดวงทั่วไป ความรัก การเงิน การงาน สุขภาพ'];
        }

        // แยกคำถามตามเครื่องหมาย หรือ ขึ้นบรรทัดใหม่
        $questions = preg_split('/[\n,]/', $text);

        // กรองคำถามที่ว่าง
        $questions = array_filter(array_map('trim', $questions));

        // จำกัดไม่เกิน 5 คำถาม
        if (count($questions) > 5) {
            $questions = array_slice($questions, 0, 5);
        }

        return ! empty($questions) ? array_values($questions) : null;
    }

    /**
     * แยกวันเกิดจากข้อความผู้ใช้
     *
     * รองรับรูปแบบ:
     * - "เกิด 15 มกราคม 2540" / "เกิดวันที่ 15/01/2540"
     * - "วันเกิด 15-01-1997" / "วันเกิด 15 ม.ค. 40"
     * - "เกิด 15/1/40" / "เกิด 15/1/97"
     *
     * @param  string  $message  ข้อความจากผู้ใช้
     * @return string|null วันเกิดในรูปแบบ Y-m-d (ค.ศ.) หรือ null ถ้าไม่พบ
     */
    public function parseBirthDate(string $message): ?string
    {
        $thaiMonths = [
            'มกราคม' => 1, 'ม.ค.' => 1, 'มค' => 1,
            'กุมภาพันธ์' => 2, 'ก.พ.' => 2, 'กพ' => 2,
            'มีนาคม' => 3, 'มี.ค.' => 3, 'มีค' => 3,
            'เมษายน' => 4, 'เม.ย.' => 4, 'เมย' => 4,
            'พฤษภาคม' => 5, 'พ.ค.' => 5, 'พค' => 5,
            'มิถุนายน' => 6, 'มิ.ย.' => 6, 'มิย' => 6,
            'กรกฎาคม' => 7, 'ก.ค.' => 7, 'กค' => 7,
            'สิงหาคม' => 8, 'ส.ค.' => 8, 'สค' => 8,
            'กันยายน' => 9, 'ก.ย.' => 9, 'กย' => 9,
            'ตุลาคม' => 10, 'ต.ค.' => 10, 'ตค' => 10,
            'พฤศจิกายน' => 11, 'พ.ย.' => 11, 'พย' => 11,
            'ธันวาคม' => 12, 'ธ.ค.' => 12, 'ธค' => 12,
        ];

        // รูปแบบ: เกิด 15 มกราคม 2540
        $monthNames = implode('|', array_keys($thaiMonths));
        if (preg_match('/(?:เกิด|วันเกิด)(?:วันที่)?\s*(\d{1,2})\s*('.$monthNames.')\s*(\d{2,4})/u', $message, $matches)) {
            $day = (int) $matches[1];
            $month = $thaiMonths[$matches[2]] ?? null;
            $year = (int) $matches[3];

            if ($month) {
                return $this->normalizeBirthDate($day, $month, $year);
            }
        }

        // รูปแบบ: เกิด 15/01/2540 หรือ 15-01-2540
        if (preg_match('/(?:เกิด|วันเกิด)(?:วันที่)?\s*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/u', $message, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            return $this->normalizeBirthDate($day, $month, $year);
        }

        return null;
    }

    /**
     * แปลงวัน/เดือน/ปี เป็นรูปแบบ Y-m-d (ค.ศ.)
     *
     * @param  int  $day  วัน
     * @param  int  $month  เดือน (1-12)
     * @param  int  $year  ปี (พ.ศ. หรือ ค.ศ. แบบ 2 หรือ 4 หลัก)
     */
    protected function normalizeBirthDate(int $day, int $month, int $year): ?string
    {
        // 🇹🇭 (2026-08-02) ใช้ตัวกลางตัวเดียวกับ FortuneConversationService — พ.ศ. เป็นหลัก
        //   เดิมสำเนาของที่นี่ไม่มีด่านอายุขั้นต่ำ → "68" กลายเป็น พ.ศ.2568 = ทารก 1 ขวบ
        //   และ fallback ค.ศ. ใช้เกณฑ์ (> 50) คนละแบบกับอีกเส้น = ตอบไม่ตรงกัน
        $normalized = \App\Support\ThaiBirthYear::normalize($year);

        if ($normalized === null) {
            return null;
        }

        $year = $normalized;

        // ตรวจสอบความถูกต้อง
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $year < 1900 || $year > now()->year) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // ============================================================
    // ตรวจสอบ Free Limit (Freemium)
    // ============================================================

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรี (พื้นฐาน) หรือยัง
     */
    public function checkFreeLimit(string $facebookUserId): array
    {
        $maxFree = $this->settings->max_free_readings;
        $todayCount = FortuneReading::countTodayReadings($facebookUserId);
        $remaining = max(0, $maxFree - $todayCount);

        return [
            'has_reached_limit' => $todayCount >= $maxFree,
            'today_count' => $todayCount,
            'max_free' => $maxFree,
            'remaining' => $remaining,
        ];
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรี (เชิงลึก) หรือยัง
     *
     * ใช้ reading_type = 'deep' แทนการเดาจาก tokens_used
     */
    public function checkDeepFreeLimit(string $facebookUserId): array
    {
        $maxFreeDeep = $this->settings->free_deep_per_day ?? 1;
        $todayDeepCount = FortuneReading::countTodayDeepReadings($facebookUserId);
        $remaining = max(0, $maxFreeDeep - $todayDeepCount);

        return [
            'has_reached_limit' => $todayDeepCount >= $maxFreeDeep,
            'today_count' => $todayDeepCount,
            'max_free' => $maxFreeDeep,
            'remaining' => $remaining,
        ];
    }

    // ============================================================
    // ข้อความอัตโนมัติ
    // ============================================================

    /**
     * สร้างข้อความเมื่อครบจำนวนฟรี (พื้นฐาน)
     *
     * ใช้เทมเพลต limit_exceeded ถ้ามี มิฉะนั้นใช้ข้อความจาก settings
     *
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function getLimitExceededMessage(?string $userName = null): string
    {
        // ลองใช้เทมเพลตก่อน
        $template = FortuneResponseTemplate::getDefault('limit_exceeded');
        if ($template) {
            return $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'max_free' => (string) ($this->settings->max_free_readings ?? 3),
                'remaining_free' => '0',
                'price' => (string) ($this->settings->reading_price ?? 0),
                'register_url' => url('/register'),
            ]);
        }

        // Fallback: ข้อความจาก settings
        $message = $this->settings->limit_exceeded_message ??
            "คุณได้ใช้งานครบจำนวนฟรีวันนี้แล้ว ({$this->settings->max_free_readings} ครั้ง)\n\n";

        if ($this->settings->reading_price > 0) {
            $message .= "💰 ราคาการทำนายต่อครั้ง: {$this->settings->reading_price} บาท\n\n";
        }

        // แนะนำดูดวงเชิงลึก (ถ้าเปิดใช้งาน)
        if ($this->settings->isDeepReadingEnabled()) {
            $message .= "🌟 หรือลอง 'ดูดวง' เพื่อรับคำทำนายเชิงลึก\n";
        }

        if ($this->settings->payment_qr_image) {
            $message .= "\n📸 โอนเงินผ่าน QR Code:\n";
            $message .= $this->settings->getPaymentQrUrl()."\n";
        }

        $message .= "\n📱 สมัครสมาชิกเพื่อใช้งานไม่จำกัด: ".url('/register');

        return $message;
    }

    /**
     * สร้างข้อความเมื่อครบจำนวนฟรีเชิงลึก
     *
     * ใช้เทมเพลต limit_exceeded ถ้ามี มิฉะนั้นใช้ข้อความ hardcoded
     *
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function getDeepLimitExceededMessage(?string $userName = null): string
    {
        // ลองใช้เทมเพลตก่อน
        $template = FortuneResponseTemplate::getDefault('limit_exceeded');
        if ($template) {
            return $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'max_free' => (string) ($this->settings->free_deep_per_day ?? 1),
                'remaining_free' => '0',
                'price' => (string) ($this->settings->deep_reading_price ?? 0),
                'register_url' => url('/register'),
            ]);
        }

        // Fallback: ข้อความ hardcoded
        $message = "🌟 คุณได้ใช้สิทธิ์ดูดวงเชิงลึกฟรีวันนี้ครบแล้ว ({$this->settings->free_deep_per_day} ครั้ง)\n\n";

        if ($this->settings->deep_reading_price > 0) {
            $message .= "💰 ดูดวงเชิงลึกเพิ่ม: {$this->settings->deep_reading_price} บาท/ครั้ง\n\n";
        }

        if ($this->settings->isSubscriptionEnabled()) {
            $message .= $this->settings->getSubscriptionMessage();
        } else {
            if ($this->settings->payment_qr_image) {
                $message .= "📸 โอนเงินผ่าน QR Code:\n";
                $message .= $this->settings->getPaymentQrUrl()."\n\n";
            }
            $message .= '📱 ชำระเงิน/สมัครสมาชิก: '.url('/register');
        }

        return $message;
    }

    /**
     * ส่งข้อความต้อนรับจากเทมเพลต
     *
     * @param  string  $recipientId  Facebook User ID
     */
    public function sendWelcomeMessage(string $recipientId): bool
    {
        $template = FortuneResponseTemplate::getDefault('welcome');
        if ($template) {
            $message = $template->render([
                'max_free' => (string) ($this->settings->max_free_readings ?? 3),
            ]);

            // 🌙 (2026-05-23) Deep ปิด → strip "ดูดวงเชิงลึก" lines ที่อาจ hardcode ใน DB template
            //   default seeder body มี 2 บรรทัด: "🌟 พิมพ์: ดูดวงละเอียด..." + "🌟 ดูดวงเชิงลึกฟรีวันละ 1 ครั้ง"
            //   admin custom ได้ — runtime strip ปลอดภัยกว่า migrate DB
            if (! $this->settings->isDeepReadingEnabled()) {
                $message = preg_replace(
                    [
                        '/🌟 พิมพ์[^\n]*ดูดวงละเอียด[^\n]*\n(\s+เช่น:[^\n]*\n)?\n?/u',
                        '/🌟 ดูดวงเชิงลึก[^\n]*\n?/u',
                    ],
                    '',
                    $message
                );
            }

            // ส่งรูปส่วนหัว (ถ้ามี)
            if ($template->hasHeaderImage()) {
                $this->sendImage($recipientId, $template->header_image_url);
            }

            $result = $this->sendMessage($recipientId, $message);

            // ส่งรูปส่วนท้าย (ถ้ามี)
            if ($template->hasFooterImage()) {
                $this->sendImage($recipientId, $template->footer_image_url);
            }

            return $result;
        }

        // Fallback: ข้อความต้อนรับเดิม
        // กระชับเมื่อปิด free — ไม่แนะนำ "ดูดวง" (ซึ่งไม่มี basic flow ให้ใช้)
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        if ($freeEnabled) {
            $welcomeText = "🔮 สวัสดี ยินดีต้อนรับสู่ระบบดูดวง!\n\n"
                ."พิมพ์: \"ดูดวง\" ตามด้วยคำถาม\n"
                .'🌟 หรือกดปุ่ม "ดูดวง" เพื่อเริ่มเลือกแพคเกจ';
        } else {
            $welcomeText = "🔮 สวัสดี ยินดีต้อนรับสู่ระบบดูดวง!\n\n"
                .'พิมพ์ "ดูดวง" เพื่อเริ่มค่ะ 🙏';
        }

        return $this->sendMessage($recipientId, $welcomeText);
    }

    /**
     * ส่งข้อความแจ้งชำระเงินจากเทมเพลต (พร้อมรูป QR Code)
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  string|null  $userName  ชื่อผู้ใช้
     */
    public function sendPaymentMessage(string $recipientId, ?string $userName = null): bool
    {
        $template = FortuneResponseTemplate::getDefault('payment');
        if ($template) {
            $message = $template->render([
                'user_name' => $userName ?? 'ท่าน',
                'price' => (string) ($this->settings->reading_price ?? 0),
                'register_url' => url('/register'),
                'payment_url' => url('/payment'),
            ]);

            // ส่งรูปส่วนหัว (ถ้ามี)
            if ($template->hasHeaderImage()) {
                $this->sendImage($recipientId, $template->header_image_url);
            }

            $result = $this->sendMessage($recipientId, $message);

            // ส่งรูปส่วนท้าย เช่น QR Code (ถ้ามี)
            if ($template->hasFooterImage()) {
                $this->sendImage($recipientId, $template->footer_image_url);
            }

            return $result;
        }

        // Fallback: ส่ง QR Code จาก settings
        if ($this->settings->payment_qr_image) {
            $this->sendImage($recipientId, $this->settings->getPaymentQrUrl());
        }

        return $this->sendMessage($recipientId,
            "💰 กรุณาชำระเงินเพื่อใช้งานต่อ\n".
            '📱 สมัครสมาชิก: '.url('/register')
        );
    }

    /**
     * ส่งข้อความเมื่อเกิดข้อผิดพลาดจากเทมเพลต
     *
     * @param  string  $recipientId  Facebook User ID
     */
    public function sendErrorMessage(string $recipientId): bool
    {
        $template = FortuneResponseTemplate::getDefault('error');
        if ($template) {
            return $this->sendMessage($recipientId, $template->render());
        }

        return $this->sendMessage($recipientId,
            "😔 ขออภัย ขณะนี้ระบบมีปัญหา\n".
            "กรุณาลองใหม่อีกครั้งในอีกสักครู่\n".
            'พิมพ์ "ดูดวง" เพื่อลองใหม่'
        );
    }

    // ============================================================
    // ส่งคำทำนายกลับไปยังผู้ใช้
    // ============================================================

    /**
     * ส่งคำทำนายกลับไปยังผู้ใช้ (ใช้เทมเพลต + รูปภาพ)
     *
     * ลำดับการส่ง:
     * 1. รูปส่วนหัว (header_image_url) ถ้ามี
     * 2. รูปจากผู้ใช้ (user_image_url) ถ้ามี
     * 3. ข้อความคำทำนาย (render จากเทมเพลต)
     * 4. รูปคำทำนาย (reading_image_url) ถ้ามี
     * 5. รูปส่วนท้าย (footer_image_url เช่น QR Code) ถ้ามี
     *
     * @param  string  $response  คำทำนายจาก AI
     */
    public function sendFortuneTelling(FortuneReading $reading, string $response): bool
    {
        $recipientId = $reading->facebook_user_id;
        $readingType = $reading->reading_type ?? 'basic';

        // ดึงเทมเพลตตามประเภทคำทำนาย (basic/deep)
        $template = FortuneResponseTemplate::getDefault($readingType);

        // เตรียมข้อมูลสำหรับ placeholders
        $data = [
            'response' => $response,
            'user_name' => $reading->facebook_user_name ?? 'ท่าน',
            'date' => now()->format('d/m/Y'),
            'questions' => $reading->getQuestionsText(),
            'reading_type' => $reading->getReadingTypeLabel(),
            'reading_id' => (string) $reading->id,
            'rate_url' => url("/fortune/{$reading->id}/rate"),
            'register_url' => url('/register'),
            'payment_url' => url('/payment'),
            'remaining_free' => '0',
            'max_free' => (string) ($this->settings->max_free_readings ?? 3),
            'price' => (string) ($reading->isDeep()
                ? ($this->settings->deep_reading_price ?? 0)
                : ($this->settings->reading_price ?? 0)),
            'zodiac' => $reading->birth_date
                ? $this->getZodiacFromDate($reading->birth_date)
                : '',
            'birth_date' => $reading->birth_date
                ? $reading->birth_date->format('d/m/Y')
                : '',
        ];

        // render ข้อความจากเทมเพลต (หรือ fallback ถ้าไม่มีเทมเพลต)
        if ($template) {
            $message = $template->render($data);
        } else {
            $readingTypeLabel = $reading->isDeep() ? '🌟 คำทำนายเชิงลึก' : '🔮 คำทำนาย';
            $message = "{$readingTypeLabel}สำหรับคุณ\n\n{$response}\n\n";
            $message .= "---\n";
            $message .= 'ให้คะแนนความพึงพอใจ: '.url("/fortune/{$reading->id}/rate");
        }

        // 🌙 (2026-05-23) Deep ปิด → strip "ดูดวงเชิงลึก/ละเอียด" CTA ที่ hardcode ใน basic template
        //   default seeder body มี:
        //     '💡 ต้องการคำทำนายเชิงลึกละเอียดกว่านี้?'
        //     'พิมพ์: "ดูดวงละเอียด" เพื่อรับคำทำนายแบบเจาะลึก'
        //     '🌟 ดูดวงละเอียด → พิมพ์ "ดูดวงละเอียด"'
        //   admin custom ได้ — runtime strip ปลอดภัยกว่า migrate DB
        if (! $reading->isDeep() && ! $this->settings->isDeepReadingEnabled()) {
            $message = preg_replace(
                [
                    '/💡 ต้องการคำทำนายเชิงลึก[^\n]*\n/u',
                    '/พิมพ์[^\n]*ดูดวงละเอียด[^\n]*\n?/u',
                    '/🌟 ดูดวงละเอียด[^\n]*\n?/u',
                ],
                '',
                $message
            );
        }

        // 1. ส่งรูปส่วนหัว (header image) ถ้ามี
        if ($template && $template->hasHeaderImage()) {
            $this->sendImage($recipientId, $template->header_image_url);
        }

        // 2. ส่งรูปจากผู้ใช้ (user image) ถ้ามี
        if ($reading->hasUserImage()) {
            $this->sendImage($recipientId, $reading->user_image_url);
        }

        // 3. ส่งข้อความคำทำนาย
        if ($this->settings->respond_in_comment && $reading->facebook_comment_id) {
            $this->replyToComment($reading->facebook_comment_id, $message);
        } else {
            $this->sendMessage($recipientId, $message);
        }

        // 4. ส่งรูปคำทำนาย (reading image) ถ้ามี
        if ($reading->hasReadingImage()) {
            $this->sendImage($recipientId, $reading->reading_image_url);
        }

        // 5. ส่งรูปส่วนท้าย (footer image เช่น QR Code) ถ้ามี
        if ($template && $template->hasFooterImage()) {
            $this->sendImage($recipientId, $template->footer_image_url);
        }

        return true;
    }

    // ============================================================
    // Webhook Security
    // ============================================================

    /**
     * ตรวจสอบ webhook signature จาก Facebook
     *
     * Facebook ส่ง X-Hub-Signature-256 header สำหรับ verify payload
     *
     * @param  string  $payload  Raw request body
     * @param  string  $signature  X-Hub-Signature-256 header value
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = $this->settings->facebook_app_secret;

        if (empty($appSecret) || empty($signature)) {
            // ⚠️ Production: ต้องมี app_secret → ปฏิเสธ request ถ้าไม่มี
            if (app()->environment('production')) {
                Log::error('❌ Webhook signature verification FAILED: missing app_secret or signature ใน production', [
                    'has_app_secret' => ! empty($appSecret),
                    'has_signature' => ! empty($signature),
                ]);

                return false;
            }

            // Dev/Local: อนุญาตผ่านเพื่อความสะดวกในการพัฒนา
            Log::warning('⚠️ Webhook signature verification skipped (dev mode): missing app_secret or signature');

            return true;
        }

        // Facebook ส่ง format: "sha256=xxxxx"
        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $payload, $appSecret);
        $receivedHash = substr($signature, 7); // ตัด "sha256=" ออก

        return hash_equals($expectedHash, $receivedHash);
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    /**
     * แบ่งข้อความยาวเป็นหลาย chunks (Messenger จำกัด 2000 ตัวอักษร)
     *
     * แบ่งที่จุดสิ้นสุดบรรทัดใกล้สุดเพื่อไม่ให้ข้อความขาดกลาง
     *
     * @param  string  $message  ข้อความทั้งหมด
     * @return array chunks ของข้อความ
     */
    public function splitLongMessage(string $message): array
    {
        $maxLength = self::MAX_MESSAGE_LENGTH;

        if (mb_strlen($message) <= $maxLength) {
            return [$message];
        }

        $chunks = [];
        $remaining = $message;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= $maxLength) {
                $chunks[] = $remaining;
                break;
            }

            // หาตำแหน่งขึ้นบรรทัดใหม่ที่ใกล้ที่สุดก่อน limit
            $cutPosition = $maxLength;
            $segment = mb_substr($remaining, 0, $maxLength);

            // หาตำแหน่ง newline สุดท้ายในส่วนที่จะตัด
            $lastNewline = mb_strrpos($segment, "\n");
            if ($lastNewline !== false && $lastNewline > ($maxLength * 0.5)) {
                $cutPosition = $lastNewline + 1;
            }

            $chunks[] = trim(mb_substr($remaining, 0, $cutPosition));
            $remaining = trim(mb_substr($remaining, $cutPosition));
        }

        return $chunks;
    }

    /**
     * คำนวณราศีจากวันเกิด
     *
     * @param  Carbon  $date
     * @return string ชื่อราศี
     */
    protected function getZodiacFromDate($date): string
    {
        $month = $date->month;
        $day = $date->day;

        $signs = [
            ['name' => 'มังกร (Capricorn)', 'end_month' => 1, 'end_day' => 19],
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 2, 'end_day' => 18],
            ['name' => 'มีน (Pisces)', 'end_month' => 3, 'end_day' => 20],
            ['name' => 'เมษ (Aries)', 'end_month' => 4, 'end_day' => 19],
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 5, 'end_day' => 20],
            ['name' => 'เมถุน (Gemini)', 'end_month' => 6, 'end_day' => 20],
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 7, 'end_day' => 22],
            ['name' => 'สิงห์ (Leo)', 'end_month' => 8, 'end_day' => 22],
            ['name' => 'กันย์ (Virgo)', 'end_month' => 9, 'end_day' => 22],
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 10, 'end_day' => 22],
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 11, 'end_day' => 21],
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 12, 'end_day' => 21],
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'มังกร (Capricorn)';
    }

    /**
     * 🚫 (2026-05-30) Block user ออกจาก Facebook Page
     *
     * ใช้ Graph API: POST /{page-id}/blocked?psid={psid}
     * ผล: user ส่ง DM ไม่ได้ + คอมเมนต์/โพสบนเพจไม่ได้ (block ระดับเพจจริง)
     *
     * ⚠️ ต้องมี permission pages_manage_metadata บน page token (ไม่งั้น error 283)
     *
     * @param  string  $psid  Page-Scoped User ID (sender id จาก webhook)
     * @return bool true = block สำเร็จ
     */
    public function blockPageUser(string $psid): bool
    {
        $this->lastFetchError = null;
        $pageId = $this->settings->facebook_page_id ?? null;

        if (empty($pageId) || empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_id หรือ page_token';

            return false;
        }

        try {
            $resp = Http::timeout(20)->post($this->graphUrl("/{$pageId}/blocked"), [
                'psid' => $psid,
                'access_token' => $this->pageAccessToken,
            ]);

            if (! $resp->successful()) {
                $this->lastFetchError = 'HTTP '.$resp->status().' | code='.$resp->json('error.code')
                    .' | '.($resp->json('error.message') ?? 'unknown');
                Log::warning('🚫 blockPageUser ล้มเหลว', [
                    'psid' => $psid,
                    'status' => $resp->status(),
                    'error' => $resp->json('error'),
                ]);

                return false;
            }

            // ผลลัพธ์ที่เป็นไปได้: {"<psid>": true} หรือ {"success": true}
            $json = $resp->json();
            $ok = ($json[$psid] ?? null) !== false; // HTTP 200 = ถือว่าสำเร็จ (บาง format ไม่มี key)

            Log::info('🚫 blockPageUser', ['psid' => $psid, 'ok' => $ok, 'resp' => $json]);

            return $ok;
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();
            Log::warning('🚫 blockPageUser exception: '.$e->getMessage(), ['psid' => $psid]);

            return false;
        }
    }

    /**
     * ✨ (2026-05-30) ปลด block user ออกจาก Facebook Page
     *
     * Graph API: DELETE /{page-id}/blocked?psid={psid}
     *
     * @param  string  $psid  Page-Scoped User ID
     * @return bool true = ปลด block สำเร็จ
     */
    public function unblockPageUser(string $psid): bool
    {
        $this->lastFetchError = null;
        $pageId = $this->settings->facebook_page_id ?? null;

        if (empty($pageId) || empty($this->pageAccessToken)) {
            $this->lastFetchError = 'ไม่พบ facebook_page_id หรือ page_token';

            return false;
        }

        try {
            $resp = Http::timeout(20)->delete($this->graphUrl("/{$pageId}/blocked"), [
                'psid' => $psid,
                'access_token' => $this->pageAccessToken,
            ]);

            if (! $resp->successful()) {
                $this->lastFetchError = 'HTTP '.$resp->status().' | '.($resp->json('error.message') ?? 'unknown');

                return false;
            }

            Log::info('✨ unblockPageUser', ['psid' => $psid, 'resp' => $resp->json()]);

            return true;
        } catch (Exception $e) {
            $this->lastFetchError = 'Exception: '.$e->getMessage();

            return false;
        }
    }

    /**
     * สร้าง Graph API URL
     *
     * @param  string  $path  API path (เช่น /me/messages)
     */
    protected function graphUrl(string $path): string
    {
        $version = self::GRAPH_API_VERSION;

        return "https://graph.facebook.com/{$version}{$path}";
    }

    // ============================================================
    // Messenger Profile Setup
    // ============================================================

    /**
     * ตั้งค่า Messenger Profile (Get Started button และ Persistent Menu)
     *
     * เรียกใช้ครั้งเดียวหลังจากตั้งค่า Facebook Page เสร็จ
     * สามารถเรียกผ่าน admin panel หรือ artisan command
     *
     * @return array ผลลัพธ์การตั้งค่า ['success' => bool, 'message' => string]
     */
    public function setupMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            // ตั้งค่า Get Started + Greeting + Persistent Menu + Ice Breakers
            $response = Http::post($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,

                // Get Started button - ส่ง payload GET_STARTED เมื่อผู้ใช้กด
                'get_started' => [
                    'payload' => 'GET_STARTED',
                ],

                // Greeting text - แสดงก่อนผู้ใช้ส่งข้อความแรก
                'greeting' => [
                    [
                        'locale' => 'default',
                        'text' => "🔮 ยินดีต้อนรับสู่ \"แม่หมอจันทรา\"\n\nหมอดูเชี่ยวชาญโหราศาสตร์ไทย-สากล ทำนายแม่นยำด้วยระบบหลักล้าน 💎\n\nกดปุ่มด้านล่างเพื่อเริ่มได้เลย ✨",
                    ],
                ],

                // ✨ Ice Breakers — 4 คำถามให้ลูกค้าใหม่กดเลือกตอนเปิดแชท
                'ice_breakers' => [
                    [
                        'locale' => 'default',
                        'call_to_actions' => $this->buildIceBreakerActions(),
                    ],
                ],

                // Persistent Menu - เมนูถาวรที่แสดงตลอดเวลา (ปุ่มลัดในแชท)
                // Facebook จำกัดสูงสุด 3 top-level items
                'persistent_menu' => [
                    [
                        'locale' => 'default',
                        'composer_input_disabled' => false, // อนุญาตให้พิมพ์ข้อความได้
                        'call_to_actions' => $this->buildPersistentMenuActions(),
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info('✅ Messenger Profile setup successful', [
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'ตั้งค่า Messenger Profile สำเร็จ (Get Started button + Persistent Menu)',
                    'data' => $response->json(),
                ];
            }

            $error = $response->json('error', []);
            Log::error('❌ Messenger Profile setup failed', [
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'ตั้งค่าไม่สำเร็จ: '.($error['message'] ?? 'Unknown error'),
                'data' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Messenger Profile setup error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * สร้างรายการเมนูสำหรับ Persistent Menu (เมนูถาวรล่างซ้ายของแชท)
     *
     * โครงสร้างตาม spec ของ user (2026-04-27):
     * 1. 📝 สมัครสมาชิก → URL ไป Facebook OAuth ของ thaiprompt
     * 2. 💎 ดูดวงละเอียด → postback เริ่ม deep reading flow
     * 3. 🔍 รู้จักเรา → postback อธิบายศาสตร์ + ติดต่อ xman studio
     *    (มี submenu: ชวนเพื่อน + เพิ่มเพื่อน LINE ถ้ามี)
     *
     * Facebook จำกัด 3 top-level items + nested ได้
     *
     * @return array call_to_actions
     */
    protected function buildPersistentMenuActions(): array
    {
        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');

        // ❌ Note: nested submenu สร้าง "Invalid button type" จาก FB API
        // → ใช้ flat menu 3 items เท่านั้น (FB max 3 top-level)
        // → ปุ่ม "ชวนเพื่อน" / "LINE" ทำได้ผ่าน Ice Breakers แทน
        return [
            // 1️⃣ สมัครสมาชิก → URL ไป FB OAuth
            [
                'type' => 'web_url',
                'title' => '📝 สมัครสมาชิก',
                'url' => $appUrl.'/auth/facebook',
            ],
            // 2️⃣ ดูดวงละเอียด → postback เริ่ม flow
            [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],
            // 3️⃣ รู้จักเรา → postback แสดงข้อมูลศาสตร์ + xman studio + ปุ่มต่างๆ
            [
                'type' => 'postback',
                'title' => '🔍 รู้จักเรา',
                'payload' => 'MENU_ABOUT_US',
            ],
        ];
    }

    /**
     * 🚫 Deprecated nested submenu (เก็บไว้เผื่อ reuse — แต่ไม่ใช้แล้ว)
     */
    protected function _buildPersistentMenuActions_OLD(): array
    {
        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');

        // Submenu ของ "🔍 รู้จักเรา"
        $aboutSubmenu = [
            [
                'type' => 'postback',
                'title' => '✨ ศาสตร์ที่เราใช้',
                'payload' => 'MENU_ABOUT_US',
            ],
            [
                'type' => 'postback',
                'title' => '👥 ชวนเพื่อน รับรายได้',
                'payload' => 'MENU_REFERRAL',
            ],
        ];

        // เพิ่ม LINE ใน submenu ถ้ามีการตั้งค่า
        $basicId = $this->settings->line_bot_basic_id ?? null;
        if (! empty($basicId)) {
            if (! str_starts_with($basicId, '@')) {
                $basicId = '@'.$basicId;
            }
            $aboutSubmenu[] = [
                'type' => 'web_url',
                'title' => '💚 เพิ่มเพื่อน LINE',
                'url' => 'https://line.me/R/ti/p/'.$basicId,
            ];
        }

        return [
            // 1️⃣ สมัครสมาชิก → ไป FB OAuth (auto-login + redirect ไป wallet)
            [
                'type' => 'web_url',
                'title' => '📝 สมัครสมาชิก',
                'url' => $appUrl.'/auth/facebook',
                'webview_height_ratio' => 'full',
            ],

            // 2️⃣ ดูดวงละเอียด → เริ่ม flow
            [
                'type' => 'postback',
                'title' => '💎 ดูดวง',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],

            // 3️⃣ รู้จักเรา (มี submenu)
            [
                'type' => 'nested',
                'title' => '🔍 รู้จักเรา',
                'call_to_actions' => $aboutSubmenu,
            ],
        ];
    }

    /**
     * สร้าง Ice Breakers — 4 คำถามให้ลูกค้าใหม่กดเลือกตอนเปิดแชท
     *
     * ปรากฏก่อนลูกค้าพิมพ์ข้อความแรก ทำให้ลูกค้าใหม่เริ่ม flow ได้ง่าย
     * Facebook รองรับสูงสุด 4 questions
     *
     * @return array call_to_actions สำหรับ ice_breakers
     */
    protected function buildIceBreakerActions(): array
    {
        // ⚠️ Facebook จำกัด 4 ice breakers ต่อ persona — เกินจะ reject ทั้งชุด
        // 🌐 (2026-05-03) ใช้ "MENU_REFERRAL" + ไม่เพิ่ม language picker ตรงนี้
        //    เหตุผล: auto-detect ทำงานเป็น default แล้ว, manual picker ใช้ผ่าน
        //    chat command "Lao"/"ลาว"/"ไทย" หรือ postback LANG_TH/LANG_LO จากที่อื่น
        return [
            [
                'question' => '🔮 อยากดูดวงเชิงลึกแม่นๆ',
                'payload' => 'MENU_DEEP_FORTUNE',
            ],
            [
                'question' => '📝 สมัครสมาชิกเพื่อดูดวง+รับรายได้',
                'payload' => 'ICEBREAKER_REGISTER',
            ],
            [
                'question' => '✨ รู้จักเรา / ศาสตร์ที่ใช้ทำนาย',
                'payload' => 'MENU_ABOUT_US',
            ],
            [
                'question' => '🌐 ภาษา / ພາສາ / Language',
                'payload' => 'LANG_PICKER',
            ],
        ];
    }

    /**
     * ลบ Messenger Profile settings ทั้งหมด
     *
     * @return array ผลลัพธ์การลบ
     */
    public function deleteMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            $response = Http::delete($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,
                'fields' => ['get_started', 'greeting', 'persistent_menu'],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'ลบ Messenger Profile สำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => 'ลบไม่สำเร็จ: '.($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    /**
     * ดึงค่า Messenger Profile ปัจจุบัน
     *
     * @return array ค่า profile ปัจจุบัน
     */
    public function getMessengerProfile(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Page Access Token',
            ];
        }

        try {
            $response = Http::get($this->graphUrl('/me/messenger_profile'), [
                'access_token' => $this->pageAccessToken,
                'fields' => 'get_started,greeting,persistent_menu',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'message' => 'ดึงข้อมูลไม่สำเร็จ: '.($response->json('error.message') ?? 'Unknown error'),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }

    // ============================================================
    // Facebook Messenger Templates: Button, Generic, Quick Replies
    // ============================================================

    /**
     * ส่ง Button Template (ข้อความ + ปุ่ม สูงสุด 3 ปุ่ม)
     *
     * ใช้สำหรับ: upsell, payment, check remaining, welcome
     * ข้อจำกัด Facebook: text สูงสุด 640 chars, สูงสุด 3 ปุ่ม
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $templatePayload  payload จาก FacebookRichMessageService
     * @param  array  $options  ตัวเลือกเพิ่มเติม (messaging_type, from_admin)
     * @return bool สำเร็จหรือไม่
     */
    /**
     * 👁️ ส่งกล่องชวนติดตามเพจ + ปุ่มยืนยัน "ติดตามแล้ว"
     *
     * - เช็ค FortuneUserCredit::shouldPromptFollow() ก่อน → skip ถ้า:
     *   • user ยืนยันติดตามแล้ว (clicked FOLLOW_CONFIRMED postback)
     *   • หรือเพิ่งส่ง prompt ภายใน 7 วันที่ผ่านมา
     * - เรียกได้จากทุกที่ที่ส่ง DM (processMessage, sendTemplateEngagement, AI job)
     * - non-blocking — ถ้า fail ไม่กระทบ flow หลัก
     *
     * @param  string  $recipientId  Facebook PSID
     * @return bool true = ส่งสำเร็จ + mark prompted, false = gated/failed
     */
    public function sendFollowPagePromptToUser(string $recipientId): bool
    {
        // 🚫 (2026-05-08) Hard-disable per user feedback "เอาออกไปก่อน ไม่ต้องปรากฎ"
        //    follow-page prompt + group invite รบกวน UX → ลบทิ้งทั้งสอง
        //    ถ้าจะเปิดอนาคต → comment line ต่อไป
        return false;
        try {
            $credit = FortuneUserCredit::getOrCreate($recipientId, 'facebook');
            // 🔄 (2026-05-02) เปลี่ยนจาก shouldPromptFollow() (7-day cooldown)
            //    → shouldPromptFollowToday() (daily cooldown — ครั้งแรกของวันเท่านั้น)
            //    user request: "ในการทักแชทครั้งแรกของวันนั้น ถ้ายังไม่ติดตาม ให้ปรากฏ"
            if (! $credit->shouldPromptFollowToday()) {
                return false; // ติดตามแล้ว หรือ ส่งวันนี้ไปแล้ว
            }

            $pageId = $this->settings->facebook_page_id ?? null;
            if (empty($pageId)) {
                return false;
            }

            // 🌟 (2026-05-05) Redesign — ลบปุ่ม "ติดตามแล้ว" postback
            //                          + เพิ่มปุ่ม "เข้ากลุ่มแม่หมอจันทรา" + ใช้ tracking URLs
            //   user spec: "ปุ่มติดตามแล้วให้นำออก ให้เพิ่มเข้ากลุ่ม + ลิงก์ขอเข้ากลุ่ม
            //               + การกดปุ่มติดตามต้องเด้งให้กดติดตาม + บันทึกว่ายูสเซ่อร์กดติดตามแล้ว"
            //
            //   Tracking URL pattern: /fortune/track/fb-follow/{psid} → record + 302 redirect
            //   web_url button ไม่ส่ง postback กลับ — ต้องใช้ redirect tracking แทน
            $groupUrl = $this->settings->fortune_group_url
                ?? 'https://www.facebook.com/groups/1539006181120751';

            // 🔒 (2026-05-05 review) Signed URLs — ป้องกัน enumeration spam
            //   ใครๆ จะเรียก /fortune/track/fb-follow/{any_psid} ตรงไม่ได้ (signed middleware reject)
            //   URL จะมี ?signature=... + ?expires=... append อัตโนมัติ
            $trackFollowUrl = URL::signedRoute(
                'fortune.track.fb-follow',
                ['psid' => $recipientId],
                now()->addDays(30)  // ลิงก์ใช้ได้ 30 วัน — กัน user ที่เก็บ DM ไว้เปิดทีหลัง
            );
            $trackGroupUrl = URL::signedRoute(
                'fortune.track.fb-group',
                ['psid' => $recipientId],
                now()->addDays(30)
            );

            // 🌙 (2026-05-22) Tone reset — ไม่ประกาศ "สิทธิ์พิเศษ/ของขวัญ"
            //   บอกข้อมูลตรงๆ ว่ามีกลุ่ม + มีดวงประจำวัน ลูกค้าตัดสินใจเอง
            $message = "🌙 ติดตามเพจหรือเข้ากลุ่มแม่หมอจันทรา\n\n"
                ."👁️ *ติดตามเพจ* — ดูดวงประจำวันได้ตอนตี 1 - 7 โมงเช้า\n\n"
                ."👥 *กลุ่มแม่หมอจันทรา* — สายมูทำดวงด้วยกัน:\n"
                ."💬 ปรึกษาแม่หมอ + คุยกับสมาชิกในกลุ่ม\n"
                ."🃏 อัพเดต tarot tip + เคล็ดโหราศาสตร์\n"
                ."🌟 บอกบุญถึงกัน\n\n"
                .'👇 กดเลือกได้เลยค่ะ';

            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => mb_substr($message, 0, 640),
                        'buttons' => [
                            ['type' => 'web_url', 'title' => '👁️ ติดตามเพจ', 'url' => $trackFollowUrl],
                            ['type' => 'web_url', 'title' => '👥 เข้ากลุ่มแม่หมอ', 'url' => $trackGroupUrl],
                        ],
                    ],
                ],
            ];

            $sent = $this->sendButtonTemplate($recipientId, $payload);
            if ($sent) {
                $credit->markFollowPrompted();
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            Log::debug('sendFollowPagePromptToUser failed (non-blocking)', [
                'user_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🌟 (2026-05-04) ส่งกล่องเชิญเข้ากลุ่ม Facebook
     *
     * - Gated โดย settings.fortune_group_invite_enabled + fortune_group_url
     * - Cache cooldown 7 วัน/user (กันสแปม) — key: fb:group_invite_sent:{psid}
     * - non-blocking — ถ้า fail ไม่กระทบ flow หลัก
     *
     * @param  string  $recipientId  Facebook PSID
     * @return bool true = ส่งสำเร็จ + mark cooldown, false = gated/failed
     */
    public function sendGroupInvitePrompt(string $recipientId): bool
    {
        try {
            // 🚫 (2026-05-08) Hard-disable per user feedback "ส่งทำไม ไม่มีประโยชน์"
            //   เก็บฟังก์ชันไว้ — ถ้าอยากเปิดอนาคต comment line ต่อไป + เปิด toggle
            return false;

            // ตรวจ toggle + URL
            if (! ($this->settings->fortune_group_invite_enabled ?? false)) {
                return false;
            }

            $groupUrl = $this->settings->fortune_group_url ?? null;
            if (empty($groupUrl)) {
                return false;
            }

            // Cooldown 7 วัน/user — กันส่งซ้ำซ้อนกับ DM อื่นๆ
            $cacheKey = "fb:group_invite_sent:{$recipientId}";
            if (Cache::has($cacheKey)) {
                return false;
            }

            $message = trim($this->settings->fortune_group_invite_message ?? '');
            if (empty($message)) {
                $message = "🌟 อยากดูดวงฟรีทุกเดือนกับแม่หมอจันทรา?\n\n"
                    ."เข้ากลุ่มสมาชิกของเรา จะได้:\n"
                    ."🎁 สิทธิ์ดูไพ่ฟรี 1 ใบทุกเดือน\n"
                    ."🔮 สุ่มดูดวงส่วนตัวกับแม่หมอ\n"
                    ."✨ ความรู้ดีๆ + กิจกรรมพิเศษ\n\n"
                    .'👇 กดเข้ากลุ่มเลย';
            }

            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => mb_substr($message, 0, 640),
                        'buttons' => [
                            [
                                'type' => 'web_url',
                                'title' => '🌟 เข้ากลุ่มแม่หมอ',
                                'url' => $groupUrl,
                            ],
                        ],
                    ],
                ],
            ];

            $sent = $this->sendButtonTemplate($recipientId, $payload);
            if ($sent) {
                // mark cooldown 7 วัน
                Cache::put($cacheKey, now()->toIso8601String(), now()->addDays(7));
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            Log::debug('sendGroupInvitePrompt failed (non-blocking)', [
                'user_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            $messagingType = 'RESPONSE';
            $requestBody = [
                'recipient' => ['id' => $recipientId],
                'message' => $templatePayload,
                'messaging_type' => $messagingType,
                'access_token' => $this->pageAccessToken,
            ];

            // 🆕 (2026-05-03) H8 — รองรับ from_admin เหมือน sendMessage()
            //    ถ้า from_admin (push หลัง payment / admin alert) → ลอง RESPONSE ก่อน
            //    ถ้าเกิน 24hr (subcode 2018278/2018065/2018001) → fallback เป็น MESSAGE_TAG
            //    เดิม: ขาด subcode 2018001 → push หลัง 24hr ของ button template เงียบไม่มี fallback
            $fromAdmin = ! empty($options['from_admin']);

            // เพิ่ม message_tag ถ้าระบุ (สำหรับส่งนอก 24 ชั่วโมง)
            // 🚨 (2026-08-13) เหตุผลเดียวกับ sendQuickReplies:1061 — บังคับแท็กตั้งแต่แรก
            //   = ลูกค้าที่จ่ายแล้วโดนปฏิเสธทันทีโดยไม่ได้ลอง RESPONSE ⇒ ปิดไว้จนกว่าจะมีแท็กที่ใช้ได้
            if (! empty($options['message_tag']) && self::MESSAGE_TAG_USABLE) {
                $requestBody['messaging_type'] = 'MESSAGE_TAG';
                $requestBody['tag'] = $options['message_tag'];
                $messagingType = 'MESSAGE_TAG';
            }

            $response = Http::timeout(30)->post($this->graphUrl('/me/messages'), $requestBody);

            if ($response->successful()) {
                return true;
            }

            // ลอง fallback เป็น MESSAGE_TAG ถ้า 24 ชั่วโมงหมดอายุ
            // 🔒 (2026-05-03) H8 — เพิ่ม subcode 2018001 (มีใน sendMessage แต่ขาดที่นี่)
            //    ทำให้สอดคล้องกับ sendMessage:434 (ซึ่งมีครบทั้ง 3 subcode)
            //
            // 🚨 (2026-08-13) เพิ่ม 1545041 — **นี่คือตัวที่ทำให้ปุ่มของฟรีหลุดกลับไปเป็น quick reply**
            //    หลักฐาน prod (laravel.log 2026-08-13): ปุ่ม "🎁 รับดวงฟรีประจำวัน" ล้มทุกครั้งด้วย
            //      (#551) ไม่สามารถติดต่อบุคคลนี้ได้ในขณะนี้ · error_subcode 1545041
            //    1545041 ไม่อยู่ในลิสต์ → ไม่ retry MESSAGE_TAG → คืน false → sendQuickReplies
            //    ตกกลับไปยิง quick reply = ช่องทางเดียวที่ @Meta AI แทรกแล้ว payload หาย
            //    (คนกลุ่มนี้คือคน react/comment ที่ยังไม่เคยทักเพจ = อยู่นอกกรอบ 24 ชม.)
            //    sendMessage/sendImage/sendAudio นับ 1545041 เป็น "นอกกรอบ" มาตั้งแต่ 2026-05-07 แล้ว
            //    (ดู :422 · :578 · :615 · :870) — มีแต่ template 2 ตัวนี้ที่ตกหล่น
            $error = $response->json('error', []);
            $subcode = $error['error_subcode'] ?? 0;
            $is24hrError = in_array($subcode, [2018278, 2018065, 2018001, 1545041]);

            // ⛔ (2026-08-13) จุดนี้คือตัวที่ยิงตกน้ำมากที่สุดของ service นี้ — 102 ครั้ง/วันบน prod
            //    เมื่อวาน (dab819269) เพิ่ม 1545041 เข้าลิสต์เพื่อให้ "ได้ retry"
            //    แต่พอ retry จริงกลับเจอ subcode 1893061 = แท็กถูกยกเลิก → ล้มทุกครั้ง
            //    = เปลี่ยนจาก "ไม่ retry เลย" เป็น "retry แล้วตายแน่นอน" — ลูกค้าได้เท่าเดิม เสีย call ฟรี
            //    ⇒ ปิด retry ไว้จนกว่าจะมีแท็กที่ใช้ได้จริง (ปลายทางยัง fallback ไป quick reply เหมือนเดิม)
            if (self::MESSAGE_TAG_USABLE && $is24hrError && empty($options['message_tag']) && $messagingType === 'RESPONSE') {
                Log::info('Facebook Button Template: 24hr expired, retry with MESSAGE_TAG', [
                    'recipient' => $recipientId,
                    'subcode' => $subcode,
                    'from_admin' => $fromAdmin,
                ]);
                $requestBody['messaging_type'] = 'MESSAGE_TAG';
                $requestBody['tag'] = self::MESSAGE_TAG_NAME;

                $retry = Http::timeout(30)->post($this->graphUrl('/me/messages'), $requestBody);

                if ($retry->successful()) {
                    return true;
                }

                Log::error('Facebook Button Template MESSAGE_TAG fallback ล้ม', [
                    'recipient' => $recipientId,
                    'retry_error' => $retry->json('error', []),
                ]);

                return false;
            }

            Log::error('Facebook Button Template ส่งไม่สำเร็จ', [
                'recipient' => $recipientId,
                'subcode' => $subcode,
                'error' => $error,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Facebook Button Template exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ส่ง Generic Template (การ์ดพร้อมรูป + ปุ่ม, รองรับ carousel)
     *
     * ใช้สำหรับ: affiliate share, reading result
     * ข้อจำกัด Facebook: สูงสุด 10 cards, title 80 chars, subtitle 80 chars
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $elements  Generic Template elements
     * @param  array  $options  ตัวเลือกเพิ่มเติม
     * @return bool สำเร็จหรือไม่
     */
    public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements,
                    ],
                ],
            ];

            return $this->sendButtonTemplate($recipientId, $payload, $options);
        } catch (Exception $e) {
            Log::error('Facebook Generic Template exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ส่งข้อความพร้อม Quick Replies และ Button Template ในครั้งเดียว
     *
     * @param  string  $recipientId  Facebook User ID
     * @param  array  $templatePayload  payload จาก FacebookRichMessageService
     * @param  array  $quickReplies  Quick Replies array
     * @return bool สำเร็จหรือไม่
     */
    public function sendTemplateWithQuickReplies(string $recipientId, array $templatePayload, array $quickReplies): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            // Facebook ไม่รองรับ Quick Replies กับ Template พร้อมกัน
            // ส่ง Template ก่อน แล้วส่ง Quick Replies แยก
            $sent = $this->sendButtonTemplate($recipientId, $templatePayload);

            if ($sent && ! empty($quickReplies)) {
                usleep(500000); // รอ 500ms
                $this->sendQuickReplies($recipientId, 'เลือกได้เลย 👇', $quickReplies);
            }

            return $sent;
        } catch (Exception $e) {
            Log::error('Facebook Template+QuickReplies exception: '.$e->getMessage());

            return false;
        }
    }

    // ============================================================
    // MessagingPlatformInterface: methods เพิ่มเติม
    // ============================================================

    /**
     * ส่งข้อความแบบ Rich (Facebook ใช้ Generic Template)
     */
    public function sendRichMessage(string $recipientId, array $richContent): bool
    {
        $this->ensurePageContextForRecipient($recipientId);

        try {
            Http::timeout(30)
                ->post($this->graphUrl('/me/messages'), [
                    'recipient' => ['id' => $recipientId],
                    'message' => $richContent,
                    'messaging_type' => 'RESPONSE',
                    'access_token' => $this->pageAccessToken,
                ])->throw();

            return true;
        } catch (Exception $e) {
            Log::error('ส่ง Rich Message ไม่สำเร็จ: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ตรวจสอบว่า webhook event เป็นข้อความหรือไม่
     */
    public function isMessageEvent(array $event): bool
    {
        return isset($event['message']['text']);
    }

    /**
     * ดึงข้อความจาก webhook event
     */
    public function getMessageText(array $event): ?string
    {
        return $event['message']['text'] ?? null;
    }

    /**
     * ดึง User ID จาก webhook event
     */
    public function getUserIdFromEvent(array $event): ?string
    {
        return $event['sender']['id'] ?? null;
    }

    /**
     * ดึงชื่อ platform
     */
    public function getPlatformName(): string
    {
        return 'facebook';
    }

    /**
     * Facebook Messenger รองรับ Rich Message (Generic Template, Button Template)
     */
    public function supportsRichMessage(): bool
    {
        return true;
    }
}
