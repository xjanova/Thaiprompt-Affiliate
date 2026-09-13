<?php

namespace App\Http\Controllers;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneRecipient;
use App\Services\FortuneBannerService;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneTakeoverService;
use App\Services\TelegramFortuneService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ✈️ Telegram Fortune Webhook — ช่องทางที่ 3 ของบอทแม่หมอ (2026-09-13)
 *
 * เจ้าของสั่ง: "เราใช้เทเลแกรม ดูดวงได้ไหม ทำแชทสวยๆ ได้ไหม ... เรามาเริ่มเลย"
 *   จ่ายเงิน = QR พร้อมเพย์ + SMS Checker เหมือน FB/LINE · ครบทุกแพคเกจ
 *
 * ## โครง
 *   ข้อความ → ด่านชุดเดียวกับ FB/LINE → FortuneChannelManager::processMessage('telegram', 'tg_…')
 *   → สมองเดียวกัน (FortuneConversationService) → ตัวเรนเดอร์ FB → TelegramFortuneService (ปุ่ม inline)
 *
 * ## ลำดับด่าน (ห้ามสลับ — ทุกข้อมีเคสจริงรองรับฝั่ง FB/LINE)
 *   1. คำทำนายที่จ่ายแล้วรอส่ง → ส่งก่อนทุกอย่าง  ([[feedback_never_interrupt_payment_to_prediction_flow]])
 *   2. แบน · 3. สติกเกอร์รัว · 4. สแปมข้อความ (ข้ามถ้าจ่ายแล้ว)
 *   5. รูป (Celtic vision / สลิป) · 6. ไม่ใช่ข้อความ
 *   7. /aistop (ยกเว้น flow จ่ายเงิน) · 8. ขอคุยกับคน (ยกเว้นระหว่างทำนาย)
 *   9. ข้อความรัว · 10. แบนเนอร์ต้อนรับ · 11. สมองแม่หมอ
 *
 * ## id
 *   ลูกค้า = 'tg_<chat id>' (FortuneRecipient::telegramUserId) — รับเฉพาะแชทส่วนตัว (private)
 *
 * ## ความปลอดภัย
 *   - ตรวจ header X-Telegram-Bot-Api-Secret-Token ด้วย hash_equals ทุก request
 *   - กันซ้ำด้วย update_id (Telegram ส่งซ้ำเมื่อเราตอบไม่ทัน)
 *   - ห้าม log token / URL ไฟล์ (มี token อยู่ในตัว)
 */
class TelegramFortuneWebhookController extends Controller
{
    /**
     * ปุ่ม (payload ทรง FB) → ข้อความที่ส่งเข้าสมองแม่หมอ
     *
     * ก็อปจาก FacebookWebhookController::handleQuickReply() + processPostback() — ตัวแปลงชุดเดียวกัน
     * ⚠️ payload ที่ไม่อยู่ในตารางและไม่มี handler พิเศษ → ส่ง payload เป็นข้อความตรง ๆ (เหมือน FB default)
     *    ยกเว้นทรงรหัส (ตัวใหญ่ล้วน) ที่เราไม่รู้จัก → ใช้ป้ายบนปุ่มแทน (กันส่งรหัสดิบให้ AI ตอบมั่ว)
     */
    protected const PAYLOAD_TEXT = [
        'DEEP_READING_ACCEPT' => 'ดูดวง',
        'DEEP_READING_DECLINE' => 'ไม่ต้องการ',
        'DEEP_READING_NO' => 'ไม่ต้องการ',
        'FORTUNE_LOVE' => 'ดูดวงความรัก เนื้อคู่ คู่ครอง',
        'FORTUNE_WORK' => 'ดูดวงการงาน อาชีพ เลื่อนตำแหน่ง',
        'FORTUNE_MONEY' => 'ดูดวงการเงิน รายได้ การลงทุน',
        'FORTUNE_HEALTH' => 'ดูดวงสุขภาพ สิ่งที่ต้องระวัง',
        'FORTUNE_OVERVIEW' => 'ดูดวงภาพรวมทุกด้าน ความรัก การงาน การเงิน สุขภาพ',
        'VIEW_LAST_READING' => 'ดูคำทำนาย',
        'AFFILIATE_SHARE' => 'แชร์',
        'DAILY_VIP_PACKAGES' => 'ดูดวง',
        'MENU_FORTUNE' => 'ดูดวง',
        'MENU_DEEP_FORTUNE' => 'ดูดวง',
        'MENU_CHECK_REMAINING' => 'เช็คสิทธิ์',
        'MENU_OPEN' => 'เมนู',
        'FREE_CARD_START' => 'ทำนายฟรี',
        'FREE_CARD_DECLINE' => 'ไม่สนใจ',
        'DELIVERY_CONFIRM_YES' => 'รับคำทำนาย',
        'DELIVERY_CONFIRM_NO' => 'ยกเลิก',
        'PAY_LATER_ACK_YES' => 'ใช่',
        'PAY_LATER_ACK_NO' => 'ยกเลิก',
        'PAY_METHOD_QR_THAI' => 'qr ไทย',
        'PAY_METHOD_STRIPE' => 'บัตร',
        'PAY_METHOD_STRIPE_FOREIGN' => 'จ่ายบัตร',
        'CARD_CONFIRM_YES' => 'ยืนยัน จ่ายบัตร',
        'CARD_CONFIRM_NO' => 'ใช้ qr ไทยต่อ',
        'REPORT_PAYMENT' => 'แจ้งชำระเงิน',
        'CANCEL_PAYMENT' => 'ยกเลิก',
        'CANCEL_HELP_TRANSFER' => 'ขอเลขบัญชี',
        'CANCEL_HELP_ADMIN' => 'คุยกับแม่หมอ',
        'CANCEL_CONFIRM_REAL' => 'ยืนยันยกเลิก',
        'TALK_ADMIN' => 'คุยกับแม่หมอ',
        'SHOW_BANK_ACCOUNT' => 'แสดงบัญชี',
        'CANCEL_DEEP' => 'ไม่ต้องการ',
        'CANCEL_FORTUNE' => 'ยกเลิก',
        'CELTIC_READY' => 'พร้อม',
        'CELTIC_RESET' => 'สับใหม่',
        'CELTIC_CONTINUE' => 'ถามต่อ',
        'CELTIC_SUGGQ_1' => '1',
        'CELTIC_SUGGQ_2' => '2',
        'CELTIC_END_ASK' => 'เลิกทำนายและสรุปผล',
        'CELTIC_END_YES' => 'ส่งสรุปเลย',
        'CELTIC_END_NO' => 'ขอคุยต่อ',
        'CELTIC_DONE' => 'เลิกทำนายและสรุปผล',
        'BIRTHDATE_CONFIRM_YES' => 'ใช่',
        'BIRTHDATE_CONFIRM_NO' => 'ไม่ใช่',
        'QUESTION_CONFIRM_YES' => 'ใช่',
        'QUESTION_CONFIRM_NO' => 'ไม่ตรงคำถาม',
        'NEW_FORTUNE' => 'ดูดวง',
        'VIEW_READING' => 'ดูคำทำนาย',
        'READ_PREDICTION' => 'อ่านคำทำนาย',
        'TALK_HUMAN' => 'คุยกับแม่หมอ',
        'START_FORTUNE' => 'ดูดวง',
        'DEEP_FORTUNE' => 'ดูดวง',
        'CHECK_STATUS' => 'เช็คสถานะ',
        'RESTART' => 'เริ่มใหม่',
        'CANCEL' => 'ยกเลิก',
        'DEEP_WITH_BIRTHDATE' => '__DEEP_WITH_CACHED_BIRTHDATE__',
        'FORTUNE_BASIC' => 'ดูดวง',
        'FORTUNE_DEEP' => 'ดูดวง',
        'FOREIGN_CONFIRM_PAY' => 'ยืนยันจ่ายเงินไทยได้',
        'CHECK_REMAINING' => 'เช็คสิทธิ์',
        'CELTIC_PREDICT_NOW' => 'เล่าเรื่องที่ค้างคาใจ',
        'CELTIC_START_Q' => 'เล่าเรื่องที่ค้างคาใจ',
        // ปุ่มชวนย้ายช่องทาง/แอด LINE — ลูกค้า Telegram ไม่ต้องย้าย → พาเข้าเมนูดูดวงแทน
        'TRANSFER_GO' => 'ดูดวง',
        'TRANSFER_GO_LINE' => 'ดูดวง',
        'LINE_ADD_FRIEND' => 'ดูดวง',
        'LINE_INVITE' => 'ดูดวง',
        'INVITE_READ_NOW' => 'ดูดวง',
        // เมนูลัด /menu /help
        'HELP' => 'เมนู',
        'MENU_HELP' => 'เมนู',
        'FORTUNE_FREE' => 'ดูดวง',
        // 🚨 ยืนยันยอดโอนที่ไม่ตรงเป๊ะ (fuzzy) — แปลงเป็นคำชัด ๆ เสมอ (ห้ามส่งรหัสดิบ: 'fuzzy_confirm_no'
        //    เคยถูกตัวจับคำนับเป็น "ใช่" เพราะมีตัว y + คำ confirm → ตัดบิลที่ลูกค้าบอกว่าไม่ใช่)
        'FUZZY_CONFIRM_YES' => 'ใช่',
        'FUZZY_CONFIRM_NO' => 'ไม่ใช่',
    ];

