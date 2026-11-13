<?php

namespace App\Services\Fortune;

use App\Contracts\FortuneMessengerSender;
use App\Models\FortuneWebHandoff;
use App\Services\FacebookRichMessageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 (2026-11-03) FB web-only — ส่งลูกค้าไป "ห้องแม่หมอ" บนเว็บจันทรา (design หัวข้อ 4-5)
 *
 * ใช้กับ FortuneConversationService (ต้องมี $this->settings, $this->currentPlatform,
 * saveConversationMessage()) — จุดเดียวที่ทุกด่านของ FB web-only เรียกเมื่อจะพาลูกค้าไปเว็บ
 * (ด่านขาเข้า T4, handler ปุ่ม WEB_ROOM ใน webhook T6, รูป/สลิประดับ C T7 ฯลฯ)
 *
 * สิ่งที่ sendWebHandoff() ทำ:
 *   1. สร้างแถว fortune_web_handoffs (nonce อยู่ใน token และ path /chat/continue/{nonce})
 *   2. บันทึกข้อความที่ทำให้เกิดการย้าย (role user) + ข้อความที่แม่หมอตอบ (role assistant)
 *      ลง conversation history → แม่หมอบนเว็บอ่านต่อได้ (T17)
 *   3. ประกอบกล่องพาไปห้องแม่หมอ (button template ปุ่ม web_url ปุ่มเดียว)
 *   4. **fail-closed**: สร้างลิงก์ไม่ได้ → ข้อความ "ห้องแม่หมอกำลังจัดเตรียม" + quick reply WEB_ROOM
 *      + แจ้งแอดมิน — **ห้ามคืน null ให้ผู้เรียกตกกลับไปออกบิลบน FB เด็ดขาด**
 *
 * ผลลัพธ์เป็น response array ทรงเดียวกับ processMessage — action = 'transfer_box'
 * (FortuneChannelManager ส่ง fb_template ผ่าน sendButtonTemplate อยู่แล้ว ไม่ต้องเพิ่ม case ใหม่)
 * ถ้าส่ง opts['deliver'] = true จะส่งเองทันทีด้วย sender (สำหรับ webhook controller ที่ไม่ผ่าน ChannelManager)
 * — กรณีนั้นผู้เรียก **ห้าม** ส่ง response นี้เข้า ChannelManager ซ้ำ
 *
 * ⚠️ LINE / Telegram: คืน null (นอกขอบเขต — ผู้เรียกเดินโฟลเดิมของช่องทางนั้น)
 */