    /**
     * รหัสปุ่มที่ FB มี handler เฉพาะ (ไม่เคยส่งรหัสดิบเข้าสมอง) แต่ Telegram ไม่มี → ใช้ป้ายบนปุ่มแทน
     *
     * @var array<int, string>
     */
    protected const TITLE_PAYLOADS = [
        'MENU_ABOUT_US', 'ICEBREAKER_ABOUT', 'MENU_REFERRAL', 'ICEBREAKER_REFERRAL', 'ICEBREAKER_REGISTER',
        'AFFILIATE_RECRUIT_YES', 'AFFILIATE_RECRUIT_NO', 'INVITE_FREE_WEB', 'FOLLOW_CONFIRMED',
        'TRANSFER_STAY_FB', 'TRANSFER_STAY_FB_CONFIRM', 'FORTUNE_EARN_INFO', 'SHARE_PAGE',
    ];

    /**
     * ปุ่มเลือกแพคเกจที่รู้ราคาแล้ว → ตั้ง force_tier แล้วส่ง 'ดูดวง' (เหมือน FB)
     */
    protected const TIER_PAYLOADS = [
        'TIER_DEEP_39' => 'deep',
        'TIER_CELTIC_99' => 'celtic',
        'TIER_CELTIC_BLACKMAGIC' => 'celtic_blackmagic',
    ];

    protected const DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];

    protected FortuneTellingSetting $settings;

    protected TelegramFortuneService $telegram;

    protected FortuneChannelManager $channelManager;

    protected FortuneTakeoverService $takeoverService;

    protected FortuneBanService $banService;

    protected FortuneConversationService $conversationService;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->telegram = new TelegramFortuneService($this->settings);
    }

    /**
     * สร้างบริการหนัก (สมองแม่หมอ ฯลฯ) — เรียกหลังผ่านด่านค่าลับแล้วเท่านั้น
     * request ปลอมจะได้ 403 โดยไม่ต้องประกอบ FortuneConversationService ทั้งก้อน
     */
    protected function bootServices(): void
    {
        $this->channelManager = new FortuneChannelManager($this->settings);
        $this->takeoverService = app(FortuneTakeoverService::class);
        $this->banService = app(FortuneBanService::class);
        $this->conversationService = new FortuneConversationService($this->settings);
    }

    /**
     * รับ update จาก Telegram
     */
    public function handle(Request $request): Response
    {
        // ปิดอยู่ → ตอบ 200 (ไม่ให้ Telegram ส่งซ้ำ) แต่ไม่ทำอะไร
        if (! $this->settings->isTelegramActive()) {
            return response('telegram disabled', 200);
        }

        // 🔐 ค่าลับใน header ต้องตรงกับที่เราตั้งตอน setWebhook — ไม่ตรง = ไม่ใช่ Telegram
        $expected = '';
        try {
            $expected = (string) $this->settings->telegram_webhook_secret;
        } catch (\Throwable $e) {
            $expected = '';
        }
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            // log นาทีละครั้งต่อ IP — กันคนยิงถล่มจน log บวม (endpoint นี้ไม่มี throttle โดยตั้งใจ)
            if (Cache::add('tg_webhook_reject_log:'.$request->ip(), true, 60)) {
                Log::warning('Telegram Webhook: secret ไม่ตรง — ปฏิเสธ', [
                    'has_secret_configured' => $expected !== '',
                    'has_header' => $given !== '',
                    'ip' => $request->ip(),
                ]);
            }

            return response('forbidden', 403);
        }

        $update = json_decode($request->getContent(), true);
        if (! is_array($update)) {
            return response('OK', 200);
        }

        // 🔁 Telegram ส่ง update เดิมซ้ำเมื่อเราตอบช้า/ล้ม → ประมวลผลครั้งเดียว
        $updateId = (int) ($update['update_id'] ?? 0);
        if ($updateId > 0 && ! Cache::add('tg_update:'.$updateId, true, now()->addMinutes(30))) {
            Log::info('🔁 Telegram webhook: ข้าม update ซ้ำ', ['update_id' => $updateId]);

            return response('OK', 200);
        }

        // 🏬 ระบบสาขา — Telegram มีบอทเดียว ใช้สาขาหลักของช่องทาง (ถ้าตั้งไว้)
        \App\Services\Fortune\FortunePageContext::set(
            \App\Services\Fortune\FortunePageContext::default(FortuneRecipient::PLATFORM_TELEGRAM)
        );

        // ค่าตั้งอาจเปลี่ยนตามสาขา → อ่านใหม่แล้วค่อยประกอบบริการ
        $this->settings = FortuneTellingSetting::getSettings();
        $this->telegram = new TelegramFortuneService($this->settings);
        $this->bootServices();

        // ✅ ตอบ 200 ก่อน แล้วค่อยประมวลผล (AI ใช้เวลาหลายวินาที — กัน Telegram ส่งซ้ำ)
        if (function_exists('fastcgi_finish_request')) {
            response('OK', 200)->send();
            fastcgi_finish_request();

            $this->safeHandleUpdate($update);

            return response('', 200);
        }

        $this->safeHandleUpdate($update);

        return response('OK', 200);
    }

    /**
     * ประมวลผล update หนึ่งก้อน — พังแล้วต้องไม่ทำให้ webhook ตอบ 500 (Telegram จะส่งซ้ำไม่หยุด)
     */
    protected function safeHandleUpdate(array $update): void
    {
        try {
            if (isset($update['message']) && is_array($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query']) && is_array($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            } elseif (isset($update['my_chat_member']) && is_array($update['my_chat_member'])) {
                $this->handleChatMemberUpdate($update['my_chat_member']);
            } else {
                Log::debug('Telegram webhook: update ชนิดที่ไม่ได้ใช้', ['keys' => array_keys($update)]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook: ประมวลผล update ล้มเหลว', [
                'update_id' => $update['update_id'] ?? null,
                'error' => $this->telegram->redact($e->getMessage()),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
        }
    }

    // ============================================================
    // ข้อความ
    // ============================================================

    protected function handleMessage(array $message): void
    {
        // รับเฉพาะแชทส่วนตัว — บอทถูกดึงเข้ากลุ่ม = ไม่ตอบ (กันเผาโควตา AI + ข้อมูลลูกค้าหลุดในกลุ่ม)
        if (($message['chat']['type'] ?? '') !== 'private' || ! empty($message['from']['is_bot'])) {
            return;
        }

        $userId = FortuneRecipient::telegramUserId((string) ($message['chat']['id'] ?? ''));
        if ($userId === '') {
            return;
        }

        $this->telegram->rememberProfile($userId, (array) ($message['from'] ?? []));
        $this->telegram->clearBlocked($userId);

        $text = trim((string) ($message['text'] ?? ''));
        $caption = trim((string) ($message['caption'] ?? ''));
        $messageType = $this->messageTypeOf($message);

        // 🧭 คำสั่ง /start /menu /help — แปลงก่อนเข้าด่าน (เป็น "การกดปุ่ม" ของ Telegram)
        if ($messageType === 'text' && str_starts_with($text, '/')) {
            $text = $this->translateCommand($userId, $text);
            if ($text === null) {
                return;
            }
        }

        $isVipPaid = false;
        try {
            $isVipPaid = $this->conversationService->hasPaidActiveReading($userId);
        } catch (\Throwable $e) {
            // เช็คไม่ได้ = ถือว่ายังไม่จ่าย (ด่านข้างล่างเข้มขึ้นเล็กน้อย ไม่ทำให้ของหาย)
        }

        // 1️⃣ คำทำนายที่จ่ายแล้ว + ยังไม่ส่ง → ส่งก่อนทุกด่าน (สติกเกอร์/รูปก็นับว่า "ทักมา")
        if ($this->deliverPendingPrediction($userId, $messageType === 'text' ? $text : '')) {
            return;
        }

        // 2️⃣ แบน
        if ($this->blockedByBan($userId)) {
            return;
        }

        // 3️⃣ สติกเกอร์/อีโมจิรัว
        $gesture = app(\App\Services\Fortune\GestureFloodGuard::class)->check(
            FortuneRecipient::PLATFORM_TELEGRAM,
            $userId,
            $messageType === 'text' ? $text : '',
            [],
            fn () => $this->telegram->getUserProfile($userId)['name'] ?? null,
            $messageType === 'sticker',
        );
        if ($gesture['action'] !== \App\Services\Fortune\GestureFloodGuard::ACTION_PASS) {
            if (! empty($gesture['message'])) {
                $this->telegram->sendMessage($userId, $gesture['message']);
            }

            Log::info('🎭 Telegram: gesture flood guard → '.$gesture['action'], ['user_id' => $userId]);

            return;
        }

        // 4️⃣ สแปมข้อความ (ลิงก์ภายนอก / ซ้ำ / รัว) — ลูกค้าจ่ายแล้วข้าม
        if (! $isVipPaid) {
            if (app(\App\Services\Fortune\ChatSpamGuard::class)->isSpamming(
                FortuneRecipient::PLATFORM_TELEGRAM,
                $userId,
                $messageType === 'text' ? $text : $caption,
                $messageType
            )) {
                Log::info('🚫 Telegram Fortune: ignore spam message (silenced)', [
                    'user_id' => $userId,
                    'message_type' => $messageType,
                ]);

                return;
            }
        } else {
            Cache::forget('fortune:spam:silenced:telegram:'.$userId);
            Cache::forget('fortune:spam:strikes:telegram:'.$userId);
        }

        // 5️⃣ รูป (photo หรือไฟล์รูป)
        if ($messageType === 'image') {
            $this->handleImage($userId, $message, $isVipPaid);

            // คำบรรยายใต้รูป (เช่น "โอนแล้วค่ะ") = ข้อความอีกก้อน (FB ก็ส่งมาเป็น 2 event)
            if ($caption !== '') {
                $this->processText($userId, $caption);
            }

            return;
        }

        // 6️⃣ ไม่ใช่ข้อความ (สติกเกอร์ / เสียง / วิดีโอ / ไฟล์)
        if ($messageType !== 'text' || $text === '') {
            $this->handleNonText($userId, $messageType);

            return;
        }

        $this->processText($userId, $text);
    }

    /**
     * ชนิดข้อความ: text | image | sticker | voice | video | file | other
     */
    protected function messageTypeOf(array $message): string
    {
        if (isset($message['text'])) {
            return 'text';
        }
        if (! empty($message['photo'])) {
            return 'image';
        }
        if (! empty($message['document']) && str_starts_with((string) ($message['document']['mime_type'] ?? ''), 'image/')) {
            return 'image';
        }
        if (! empty($message['sticker'])) {
            return 'sticker';
        }
        if (! empty($message['voice']) || ! empty($message['audio'])) {
            return 'audio';
        }
        if (! empty($message['video']) || ! empty($message['video_note']) || ! empty($message['animation'])) {
            return 'video';
        }
        if (! empty($message['document'])) {
            return 'file';
        }

        return 'other';
    }

    /**
     * คำสั่ง Telegram → ข้อความที่สมองเข้าใจ (null = จัดการเสร็จแล้ว ไม่ต้องเดินต่อ)
     */
    protected function translateCommand(string $userId, string $text): ?string
    {
        // "/start@ชื่อบอท payload" → ['/start', 'payload']
        [$command, $arg] = array_pad(preg_split('/\s+/u', $text, 2) ?: [$text], 2, '');
        $command = strtolower((string) preg_replace('/@.*$/', '', $command));

        return match ($command) {
            '/start' => $this->handleStart($userId, trim((string) $arg)),
            '/menu' => 'ดูดวง',
            '/help' => 'เมนู',
            // คำสั่งที่ไม่รู้จัก → ตัด "/" แล้วส่งเป็นข้อความ (เช่น "/ดูดวง")
            default => ltrim($text, '/') !== '' ? ltrim($text, '/') : null,
        };
    }

    /**
     * ลูกค้ากด Start (ครั้งแรก หรือกดซ้ำ) — ต้อนรับสั้น ๆ + ปุ่มดูดวง
     *
     * FB กด "เริ่มต้นใช้งาน" = ส่งรูปต้อนรับอย่างเดียว · Telegram ถ้าไม่มีรูปลูกค้าจะเจอความเงียบ
     * ⇒ ส่งคำทักทายสั้น ๆ เสมอ (ไม่เรียก AI — ไม่เปลืองโควตา)
     *
     * @return null เสมอ (จัดการครบในตัว)
     */
    protected function handleStart(string $userId, string $payload): ?string
    {
        // มีคำทำนายจ่ายแล้วรอส่ง → ส่งเลย (กด Start ซ้ำหลังจ่ายเงินก็ต้องได้ของ)
        if ($this->deliverPendingPrediction($userId, '')) {
            return null;
        }

        if ($this->blockedByBan($userId)) {
            return null;
        }

        // กด /start รัว → ทำงานครั้งเดียวต่อ 30 วิ (คำสั่งมาก่อนด่านสแปม จึงต้องกันเอง — รวมทางลิงก์ start=<payload>)
        if (! Cache::add('tg_start:'.$userId, true, 30)) {
            return null;
        }

        // ลิงก์ t.me/<บอท>?start=<payload> ที่พาปุ่ม FB มาด้วย (เช่น MENU_FORTUNE) → ทำเหมือนกดปุ่ม
        //    เฉพาะรหัสที่อยู่ในตารางของเราเท่านั้น (ลิงก์ start ใครก็สร้างได้ — ห้ามส่งรหัสแปลกเข้าสมอง)
        if ($payload !== '') {
            if (isset(self::PAYLOAD_TEXT[$payload]) || isset(self::TIER_PAYLOADS[$payload])) {
                $this->routePayload($userId, $payload, '');

                return null;
            }
        }

        // มี flow ค้างอยู่ (บิล/เปิดไพ่/รอคำทำนาย) → ห้ามยื่นเมนูดูดวงใหม่ทับ — ทักสั้น ๆ ให้คุยต่อจากเดิม
        if (FortuneReading::hasActiveReading(FortuneRecipient::PLATFORM_TELEGRAM, $userId)) {
            $this->telegram->sendMessage($userId, "🌙 ยินดีต้อนรับกลับค่ะ\nเรื่องที่คุยค้างไว้ยังอยู่ครบ พิมพ์ต่อได้เลยนะคะ ✨");

            return null;
        }

        $this->sendWelcomeBannerOnce($userId);

        $profile = $this->telegram->getUserProfile($userId);
        $first = trim((string) ($profile['first_name'] ?? ''));
        $brand = $this->settings->getFortuneBrandName();

        $greeting = '🌙 สวัสดีค่ะ'.($first !== '' ? ' คุณ'.$first : '')."\n"
            .$brand." ยินดีต้อนรับนะคะ\n\n"
            ."อยากรู้เรื่องไหน พิมพ์เล่าให้แม่หมอฟังได้เลยค่ะ\n"
            .'หรือกดปุ่มด้านล่างเพื่อดูแพคเกจดูดวง ✨';

        $this->telegram->sendQuickReplies($userId, $greeting, [
            ['title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
        ]);

        return null;
    }

    // ============================================================
    // ปุ่ม inline
    // ============================================================

    protected function handleCallbackQuery(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $message = (array) ($callback['message'] ?? []);

        // ปิดวงกลมหมุนบนปุ่มของลูกค้าก่อนเสมอ (ไม่งั้นค้าง ~15 วิ ดูเหมือนบอทพัง)
        if ($callbackId !== '') {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        if (($message['chat']['type'] ?? '') !== 'private' || ! empty($callback['from']['is_bot'])) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $userId = FortuneRecipient::telegramUserId($chatId);
        if ($userId === '') {
            return;
        }

        $this->telegram->rememberProfile($userId, (array) ($callback['from'] ?? []));
        $this->telegram->clearBlocked($userId);

        $data = (string) ($callback['data'] ?? '');
        $title = $this->buttonTitleFor($message, $data);

        // 🔐 ปุ่มที่กดต้อง "มีอยู่จริง" บนข้อความนั้น — client ดัดแปลง (MTProto) ยิง callback_data อะไรก็ได้
        //    ถ้าไม่ตรวจ ข้อความใดก็ได้จะวิ่งเข้าสมองแบบ "กดปุ่ม" = ข้ามด่าน /aistop + ด่านสแปม
        //    ผลพลอยได้: กดซ้ำหลังปุ่มถูกถอดแล้ว (double-tap) → ไม่เจอปุ่ม → ไม่ประมวลผลซ้ำ
        if ($title === '') {
            Log::info('Telegram: callback ไม่ตรงกับปุ่มบนข้อความ — ข้าม (กดซ้ำ/ปุ่มถูกถอด/ปลอม)', ['user_id' => $userId]);

            return;
        }

        // ปุ่ม hash ที่ cache หายไป (ข้าม deploy) → ใช้ป้ายบนปุ่มแทน (เหมือน FB ที่ป้ายปุ่มไหลมาเป็นข้อความ)
        $payload = $this->telegram->decodeCallback($data) ?? $title;

        // 1️⃣ คำทำนายจ่ายแล้วรอส่ง → ส่งก่อน
        if ($this->deliverPendingPrediction($userId, '')) {
            return;
        }

        // 2️⃣ แบน (ลูกค้าจ่ายแล้วข้าม — ตรวจใน blockedByBan)
        if ($this->blockedByBan($userId)) {
            return;
        }

        // 3️⃣ กดปุ่มรัว
        if ($this->blockedByNavFlood($userId, $payload)) {
            return;
        }

        // ✅ ผ่านด่านแล้วค่อยจัดการปุ่ม (ถ้าด่านกลืนคลิก ลูกค้ายังมีปุ่มให้กดใหม่)
        //    ชุดปุ่มแบบ quick reply (ชุดล่าสุดที่ TelegramFortuneService จดไว้) = ใช้ได้ครั้งเดียวเหมือน FB
        //    → กดซ้ำเร็ว ๆ (double-tap) นับครั้งเดียว + ถอดปุ่มออกพร้อมโชว์ว่าเลือกอะไร
        //    ปุ่มใน template (เมนูแพคเกจ/บิล) = คงไว้เหมือนปุ่ม template ของ FB
        $messageId = (int) ($message['message_id'] ?? 0);
        $tappedKey = 'tg_kb_tapped:'.$userId.':'.$messageId;

        // ชุด quick reply นี้ถูกกดไปแล้ว (ตัวจดชุดล่าสุดถูกล้างไปแล้ว — ต้องเช็คธงนี้ก่อน ไม่งั้นกดซ้ำหลุดเป็นปุ่ม template)
        if ($messageId > 0 && Cache::has($tappedKey)) {
            return;
        }

        $isQuickReplySet = $messageId > 0 && (int) Cache::get('tg_last_kb:'.$userId, 0) === $messageId;

        if ($isQuickReplySet) {
            if (! Cache::add($tappedKey, true, 300)) {
                return;
            }

            Cache::forget('tg_last_kb:'.$userId);
            $this->markChoice($chatId, $message, $title);
        }

        $this->routePayload($userId, $payload, $title);
    }

    /**
     * ป้ายของปุ่มที่ลูกค้ากด (ดึงจาก keyboard ของข้อความต้นทาง)
     */
    protected function buttonTitleFor(array $message, string $data): string
    {
        foreach ((array) ($message['reply_markup']['inline_keyboard'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                if (($button['callback_data'] ?? null) === $data) {
                    return trim((string) ($button['text'] ?? ''));
                }
            }
        }

        return '';
    }

    /**
     * หลังกดปุ่ม: ถอดปุ่ม callback ของข้อความนั้น (ปุ่มลิงก์คงไว้) + ต่อท้าย "👉 ตัวเลือก"
     *
     * Telegram ไม่แสดงสิ่งที่ลูกค้ากดในแชท (ต่างจาก quick reply ของ FB ที่ขึ้นเป็นฟองของลูกค้า)
     * ⇒ ต่อบรรทัดสั้น ๆ ท้ายข้อความเดิม ให้อ่านย้อนแล้วรู้ว่าเลือกอะไรไป
     */
    protected function markChoice(string $chatId, array $message, string $title): void
    {
        $messageId = (int) ($message['message_id'] ?? 0);
        if ($messageId <= 0) {
            return;
        }

        // เก็บเฉพาะปุ่มลิงก์ (url) — ปุ่มจ่ายเงิน/เปิดเว็บต้องกดได้ต่อ
        $urlRows = [];
        foreach ((array) ($message['reply_markup']['inline_keyboard'] ?? []) as $row) {
            $kept = array_values(array_filter((array) $row, fn ($b) => is_array($b) && ! empty($b['url'])));
            if ($kept !== []) {
                $urlRows[] = $kept;
            }
        }

        $markup = ['inline_keyboard' => $urlRows];
        $original = (string) ($message['text'] ?? '');

        if ($title !== '' && $original !== '') {
            $newText = $original."\n\n👉 ".$title;
            if ($this->telegram->utf16Length($newText) <= TelegramFortuneService::MAX_TEXT_UNITS) {
                $edited = $this->telegram->call('editMessageText', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $newText,
                    'link_preview_options' => ['is_disabled' => true],
                    'reply_markup' => $markup,
                ], [], 10);

                if (! empty($edited['ok'])) {
                    return;
                }
            }
        }

        $this->telegram->call('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => $markup,
        ], [], 10);
    }

    /**
     * ปุ่ม → การกระทำ (ตารางเดียวกับ FB + handler พิเศษ)
     */
    protected function routePayload(string $userId, string $payload, string $title): void
    {
        if (isset(self::TIER_PAYLOADS[$payload])) {
            Cache::put("fortune:force_tier:{$userId}", self::TIER_PAYLOADS[$payload], 30);
            $this->processText($userId, 'ดูดวง', true);

            return;
        }

        if (isset(self::PAYLOAD_TEXT[$payload])) {
            $this->processText($userId, self::PAYLOAD_TEXT[$payload], true);

            return;
        }

        if (preg_match('/^CELTIC_VIEW_Q(\d{1,2})$/', $payload, $m)) {
            $this->sendResult(fn () => $this->conversationService->handleViewCelticQuestion($userId, (int) $m[1]), $userId);

            return;
        }

        if (preg_match('/^DAILY_BDAY_([0-7])$/', $payload, $m)) {
            $this->markDailyPendingSafe($userId);
            $this->processText($userId, 'วัน'.self::DAY_NAMES[(int) $m[1]], true);

            return;
        }

        if (preg_match('/^QUESTION_(LOVE|WORK|MONEY|HEALTH)$/', $payload, $m)) {
            $this->handleCategoryQuestion($userId, strtolower($m[1]));

            return;
        }

        match ($payload) {
            'CELTIC_VIEW_LIST' => $this->sendResult(fn () => $this->conversationService->handleViewCelticList($userId), $userId),
            'DAILY_SHOW_MINE' => $this->handleDailyShowMine($userId),
            'DAILY_FREE_START' => $this->handleDailyShowMine(
                $userId,
                "🌙 ได้เลยค่ะ ดวงประจำวันนี้แม่หมอเปิดให้ฟรี ไม่มีค่าใช้จ่าย\nเจ้าชะตาเกิดวันอะไรคะ กดเลือกด้านล่างได้เลย ✨"
            ),
            'QUESTION_CUSTOM' => $this->telegram->sendMessage($userId, "✍️ พิมพ์คำถามที่อยากรู้มาได้เลยค่ะ\nเล่ารายละเอียดมาได้เต็มที่ แม่หมอจะอ่านให้ตรงเรื่องที่สุดนะคะ 🔮"),
            'VIEW_LATER' => $this->telegram->sendMessage($userId, "✨ ได้เลย! เมื่อพร้อมดูแล้ว พิมพ์ 'ดูคำทำนาย' ได้ทุกเมื่อ 🔮"),
            'SUBSCRIBE' => $this->telegram->sendMessage($userId, (string) $this->settings->getSubscriptionMessage()),
            'INVITE_SNOOZE_7D' => $this->handleInviteSnooze($userId),
            'INVITE_OPTOUT' => $this->handleInviteOptOut($userId),
            // ภาษาลาวถูกปิดถาวร ([[rule_fortune_lao_dead_kill_switch]]) — ปุ่มเก่า = เข้าเมนูปกติ
            'LANG_PICKER', 'LANG_TH', 'LANG_LO', 'GET_STARTED', 'get_started' => $this->handleStart($userId, ''),
            default => $this->processText($userId, $this->payloadAsText($payload, $title), true),
        };
    }

    /**
     * payload ที่ไม่มีในตาราง → ข้อความ — **ส่ง payload ดิบเหมือน FB default** (สมองอ่านรหัสบางตัวแบบดิบ
     * เช่น PAY_METHOD_BACK → 'pay_method_back' · STRIPE_OPEN_CHECKOUT) ถ้าเอาป้ายไปแทน ปุ่มพวกนี้ตาย
     *
     * ยกเว้นรหัสที่ FB มี handler เฉพาะซึ่งฝั่ง Telegram ไม่มี (TITLE_PAYLOADS) → ใช้ป้ายบนปุ่ม
     * เพราะรหัสดิบพวกนั้นสมองไม่รู้จัก (FB ไม่เคยส่งเข้าสมอง) ถ้าส่งไปจะกลายเป็นแชท AI ตอบมั่ว
     */
    protected function payloadAsText(string $payload, string $title): string
    {
        if (in_array($payload, self::TITLE_PAYLOADS, true) && $title !== '') {
            return $title;
        }

        return $payload;
    }

    /**
     * ปุ่มชวน (invite) — "พัก 7 วัน" / "ไม่ต้องส่งอีก" · เหมือน FB handleInviteSnooze/handleInviteOptOut
     */
    protected function handleInviteSnooze(string $userId): void
    {
        \App\Models\FortuneUserCredit::snoozeOutbound($userId, FortuneRecipient::PLATFORM_TELEGRAM, 7);
        $this->telegram->sendMessage($userId, "🔕 รับทราบค่ะ แม่หมอจะพักการทักไปหา 7 วันนะคะ\n\nถ้าช่วงไหนอยากดูดวง ทักมาหาแม่หมอได้เสมอเลยค่ะ ✨");
    }

    protected function handleInviteOptOut(string $userId): void
    {
        \App\Models\FortuneUserCredit::optOutOutbound($userId, FortuneRecipient::PLATFORM_TELEGRAM);
        $this->telegram->sendMessage($userId, "🙏 รับทราบค่ะ แม่หมอจะไม่ทักไปรบกวนอีกนะคะ\n\nแต่ถ้าวันไหนเปลี่ยนใจอยากดูดวง ทักมาได้ตลอดเลยค่ะ แม่หมอยินดีเสมอ 🌙");
    }

    /**
     * เลือกหมวดคำถาม (Deep เก็บทีละข้อ) — เหมือน FB handleCategoryQuestion
     */
    protected function handleCategoryQuestion(string $userId, string $category): void
    {
        try {
            $reading = FortuneReading::where('facebook_user_id', $userId)
                ->where('conversation_status', FortuneReading::STATUS_COLLECTING_QUESTIONS)
                ->latest()
                ->first();

            $existing = $reading ? $reading->getCollectedQuestions() : [];
            $question = $this->conversationService->getQuestionForCategory($category, $existing);

            $this->processText($userId, $question, true);
        } catch (\Throwable $e) {
            Log::warning('Telegram: handleCategoryQuestion ล้ม', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->processText($userId, 'ดูดวง', true);
        }
    }

    /**
     * ปุ่ม "ดูดวงวันนี้เลย" / "รับดวงฟรีประจำวัน" — เหมือน FB handleDailyShowMine
     */
    protected function handleDailyShowMine(string $userId, ?string $askBirthdayMessage = null): void
    {
        try {
            $dayIndex = \App\Models\FortuneUserCredit::findBirthDayIndex($userId, FortuneRecipient::PLATFORM_TELEGRAM);

            if ($dayIndex === null || ! isset(self::DAY_NAMES[$dayIndex])) {
                $this->markDailyPendingSafe($userId);

                $askText = $askBirthdayMessage
                    ?? '🌙 ขอทราบวันเกิดอีกครั้งได้ไหมคะ เดี๋ยวแม่หมอเปิดดวงวันนี้ให้ฟรีเลย';

                if (! empty($this->settings->birth_day_cards_enabled)) {
                    try {
                        $dayCards = app(\App\Services\Fortune\FortuneEntryCardBuilder::class)->facebookDays();
                        if ($dayCards !== null) {
                            $this->telegram->sendMessage($userId, $askText);
                            if ($this->telegram->sendButtonTemplate($userId, $dayCards)) {
                                return;
                            }
                            $askText = 'เลือกวันเกิดของเจ้าชะตาด้านล่างได้เลยค่ะ 👇';
                        }
                    } catch (\Throwable $cardErr) {
                        Log::warning('🃏 Telegram Daily: การ์ด 7 วันเกิดล้ม → ใช้ปุ่มข้อความ', ['error' => $cardErr->getMessage()]);
                    }
                }

                $this->telegram->sendQuickReplies($userId, $askText, FortuneConversationService::dailyBirthdayQuickReplies());

                return;
            }

            $this->markDailyPendingSafe($userId);
            $this->processText($userId, 'วัน'.self::DAY_NAMES[$dayIndex], true);
        } catch (\Throwable $e) {
            Log::warning('🔔 Telegram Daily: ปุ่มดูดวงวันนี้ล้ม', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    protected function markDailyPendingSafe(string $userId): void
    {
        try {
            $this->conversationService->markDailyPending(FortuneRecipient::PLATFORM_TELEGRAM, $userId);
        } catch (\Throwable $e) {
            Log::warning('🌙 Telegram Daily: ตั้งธงไม่สำเร็จ', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * เรียก handler ที่คืน result แล้วส่งผ่าน channel manager (ตัวเรนเดอร์เดียวกับข้อความปกติ)
     */
    protected function sendResult(\Closure $producer, string $userId): void
    {
        try {
            $result = $producer();
            if (is_array($result)) {
                $this->channelManager->sendResponse(FortuneRecipient::PLATFORM_TELEGRAM, $userId, $result);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram: ส่งผลปุ่มไม่สำเร็จ', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    // ============================================================
    // ด่าน
    // ============================================================

    /**
     * คำทำนาย Deep ที่จ่ายแล้ว + เขียนเสร็จ + ยังไม่เคยส่ง → ส่งทันที (ด่านอื่นต้องมาทีหลัง)
     *
     * เหมือน FB pendingDelivery — ลูกค้าส่งอะไรมาก็ตาม (สติกเกอร์/รูป) = ได้คำทำนาย
     */
    protected function deliverPendingPrediction(string $userId, string $text): bool
    {
        try {
            $pending = FortuneReading::where('facebook_user_id', $userId)
                ->where('is_paid', true)
                ->whereNotNull('deep_response')
                ->where('deep_response', '!=', '')
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->latest()
                ->first();

            if (! $pending || $pending->getConversationState('reading_sent_directly', false)) {
                return false;
            }

            Log::info('Telegram: มีคำทำนายจ่ายแล้วรอส่ง → ส่งก่อนทุกด่าน', [
                'user_id' => $userId,
                'reading_id' => $pending->id,
            ]);

            $this->processText($userId, $text !== '' ? $text : 'อ่านคำทำนาย');

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram: เช็คคำทำนายรอส่งล้ม (ปล่อยผ่าน)', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * ลูกค้าถูกแบน → เตือนครั้งแรก (cooldown ตาม FortuneBanService) แล้วเงียบ · จ่ายแล้วไม่โดน
     */
    protected function blockedByBan(string $userId): bool
    {
        try {
            if ($this->conversationService->hasPaidActiveReading($userId)) {
                return false;
            }

            $ban = $this->banService->getActiveBan(FortuneRecipient::PLATFORM_TELEGRAM, $userId);
            if ($ban === null) {
                return false;
            }

            if ($this->banService->shouldNotify($ban)) {
                try {
                    $this->telegram->sendMessage($userId, $this->banService->buildBanReplyMessage($ban));
                    $this->banService->recordNotification($ban);
                } catch (\Throwable $notifyErr) {
                    Log::debug('Telegram ban: แจ้งเตือนไม่สำเร็จ (non-blocking)', ['error' => $notifyErr->getMessage()]);
                }
            }

            Log::info('🚫 Telegram: ignore banned user', [
                'user_id' => $userId,
                'ban_id' => $ban->id,
                'permanent' => $ban->isPermanent(),
            ]);

            return true;
        } catch (\Throwable $e) {
            // ด่านเสริมพังต้องไม่ทำให้ลูกค้าคุยไม่ได้ทั้งระบบ
            Log::warning('Telegram: ด่านแบนล้ม (ปล่อยผ่าน)', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * กดปุ่มรัว (NavFloodGuard — ปิด/เปิดที่หลังบ้าน enable_nav_flood_guard)
     */
    protected function blockedByNavFlood(string $userId, string $payload): bool
    {
        try {
            $result = app(\App\Services\Fortune\NavFloodGuard::class)
                ->check(FortuneRecipient::PLATFORM_TELEGRAM, $userId, $payload);

            if ($result['action'] === \App\Services\Fortune\NavFloodGuard::ACTION_PASS) {
                return false;
            }

            if (! empty($result['message'])) {
                $this->telegram->sendMessage($userId, $result['message']);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram: ด่านกดปุ่มรัวล้ม (ปล่อยผ่าน)', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // ============================================================
    // ส่งเข้าสมองแม่หมอ
    // ============================================================

    /**
     * ข้อความ (พิมพ์เอง หรือแปลงจากปุ่ม) → ด่านชั้นท้าย → FortuneChannelManager
     *
     * @param  bool  $explicit  มาจากการกดปุ่ม — ผ่านด่าน /aistop ได้ (spec "postback bypass เสมอ")
     */
    protected function processText(string $userId, string $text, bool $explicit = false): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        try {
            // 🛑 แอดมิน /aistop → บอทเงียบเฉพาะคุยเล่น (flow จ่ายเงิน/รับคำทำนายผ่านเสมอ)
            if ($this->takeoverService->isActiveByPlatform(FortuneRecipient::PLATFORM_TELEGRAM, $userId)
                && ! $this->takeoverService->shouldBypassTakeover(FortuneRecipient::PLATFORM_TELEGRAM, $userId, $text, $explicit)) {
                Log::info('👨‍💼 Telegram /aistop: บอทข้าม (chitchat)', ['user_id' => $userId]);

                return;
            }

            // 🙋 ขอคุยกับคน → แจ้งแอดมิน (ไม่ takeover อัตโนมัติ) · ห้ามแทรกระหว่างทำนาย
            if (! $explicit) {
                $inPrediction = false;
                try {
                    $inPrediction = $this->conversationService->isInPrediction($userId);
                } catch (\Throwable $e) {
                    // ignore
                }

                if (! $inPrediction && $this->takeoverService->detectCustomerHandoffRequest($text)) {
                    $this->handleCustomerHandoffRequest($userId, $text);

                    return;
                }
            }

            // 🌊 ข้อความรัวผิดปกติ (>10 ใน 10 วิ) — กันบอทสแปม ไม่เรียก AI
            //    หน้าต่างเวลาคงที่ (นับจากช่วง 10 วิ ไม่ต่ออายุทุกข้อความ) — [[rule_chat_debounce_fixed_window]]
            //    ลูกค้าจ่ายแล้วไม่โดน (กดเปิดไพ่ Celtic รัว ๆ แล้วถามทันที = ปกติ) — [[rule_paid_customer_bypass_all_guards]]
            $floodKey = 'telegram_flood:'.$userId.':'.intdiv(time(), 10);
            Cache::add($floodKey, 0, 30);
            $floodCount = (int) Cache::increment($floodKey);
            if ($floodCount > 10 && ! $this->conversationService->hasPaidActiveReading($userId)) {
                Log::warning('Telegram Webhook: flood detected', ['user_id' => $userId, 'count' => $floodCount]);
                if ($floodCount === 11) {
                    $this->telegram->sendMessage($userId, "🌙 แม่หมอกำลังพิมพ์อยู่ค่ะ ✨\n\nพิมพ์ทีละข้อความนะคะ 💫");
                }

                return;
            }

            // 🖼️ รูปต้อนรับ — เฉพาะลูกค้าที่ไม่ได้อยู่กลาง flow (กันแทรกกลางบิล/คำทำนาย)
            if (! $this->hasActiveFlow($userId)) {
                $this->sendWelcomeBannerOnce($userId);
            }

            $this->telegram->sendTypingIndicator($userId);

            $profile = $this->telegram->getUserProfile($userId) ?? ['id' => $userId, 'name' => null];

            $extra = $explicit ? ['is_explicit_action' => true] : [];

            $result = $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_TELEGRAM,
                $userId,
                $text,
                $profile,
                $extra
            );

            Log::info('Telegram Webhook: message processed', [
                'user_id' => $userId,
                'action' => $result['action'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram Webhook: ประมวลผลข้อความล้มเหลว', [
                'user_id' => $userId,
                'error' => $this->telegram->redact($e->getMessage()),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            $this->telegram->sendMessage($userId, 'ขออภัย เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏');
        }
    }

    /**
     * อยู่กลาง flow (บิล/คำทำนาย) หรือจ่ายแล้วรอคำทำนาย — ห้ามแทรกรูปต้อนรับ
     */
    protected function hasActiveFlow(string $userId): bool
    {
        try {
            return FortuneReading::where('facebook_user_id', $userId)
                ->where(function ($q) {
                    $q->whereIn('conversation_status', FortuneReading::ACTIVE_READING_STATUSES)
                        ->orWhere(function ($sub) {
                            $sub->where('is_paid', true)
                                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                ->whereNotNull('deep_response')
                                ->where('deep_response', '!=', '');
                        });
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();
        } catch (\Throwable $e) {
            return true; // เช็คไม่ได้ = ไม่ส่งรูป (ปลอดภัยกว่าแทรกกลางบิล)
        }
    }

    /**
     * รูปต้อนรับ วันละครั้ง/คน (ระบบเดียวกับ FB/LINE — งดถ้าได้รูปสัปดาห์นี้แล้ว)
     */
    protected function sendWelcomeBannerOnce(string $userId): void
    {
        try {
            if (\App\Models\FortuneInviteMessage::shouldSuppressImage($userId, FortuneRecipient::PLATFORM_TELEGRAM)) {
                return;
            }

            $sent = (new FortuneBannerService($this->settings))->sendBannerOnce(
                $userId,
                fn ($url) => $this->telegram->sendImage($userId, $url),
                'welcome',
                24,
                FortuneRecipient::PLATFORM_TELEGRAM
            );

            if ($sent) {
                \App\Models\FortuneUserCredit::markImageSent($userId, FortuneRecipient::PLATFORM_TELEGRAM);
            }
        } catch (\Throwable $e) {
            Log::debug('Telegram: ส่งรูปต้อนรับไม่สำเร็จ (non-blocking)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ลูกค้าพิมพ์ขอคุยกับคน — แจ้งลูกค้า + แจ้งแอดมิน (alert-only เหมือน FB/LINE)
     */
    protected function handleCustomerHandoffRequest(string $userId, string $messageText): void
    {
        $reading = FortuneReading::where('platform', FortuneRecipient::PLATFORM_TELEGRAM)
            ->where('platform_user_id', $userId)
            ->latest()
            ->first();

        if (! $reading) {
            $reading = FortuneReading::create([
                'platform' => FortuneRecipient::PLATFORM_TELEGRAM,
                'platform_user_id' => $userId,
                'facebook_user_id' => $userId, // คอลัมน์เก่าต้องไม่ null
                'reading_type' => 'basic',
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
                'conversation_state' => ['placeholder' => true, 'source' => 'customer_request_alert'],
                'questions' => [],
                'ai_response' => '',
                'ai_provider' => 'none',
            ]);
        }

        try {
            \App\Models\FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => null,
                'action' => \App\Models\FortuneTakeoverLog::ACTION_MESSAGE,
                'reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
                'platform' => FortuneRecipient::PLATFORM_TELEGRAM,
                'message' => mb_substr('🙋 ลูกค้าขอคุยกับคน: '.$messageText, 0, 2000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram Customer Handoff: log ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }

        $this->telegram->sendMessage(
            $userId,
            "🌙 รอแอดมินสักครู่นะคะ\n\nแม่หมอจะแจ้งให้แอดมินมาตอบคุณค่ะ ✨\nระหว่างรอ พิมพ์ถามแม่หมอต่อได้นะคะ 🔮"
        );

        try {
            app(\App\Services\LineAlertService::class)->alertUnusualActivity('🙋 ลูกค้า Telegram ขอคุยกับคน', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'message' => mb_substr($messageText, 0, 200),
                'admin_panel' => url('/admin/takeover/'.$reading->id),
            ]);
        } catch (\Throwable $alertErr) {
            Log::warning('Telegram Customer Handoff: ส่ง alert ไม่สำเร็จ (non-blocking)', ['error' => $alertErr->getMessage()]);
        }

        Log::info('🙋 Telegram Customer Handoff: alert-only', ['user_id' => $userId, 'reading_id' => $reading->id]);
    }

    /**
     * สติกเกอร์ / เสียง / วิดีโอ / ไฟล์ — Celtic กำลังเปิดไพ่ = พากลับไปเปิดไพ่ · อื่น ๆ บอกให้พิมพ์
     */
    protected function handleNonText(string $userId, string $messageType): void
    {
        try {
            $celticActive = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', FortuneReading::CELTIC_ACTIVE_STATUSES)
                ->latest()
                ->first();

            if ($celticActive) {
                $resume = app(\App\Services\CelticCrossService::class)
                    ->buildResumeMessage($celticActive, $messageType === 'sticker' ? 'sticker' : 'generic');
                $this->telegram->sendMessage($userId, (string) ($resume['message'] ?? ''));

                return;
            }
        } catch (\Throwable $e) {
            Log::debug('Telegram: เช็ค Celtic active ล้ม (non-blocking)', ['error' => $e->getMessage()]);
        }

        // สติกเกอร์ = ภาษาแชทปกติ (ด่านสติกเกอร์รัวดูแลแล้ว) → ไม่ต้องสอน
        if ($messageType === 'sticker') {
            return;
        }

        $this->telegram->sendMessage(
            $userId,
            "🙏 ขอบคุณที่ทักมานะคะ\n\nแม่หมอรับเป็นข้อความพิมพ์กับรูปภาพค่ะ\nพิมพ์คำถามที่อยากให้ดูดวงมาได้เลยนะคะ 🔮✨"
        );
    }

    // ============================================================
    // รูป (Celtic vision / สลิปโอนเงิน)
    // ============================================================

    /**
     * รูปจากลูกค้า — ลำดับเดียวกับ LINE: Celtic จ่ายแล้วครบ 10 ใบ → vision · ด่านรูปรัว · classify
     * → ไม่มี flow = ตรวจสลิปย้อนหลัง/เก็บเงียบ · มี flow = Celtic / สลิป
     */
    protected function handleImage(string $userId, array $message, bool $isVipPaid): void
    {
        $fileId = $this->imageFileId($message);
        if ($fileId === null) {
            return;
        }

        // Celtic จ่ายแล้ว + เปิดไพ่ครบ 10 → vision ตรง (ข้ามด่านรูปรัว + classifier)
        $celticPaid = FortuneReading::where(function ($q) use ($userId) {
            $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
        })
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_GENERATING,
            ])
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->latest()
            ->first();

        if ($celticPaid && $celticPaid->getCelticPickedCount() >= 10) {
            $this->handleCelticVisionImage($userId, $fileId, $celticPaid, null);

            return;
        }

        // รูปรัว (ไม่ใช่ลูกค้าจ่ายแล้ว)
        if (! $isVipPaid) {
            try {
                $guard = app(\App\Services\Fortune\ImageSpamGuard::class);
                $check = $guard->check(FortuneRecipient::PLATFORM_TELEGRAM, $userId);
                if ($check['blocked']) {
                    return;
                }
                $record = $guard->record(FortuneRecipient::PLATFORM_TELEGRAM, $userId);
                if ($record['triggered']) {
                    return;
                }
            } catch (\Throwable $e) {
                Log::debug('Telegram: image spam guard ล้ม (non-blocking)', ['error' => $e->getMessage()]);
            }
        }

        $hasActiveFortune = FortuneReading::where(function ($q) use ($userId) {
            $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
        })
            ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
            ->exists();

        $base64 = null;

        // ไม่มี flow → ตรวจสลิปย้อนหลัง (บิลที่ทิ้งไว้/หมดเวลา) หรือเก็บเงียบ 30 นาที — ไม่รัน vision
        if (! $hasActiveFortune) {
            $this->handleImageWithoutActiveFlow($userId, $fileId);

            return;
        }

        $intent = null;
        try {
            $base64 = $this->telegram->downloadFileAsBase64($fileId);
            if ($base64 !== null) {
                $intentResult = app(\App\Services\Fortune\ImageIntentClassifier::class)->classify($base64, 'celtic_active');
                $intent = $intentResult['intent'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::debug('Telegram: classifier ล้ม (fall through)', ['error' => $e->getMessage()]);
        }

        if (in_array($intent, [
            \App\Services\Fortune\ImageIntentClassifier::INTENT_EMOJI_STICKER,
            \App\Services\Fortune\ImageIntentClassifier::INTENT_NONSENSE,
        ], true)) {
            return;
        }

        if ($intent !== \App\Services\Fortune\ImageIntentClassifier::INTENT_PAYMENT_SLIP) {
            $celticVision = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                    FortuneReading::STATUS_CELTIC_GENERATING,
                    FortuneReading::STATUS_CELTIC_PICKING,
                ])
                ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->latest()
                ->first();

            if ($celticVision && $celticVision->getCelticPickedCount() >= 10) {
                $this->handleCelticVisionImage($userId, $fileId, $celticVision, $base64);

                return;
            }

            // จ่ายแล้วแต่ยังเปิดไพ่ไม่ครบ → เก็บรูปเป็นบริบท (intent=null = ไม่รู้ว่าสลิปไหม → ไปทางสลิป)
            if ($intent !== null && $celticVision && $celticVision->is_paid) {
                $this->handleCelticPendingImage($userId, $fileId, $celticVision, $base64);

                return;
            }
        }

        $this->handleSlipImage($userId, $fileId, $base64);
    }

    /**
     * file_id ของรูป — photo เอาขนาดใหญ่สุดที่ไม่เกิน 10MB · หรือไฟล์ที่เป็นรูป
     */
    protected function imageFileId(array $message): ?string
    {
        if (! empty($message['photo']) && is_array($message['photo'])) {
            $best = null;
            foreach ($message['photo'] as $size) {
                if (! is_array($size) || empty($size['file_id'])) {
                    continue;
                }
                if ((int) ($size['file_size'] ?? 0) > 10 * 1024 * 1024) {
                    continue;
                }
                if ($best === null || (int) ($size['width'] ?? 0) * (int) ($size['height'] ?? 0) > (int) ($best['width'] ?? 0) * (int) ($best['height'] ?? 0)) {
                    $best = $size;
                }
            }

            return $best['file_id'] ?? null;
        }

        if (! empty($message['document']['file_id'])
            && str_starts_with((string) ($message['document']['mime_type'] ?? ''), 'image/')) {
            return (string) $message['document']['file_id'];
        }

        return null;
    }

    /**
     * รูปตอนไม่มี flow — ลูกค้าที่ทิ้งบิลไว้แล้วกลับมาส่งสลิป ต้องได้ตรวจ ไม่ใช่ถูกทิ้งเงียบ
     * (เหมือน LINE: ตรวจเฉพาะเมื่อมีร่องรอยการจ่าย · ไม่มีร่องรอย = เก็บเงียบ 30 นาทีรอ "โอนแล้ว")
     */
    protected function handleImageWithoutActiveFlow(string $userId, string $fileId): void
    {
        try {
            $slipOk = new \App\Services\Fortune\SlipOkService($this->settings);
            if (! $slipOk->isEnabled()) {
                return;
            }

            $hasPaymentTrace = Cache::has('fortune:returning_slip_ask:'.$userId)
                || FortuneReading::where('facebook_user_id', $userId)
                    ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->where('created_at', '>=', now()->subDays(3))
                    ->where(function ($q) {
                        $q->where('is_paid', false)
                            ->orWhereNull('celtic_questions_used')
                            ->orWhere('celtic_questions_used', '<=', 0);
                    })
                    ->exists()
                || (($this->settings->slipok_auto_provision ?? true)
                    && $this->conversationService->findAbandonedUnpaidDeepBill($userId) !== null);

            $base64 = $this->telegram->downloadFileAsBase64($fileId);
            if ($base64 === null) {
                return;
            }

            if ($hasPaymentTrace) {
                $ret = $this->conversationService->handleReturningSlipImage(FortuneRecipient::PLATFORM_TELEGRAM, $userId, null, $base64);
                if ($ret !== null) {
                    $this->channelManager->sendResponse(FortuneRecipient::PLATFORM_TELEGRAM, $userId, $ret, ['from_admin' => true]);

                    return;
                }
            }

            if ($this->settings->slipok_auto_provision ?? true) {
                $this->conversationService->capturePendingSlipFromImage(FortuneRecipient::PLATFORM_TELEGRAM, $userId, null, $base64);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram: รูปไม่มี flow ตรวจ/เก็บไม่สำเร็จ (non-blocking)', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Celtic จ่ายแล้วแต่ยังเปิดไพ่ไม่ครบ — เก็บรูปเป็นบริบท แล้วพากลับไปเปิดไพ่
     */
    protected function handleCelticPendingImage(string $userId, string $fileId, FortuneReading $reading, ?string $cachedBase64): void
    {
        $picked = $reading->getCelticPickedCount();
        $remain = max(0, 10 - $picked);
        $summaryLine = '📸 แม่หมอเก็บรูปที่ลูกส่งมาไว้แล้วนะคะ ไม่หายไปไหน';

        try {
            $base64 = $cachedBase64 ?: $this->telegram->downloadFileAsBase64($fileId);
            if ($base64 !== null) {
                $captured = app(\App\Services\CelticCrossService::class)->captureImageAsContext($reading, $base64);

                if (($captured['reason'] ?? null) === 'cap_reached') {
                    return; // รูปเกินเพดาน → เงียบ
                }

                if (empty($captured['captured'])) {
                    $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
                }
            } else {
                $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram: Celtic pending image ล้ม (non-blocking)', ['reading_id' => $reading->id, 'error' => $e->getMessage()]);
            $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
        }

        $this->telegram->sendQuickReplies(
            $userId,
            $summaryLine."\n\n"
                ."🃏 ตอนนี้เปิดไพ่ได้ {$picked}/10 ใบ (เหลืออีก {$remain} ใบ)\n"
                .'ไพ่ครบเมื่อไหร่ แม่หมอจะอ่านรูปนี้ผูกกับไพ่ให้เต็ม ๆ ค่ะ',
            [['title' => '🃏 เปิดไพ่ใบถัดไป', 'payload' => 'CELTIC_READY']]
        );
    }

    /**
     * Celtic จ่ายแล้ว + เปิดไพ่ครบ → ให้แม่หมออ่านรูปผูกกับไพ่ (vision)
     */
    protected function handleCelticVisionImage(string $userId, string $fileId, FortuneReading $reading, ?string $cachedBase64): void
    {
        try {
            $base64 = $cachedBase64 ?: $this->telegram->downloadFileAsBase64($fileId);
            if ($base64 === null) {
                throw new \RuntimeException('ดาวน์โหลดรูปจาก Telegram ไม่ได้');
            }

            $mime = $this->sniffImageMime(base64_decode($base64, true) ?: '');
            $dataUrl = 'data:'.$mime.';base64,'.$base64;

            $this->telegram->sendTypingIndicator($userId);

            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);

            $result = app(\App\Services\CelticCrossService::class)->askQuestionWithImage($reading, $dataUrl);

            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            if (empty($result['success'])) {
                $this->telegram->sendMessage(
                    $userId,
                    $result['message'] ?? "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
                );

                return;
            }

            $reading->refresh();
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
            $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
            $usedQ = (int) ($reading->celtic_questions_used ?? 0);
            $remainingQ = $maxQ > 0 ? max(0, $maxQ - $usedQ) : null;

            $timeHint = $remainingMin !== null
                ? "⏳ เหลือเวลา {$remainingMin} นาที (จาก {$qaWindow})"
                : "⏳ คุยได้ภายใน {$qaWindow} นาที นับจากคำทำนายแรก";
            if ($remainingQ !== null) {
                $timeHint .= "\n❓ เหลือถามได้อีก {$remainingQ} คำถาม (จาก {$maxQ})";
            }

            $sent = $this->telegram->sendQuickReplies(
                $userId,
                $result['response']."\n\n".$timeHint."\n💬 พิมพ์ต่อได้เลย หรือกดปุ่มด้านล่างเมื่อพร้อม ✨",
                [['title' => '📜 เลิกทำนายและสรุปผล', 'payload' => 'CELTIC_END_ASK']]
            );

            // เส้นนี้ส่งเอง → ต้อง mark delivered เอง ไม่งั้น cron redeliver ส่งซ้ำ ([[rule_direct_send_path_must_mark_delivered]])
            if ($sent && ($result['question_record'] ?? null) instanceof \App\Models\FortuneCelticQuestion) {
                try {
                    $result['question_record']->markDelivered();
                } catch (\Throwable $markErr) {
                    Log::warning('Telegram Celtic vision: mark delivered ไม่สำเร็จ', ['reading_id' => $reading->id]);
                }
            }
        } catch (\Throwable $e) {
            try {
                $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
            } catch (\Throwable $stateErr) {
                // ignore
            }

            Log::error('Telegram Celtic vision exception', ['reading_id' => $reading->id, 'error' => $this->telegram->redact($e->getMessage())]);

            $this->telegram->sendMessage(
                $userId,
                "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
            );
        }
    }

    /**
     * รูประหว่างมีบิล/flow — ลำดับเดียวกับ LINE handleSlipImageOnly
     */
    protected function handleSlipImage(string $userId, string $fileId, ?string $cachedBase64): void
    {
        $platform = FortuneRecipient::PLATFORM_TELEGRAM;

        try {
            $active = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', array_merge(
                    FortuneReading::PENDING_PAYMENT_STATUSES,
                    [FortuneReading::STATUS_PAID],
                    FortuneReading::CELTIC_ACTIVE_STATUSES,
                    FortuneReading::DEEP_ACTIVE_STATUSES
                ))
                ->latest()
                ->first();

            if (! $active) {
                $recent = FortuneReading::where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                    ->where(function ($q) {
                        $q->where('paid_at', '>=', now()->subMinutes(30))
                            ->orWhere('updated_at', '>=', now()->subMinutes(30));
                    })
                    ->whereNotNull('bill_reference')
                    ->latest('updated_at')
                    ->first();

                if ($recent && $recent->is_paid) {
                    $active = $recent;
                }
            }

            // Celtic กำลังเปิดไพ่/ถาม → พากลับเข้า flow
            if ($active && in_array($active->conversation_status, FortuneReading::CELTIC_ACTIVE_STATUSES, true)) {
                $resume = app(\App\Services\CelticCrossService::class)->buildResumeMessage($active, 'image');
                $this->telegram->sendMessage($userId, (string) ($resume['message'] ?? ''));

                return;
            }

            // รอจ่าย → พยายามตัดบิลจริง (fuzzy) → เก็บสลิปให้ SlipOK ตรวจ
            if ($active && in_array($active->conversation_status, FortuneReading::PENDING_PAYMENT_STATUSES, true)) {
                try {
                    $fuzzy = $this->conversationService->tryFuzzyMatchForSlip($active);
                    if ($fuzzy !== null) {
                        $this->channelManager->sendResponse($platform, $userId, $fuzzy, ['from_admin' => true]);

                        return;
                    }
                } catch (\Throwable $fuzzyErr) {
                    Log::warning('Telegram: slip fuzzy ล้ม (non-blocking)', ['reading_id' => $active->id, 'error' => $fuzzyErr->getMessage()]);
                }

                $flood = $this->conversationService->slipFloodGate($platform, $userId, $active, 'active_bill');
                if ($flood !== null) {
                    if (! empty($flood['message'])) {
                        $this->telegram->sendMessage($userId, $flood['message']);
                    }

                    return;
                }

                try {
                    $slipSvc = new \App\Services\Fortune\SlipOkService($this->settings);
                    if ($slipSvc->isEnabled() && empty($active->slipok_verified_at)) {
                        $b64 = $cachedBase64 ?: $this->telegram->downloadFileAsBase64($fileId);

                        if (! empty($b64)) {
                            $stored = $this->conversationService->storeIncomingSlipFromBase64($active, $b64);

                            if ($stored === FortuneConversationService::SLIP_STORE_OK) {
                                $this->telegram->sendQuickReplies(
                                    $userId,
                                    "🌙 ได้รับรูปแล้วค่ะ ขอบคุณนะคะ\n\n"
                                        .'⏳ ถ้าเป็นสลิปการโอน ระบบกำลังตรวจสอบให้ — ถ้าโอนเข้าจริง จะตัดบิลและเริ่มดูดวงให้ภายใน 1 นาที ✨',
                                    [['title' => '🔄 เช็คสถานะ', 'payload' => 'CHECK_STATUS']]
                                );

                                return;
                            }

                            if ($stored === FortuneConversationService::SLIP_STORE_NOT_SLIP) {
                                $nudge = $this->conversationService->notSlipNudgeMessage($platform, $userId);
                                if ($nudge !== null) {
                                    $this->telegram->sendMessage($userId, $nudge);
                                }

                                return;
                            }

                            if ($stored === FortuneConversationService::SLIP_STORE_HOLD) {
                                $hold = $this->conversationService->partialHoldResponse($userId);
                                if ($hold !== null && ! empty($hold['message'])) {
                                    $this->telegram->sendMessage($userId, $hold['message']);
                                }

                                return;
                            }
                        }
                    }
                } catch (\Throwable $slipErr) {
                    Log::warning('Telegram: SlipOK store ล้ม (non-blocking)', ['user_id' => $userId, 'error' => $slipErr->getMessage()]);
                }

                $billRef = $active->bill_reference ?? '-';
                $this->telegram->sendQuickReplies(
                    $userId,
                    "🌙 ขอบคุณค่ะที่ส่งสลิปมาให้แม่หมอ\n\n"
                        ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."💡 ระบบตรวจยอดโอนอัตโนมัติ — ไม่ต้องส่งสลิปให้แอดมินดูค่ะ\n"
                        ."กดปุ่มด้านล่างหรือพิมพ์ \"โอนแล้ว\" เพื่อให้ระบบเช็คเร็วขึ้น\n"
                        .'ระบบจะตัดบิลภายใน 1-3 นาทีค่ะ ✨',
                    [['title' => '✅ แจ้งชำระเงิน', 'payload' => 'REPORT_PAYMENT']]
                );

                return;
            }

            // จ่ายแล้ว (ไม่ใช่ Celtic) → บอกขั้นตอนจริงที่รออยู่
            if ($active && $active->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS
                && ($active->conversation_status === FortuneReading::STATUS_PAID
                    || ($active->is_paid && empty($active->deep_response)))) {
                $billRef = $active->bill_reference ?? '-';

                if ($active->conversation_status === FortuneReading::STATUS_COLLECTING_BIRTHDATE || empty($active->birth_date)) {
                    $msg = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ (ไม่ต้องส่งสลิปซ้ำนะคะ)\n\n📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🪄 ตอนนี้แม่หมอรอ วันเดือนปีเกิด ของเจ้าชะตาอยู่ค่ะ\nพิมพ์บอกได้เลย เช่น 15 มีนาคม 2538 หรือ 15/3/2538";
                } elseif ($active->conversation_status === FortuneReading::STATUS_COLLECTING_TAROT) {
                    $msg = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ (ไม่ต้องส่งสลิปซ้ำนะคะ)\n\n📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🧘 ตั้งจิตนึกถึงเรื่องที่อยากรู้ แล้วพิมพ์ \"พร้อม\"\n🃏 แม่หมอจะเปิดไพ่อ่านพื้นดวงให้ทันทีค่ะ";
                } else {
                    $msg = "✅ ระบบรับเงินไปเรียบร้อยแล้วค่ะ\n\n📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🌙 แม่หมอกำลังคำนวณดวงดาวให้เจ้าชะตาอยู่\nใช้เวลาประมาณ 1-3 นาที — คำทำนายจะส่งไปให้ทันทีเมื่อเสร็จ ✨\n\n"
                        .'💡 ห้ามสร้างบิลใหม่นะคะ (ป้องกันจ่ายซ้ำ)';
                }

                $this->telegram->sendMessage($userId, $msg);

                return;
            }

            // Celtic จบแล้ว
            if ($active && $active->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS && $active->is_paid) {
                $billRef = $active->bill_reference ?? '-';
                $this->telegram->sendMessage(
                    $userId,
                    "💖 ขอบคุณค่ะ — ได้รับรูปแล้ว\n\n📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🌟 การดูดวง Celtic Cross ของเจ้าชะตาเสร็จไปแล้ว\n\n"
                        ."💡 อยากอ่านคำทำนายอีกครั้ง พิมพ์ \"ดูคำทำนายล่าสุด\"\n"
                        .'💜 อยากดูใหม่ พิมพ์ "ดูดวง" ได้ตลอดเลยค่ะ ✨'
                );

                return;
            }

            // ไม่มีบิล active แต่เคยพยายามจ่าย → ตรวจ+กู้ (returning)
            try {
                if ((new \App\Services\Fortune\SlipOkService($this->settings))->isEnabled()) {
                    $b64 = $cachedBase64 ?: $this->telegram->downloadFileAsBase64($fileId);
                    if (! empty($b64)) {
                        $ret = $this->conversationService->handleReturningSlipImage($platform, $userId, null, $b64);
                        if ($ret !== null) {
                            $this->channelManager->sendResponse($platform, $userId, $ret, ['from_admin' => true]);

                            return;
                        }
                    }
                }
            } catch (\Throwable $retErr) {
                Log::warning('Telegram: returning slip ล้ม (non-blocking)', ['user_id' => $userId, 'error' => $retErr->getMessage()]);
            }

            $this->telegram->sendMessage(
                $userId,
                "📸 ได้รับรูปภาพแล้วค่ะ\n\n"
                    ."💡 ถ้าเป็นสลิปการโอน — ระบบตรวจยอดอัตโนมัติ ไม่ต้องส่งสลิปให้แอดมินค่ะ\n\n"
                    ."🔮 ถ้าต้องการเริ่มดูดวง พิมพ์ 'ดูดวง' หรือคำถามที่อยากรู้มาได้เลย ✨"
            );
        } catch (\Throwable $e) {
            Log::warning('Telegram: handleSlipImage ล้ม', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->telegram->sendMessage($userId, "📸 ได้รับรูปภาพแล้วค่ะ\n\nพิมพ์คำถามที่อยากให้ดูดวงมาได้เลยนะคะ 🔮✨");
        }
    }

    /**
     * ชนิดรูปจากหัวไฟล์ (Telegram ไม่บอก mime ของ photo) — ไม่รู้จัก = jpeg
     */
    protected function sniffImageMime(string $bytes): string
    {
        return match (true) {
            str_starts_with($bytes, "\x89PNG") => 'image/png',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'image/webp',
            str_starts_with($bytes, 'GIF8') => 'image/gif',
            default => 'image/jpeg',
        };
    }

    // ============================================================
    // สถานะห้องแชท
    // ============================================================

    /**
     * ลูกค้าบล็อก/ปลดบล็อกบอท — จดสถานะไว้ (job จะได้ไม่ยิงซ้ำ)
     */
    protected function handleChatMemberUpdate(array $update): void
    {
        if (($update['chat']['type'] ?? '') !== 'private') {
            return;
        }

        $userId = FortuneRecipient::telegramUserId((string) ($update['chat']['id'] ?? ''));
        if ($userId === '') {
            return;
        }

        $status = (string) ($update['new_chat_member']['status'] ?? '');

        if ($status === 'kicked') {
            Cache::put('tg_blocked:'.$userId, true, now()->addDays(30));
            Log::info('Telegram: ลูกค้าบล็อกบอท', ['user_id' => $userId]);
        } elseif ($status === 'member') {
            $this->telegram->clearBlocked($userId);
            Log::info('Telegram: ลูกค้าปลดบล็อก/เริ่มแชท', ['user_id' => $userId]);
        }
    }
}