trait WebHandoffTrait
{
    /**
     * พาลูกค้า FB ไปห้องแม่หมอ — คืน response array (null เฉพาะเมื่อไม่ใช่ลูกค้า FB)
     *
     * @param  string  $platformUserId  PSID ของลูกค้า
     * @param  string|null  $triggerText  ข้อความที่ทำให้เกิดการย้าย (null/ว่าง = ไม่บันทึก, เช่นกดปุ่ม)
     * @param  array  $opts  ตัวเลือก:
     *                       - kind: WebHandoffCopy::KIND_* (default KIND_READING)
     *                       - source: จุดที่สร้าง เช่น 'web_only_gate' | 'web_room_postback' | 'slip_tier_c' (default 'web_only_gate')
     *                       - topic: love|career|money|health|kunsai|general (default เดาจาก triggerText)
     *                       - name: ชื่อลูกค้าสำหรับ {name}
     *                       - page_id: fortune_page_id (default FortunePageContext::currentId())
     *                       - save_user_message: บันทึก triggerText เป็นข้อความลูกค้าไหม (default true)
     *                       - deliver: ส่งเองทันที (default false = ให้ ChannelManager ส่ง)
     *                       - sender: FortuneMessengerSender ที่จะใช้ส่ง (default FortuneMessengerFactory)
     * @return array|null ['action','message','fb_template','reading'=>null,'web_handoff'=>true,
     *                    'handoff_nonce'=>?string,'fail_closed'=>bool,'sent'=>?bool]
     *
     * @example
     * if ($mode->fbWebOnlyFor($this->currentPlatform, $uid)) {
     *     return $this->sendWebHandoff($uid, $text, ['kind' => WebHandoffCopy::KIND_PRICE]);
     * }
     */
    public function sendWebHandoff(string $platformUserId, ?string $triggerText = null, array $opts = []): ?array
    {
        $platform = FortuneBotMode::resolvePlatform($this->currentPlatform ?? null, $platformUserId);
        if ($platform !== FortuneBotMode::FB_WEB_ONLY_PLATFORM) {
            Log::info('🌙 WebHandoff: ไม่ใช่ลูกค้า FB — ข้าม (LINE/Telegram ไม่เปลี่ยน)', [
                'platform_user_id' => $platformUserId,
                'platform' => $platform,
            ]);

            return null;
        }

        $kind = (string) ($opts['kind'] ?? WebHandoffCopy::KIND_READING);
        $source = mb_substr((string) ($opts['source'] ?? 'web_only_gate'), 0, 60);
        $name = $opts['name'] ?? null;
        $trigger = trim((string) $triggerText);
        $pageId = $this->webHandoffPageId($opts);

        // 1️⃣ แถว handoff — สร้างไม่ได้ก็ยังพาไปได้ (ลิงก์ไป /chat เฉย ๆ ไม่มีความต่อเนื่อง)
        $nonce = null;
        try {
            $handoff = FortuneWebHandoff::issue([
                'platform' => FortuneBotMode::FB_WEB_ONLY_PLATFORM,
                'platform_user_id' => $platformUserId,
                'fortune_page_id' => $pageId,
                'intent_text' => $trigger !== '' ? $this->sanitizeHandoffIntent($trigger) : null,
                // 🔐 (รีวิว) กดปุ่ม/ไม่มีข้อความ = ไม่มีหัวข้อ (null) — เดิมได้ 'general' แล้วห้องเว็บทักว่า
                //    "รับเรื่องที่ลูกทักมาในเพจไว้แล้ว" ทั้งที่ลูกค้าไม่ได้พิมพ์อะไรเลย
                'intent_topic' => $opts['topic'] ?? ($trigger !== '' ? WebHandoffCopy::detectTopic($trigger) : null),
                'source' => $source,
            ]);
            $nonce = $handoff->nonce;
        } catch (\Throwable $e) {
            Log::error('🌙 WebHandoff: สร้างแถว handoff ไม่ได้ — ส่งลิงก์แบบไม่มีความต่อเนื่องแทน', [
                'platform_user_id' => $platformUserId,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }

        // 2️⃣ กล่องพาไปห้องแม่หมอ
        $box = null;
        try {
            $box = (new FacebookRichMessageService($this->settings))
                ->buildWebHandoffBox($platformUserId, $kind, $nonce, $pageId, is_string($name) ? $name : null);
        } catch (\Throwable $e) {
            Log::error('🌙 WebHandoff: ประกอบกล่องไม่สำเร็จ', [
                'platform_user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);
            $box = null;
        }

        $failClosed = ! is_array($box) || empty($box);
        if ($failClosed) {
            // 🔒 fail-closed: ห้ามตกกลับไปออกบิล — บอกลูกค้าให้ลองกดใหม่ + แจ้งแอดมิน
            $text = WebHandoffCopy::FAIL_CLOSED;
            $payload = [
                'text' => $text,
                'quick_replies' => WebHandoffCopy::webRoomQuickReply(),
            ];
            $this->alertWebHandoffFailure($platformUserId, $source);
        } else {
            // การ์ดรูป (generic) ไม่มี text — ใช้หัว — คำโปรยการ์ดเป็นข้อความที่บันทึก
            $text = WebHandoffCopy::textOf($box) ?? WebHandoffCopy::boxText($kind, is_string($name) ? $name : null);
            $payload = $box;
        }

        // 3️⃣ บันทึกบทสนทนา (ข้อความลูกค้า + คำตอบแม่หมอ) — ให้แม่หมอบนเว็บคุยต่อได้
        if ($trigger !== '' && ($opts['save_user_message'] ?? true)) {
            $this->saveConversationMessage($platformUserId, 'user', $trigger, FortuneBotMode::FB_WEB_ONLY_PLATFORM);
        }
        $this->saveConversationMessage($platformUserId, 'assistant', $text, FortuneBotMode::FB_WEB_ONLY_PLATFORM);

        // 4️⃣ ส่งเอง (เฉพาะผู้เรียกที่ไม่ผ่าน ChannelManager)
        $sent = null;
        if (! empty($opts['deliver'])) {
            $sent = $this->deliverWebHandoff($platformUserId, $payload, $opts['sender'] ?? null);
        }

        // 📈 (2026-11-14) ตัวนับ FB → เว็บ ขั้น 1 — นับเฉพาะกล่องที่มีลิงก์จริงและไม่ได้ส่งพลาด
        //    (sent = null คือ ChannelManager เป็นคนส่งต่อ — นับว่าส่ง)
        if (! $failClosed && $sent !== false) {
            WebFunnelTracker::boxSent($platformUserId, $pageId, $source);
        }

        Log::info('🌙 WebHandoff: พาลูกค้าไปห้องแม่หมอ', [
            'platform_user_id' => $platformUserId,
            'source' => $source,
            'kind' => $kind,
            'has_handoff' => $nonce !== null,
            'fail_closed' => $failClosed,
            'sent' => $sent,
        ]);

        return [
            // action ที่ ChannelManager รู้จักอยู่แล้ว → ส่ง fb_template ผ่าน sendButtonTemplate
            'action' => 'transfer_box',
            'message' => $text,
            'fb_template' => $payload,
            'reading' => null,
            'web_handoff' => true,
            'handoff_nonce' => $nonce,
            'fail_closed' => $failClosed,
            'sent' => $sent,
        ];
    }

    /**
     * ส่ง payload ด้วย sender (ปุ่ม template หรือข้อความ fail-closed ที่มี quick reply)
     */
    protected function deliverWebHandoff(string $platformUserId, array $payload, mixed $sender = null): bool
    {
        try {
            if (! $sender instanceof FortuneMessengerSender) {
                $sender = FortuneMessengerFactory::sender(FortuneBotMode::FB_WEB_ONLY_PLATFORM, $platformUserId, $this->settings);
            }
            if ($sender === null) {
                return false;
            }

            // sendButtonTemplate ส่ง payload เป็น message ตรง ๆ → ใช้ได้ทั้งกล่อง template และ {text, quick_replies}
            return (bool) $sender->sendButtonTemplate($platformUserId, $payload);
        } catch (\Throwable $e) {
            Log::error('🌙 WebHandoff: ส่งกล่องไม่สำเร็จ', [
                'platform_user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * fortune_page_id ของสาขาต้นทาง (ค่าบวกเท่านั้น)
     */
    protected function webHandoffPageId(array $opts): ?int
    {
        $pageId = $opts['page_id'] ?? null;
        if ($pageId === null) {
            try {
                $pageId = FortunePageContext::currentId();
            } catch (\Throwable $e) {
                $pageId = null;
            }
        }

        $pageId = is_numeric($pageId) ? (int) $pageId : null;

        return ($pageId !== null && $pageId > 0) ? $pageId : null;
    }

    /**
     * ทำความสะอาดข้อความลูกค้าก่อนเก็บเป็น intent_text (≤300 ตัว — model ตัดซ้ำอีกชั้น)
     *
     * ใช้ WebChatContinuitySanitizer ถ้ามี (T17) ไม่งั้นตัด URL/เลขยาว/จำนวนเงินเอง
     */
    protected function sanitizeHandoffIntent(string $text): ?string
    {
        try {
            if (class_exists(WebChatContinuitySanitizer::class)
                && method_exists(WebChatContinuitySanitizer::class, 'sanitizeInbound')) {
                $clean = WebChatContinuitySanitizer::sanitizeInbound($text, 'user');
            } else {
                $clean = $text;
            }
        } catch (\Throwable $e) {
            $clean = $text;
        }

        // ชั้นสำรอง (ทำซ้ำได้ไม่เสียหาย): URL · เลขยาว 8 หลักขึ้นไป · จำนวนเงิน
        $clean = preg_replace('#(https?://\S+|www\.\S+|m\.me/\S+|line\.me/\S+|lin\.ee/\S+)#iu', '', $clean) ?? '';
        $clean = preg_replace('/\d[\d\s\-]{7,}\d/u', '', $clean) ?? '';
        $clean = preg_replace('/\d[\d,\.]*\s*(บาท|฿)|฿\s*\d[\d,\.]*/u', '', $clean) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        return $clean === '' ? null : mb_substr($clean, 0, FortuneWebHandoff::INTENT_MAX_LENGTH);
    }

    /**
     * แจ้งแอดมินว่าสร้างลิงก์ห้องแม่หมอไม่ได้ — กันรัว 1 ครั้ง/30 นาที (ระบบพังทีเดียวลูกค้าทุกคนโดน)
     */
    protected function alertWebHandoffFailure(string $platformUserId, string $source): void
    {
        Log::critical('🌙 WebHandoff: สร้างลิงก์ห้องแม่หมอไม่ได้ — ส่งข้อความ fail-closed แทน', [
            'platform_user_id' => $platformUserId,
            'source' => $source,
        ]);

        try {
            if (! Cache::add('fbweb:handoff_fail_alert', 1, now()->addMinutes(30))) {
                return;
            }

            app(\App\Services\LineAlertService::class)->alertSystemError(
                'FB web-only: สร้างลิงก์ห้องแม่หมอไม่ได้ — ลูกค้า FB กำลังได้ข้อความ "ห้องกำลังจัดเตรียม"',
                [
                    'source' => $source,
                    'admin_action' => 'เช็ค facebook_web_only / enable_web_fortune_button, APP_KEY และ FortuneWebLinkService',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('🌙 WebHandoff: แจ้งแอดมินไม่สำเร็จ (non-blocking)', ['error' => $e->getMessage()]);
        }
    }
}
