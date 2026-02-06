<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommentEngagement;
use App\Jobs\ProcessFortuneTelling;
use App\Models\FortuneCommentEngagement;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Facebook Webhook Controller
 *
 * จัดการ webhook events จาก Facebook Messenger
 * รองรับการรับคอมเมนต์, Messenger messages, รูปภาพ
 * รองรับระบบ Freemium: คำทำนายพื้นฐาน + เชิงลึก
 *
 * Production features:
 * - Webhook signature verification (X-Hub-Signature-256)
 * - Always return HTTP 200 (Facebook จะ disable webhook ถ้าได้ 500)
 * - Typing indicator ขณะ AI กำลังประมวลผล
 * - รองรับรับรูปภาพจากผู้ใช้และส่งรูปกลับ
 * - Quick replies buttons สำหรับ UX ที่ดี
 * - Message splitting สำหรับข้อความยาว
 */
class FacebookWebhookController extends Controller
{
    protected $facebookService;
    protected $aiService;
    protected $conversationService;
    protected $settings;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->facebookService = new FacebookWebhookService($this->settings);
        $this->aiService = new FortuneAIService($this->settings);
        $this->conversationService = new FortuneConversationService($this->settings);
    }

    /**
     * Verify webhook (GET request จาก Facebook)
     *
     * Facebook จะส่ง GET request เพื่อ verify webhook URL
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $this->settings->facebook_verify_token) {
            Log::info('Facebook Webhook Verified');
            return response($challenge, 200);
        }

        Log::warning('Facebook Webhook Verification Failed', [
            'mode' => $mode,
            'token_match' => $token === $this->settings->facebook_verify_token,
        ]);
        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * รับ webhook events (GET/POST request จาก Facebook)
     *
     * GET: Facebook ส่ง verify request พร้อม hub.mode, hub.verify_token, hub.challenge
     * POST: Facebook ส่ง events (messages, comments, etc.)
     *
     * ⚠️ CRITICAL: POST ต้อง return 200 เสมอ
     * Facebook จะ retry ถ้าได้ non-200 response และจะ disable webhook หลัง retry หลายครั้ง
     */
    public function webhook(Request $request)
    {
        // จัดการ GET verification request จาก Facebook
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        try {
            // 🔍 Debug: Log ทุก webhook request ที่เข้ามา
            Log::info('📥 Facebook Webhook RAW', [
                'method' => $request->method(),
                'has_signature' => !empty($request->header('X-Hub-Signature-256')),
                'content_length' => strlen($request->getContent()),
                'ip' => $request->ip(),
            ]);

            // ตรวจสอบ webhook signature (security)
            $signature = $request->header('X-Hub-Signature-256', '');
            if (!$this->facebookService->verifyWebhookSignature(
                $request->getContent(),
                $signature
            )) {
                Log::warning('Facebook Webhook: Invalid signature', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['status' => 'ok']); // ยังคง return 200
            }

            if (!$this->settings->isServiceEnabled()) {
                Log::info('📥 Webhook: Service disabled, skipping');
                return response()->json(['status' => 'ok']);
            }

            $data = $request->all();
            Log::info('Received Facebook Webhook', [
                'object' => $data['object'] ?? 'unknown',
                'entry_count' => count($data['entry'] ?? []),
            ]);

            if (($data['object'] ?? '') !== 'page') {
                return response()->json(['status' => 'ok']);
            }

            foreach ($data['entry'] ?? [] as $entry) {
                // 🔍 Debug: Log entry details
                $hasChanges = !empty($entry['changes']);
                $hasMessaging = !empty($entry['messaging']);
                if ($hasChanges) {
                    foreach ($entry['changes'] as $change) {
                        Log::info('📥 Webhook Entry Change', [
                            'field' => $change['field'] ?? 'unknown',
                            'item' => $change['value']['item'] ?? 'unknown',
                            'verb' => $change['value']['verb'] ?? 'unknown',
                            'from_id' => $change['value']['from']['id'] ?? null,
                            'message' => substr($change['value']['message'] ?? '', 0, 100),
                        ]);
                    }
                }
                if ($hasMessaging) {
                    Log::info('📥 Webhook Entry Messaging', [
                        'count' => count($entry['messaging']),
                    ]);
                }

                $this->processEntry($entry);
            }
        } catch (\Exception $e) {
            // Log error แต่ยังคง return 200 เพื่อไม่ให้ Facebook retry
            Log::error('Facebook Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // ⚠️ ALWAYS return 200 OK
        return response()->json(['status' => 'ok']);
    }

    /**
     * ประมวลผล entry จาก webhook
     */
    protected function processEntry(array $entry): void
    {
        // ประมวลผลคอมเมนต์
        foreach ($entry['changes'] ?? [] as $change) {
            if (($change['field'] ?? '') === 'feed'
                && ($change['value']['item'] ?? '') === 'comment') {
                $this->processComment($change['value']);
            }
        }

        // ประมวลผล direct messages
        foreach ($entry['messaging'] ?? [] as $messaging) {
            // ตรวจจับ Echo Messages (ข้อความที่แอดมินส่งจากเพจ)
            // เมื่อแอดมินตอบข้อความ user ผ่าน Page Inbox
            // Facebook จะส่ง webhook พร้อม is_echo = true
            if (!empty($messaging['message']['is_echo'])) {
                $this->handleEchoMessage($messaging);
                continue; // ไม่ต้องประมวลผลเป็น user message
            }

            if (isset($messaging['message'])) {
                $this->processMessage($messaging);
            }

            // ประมวลผล postback events (ปุ่ม Get Started, Quick Replies, etc.)
            if (isset($messaging['postback'])) {
                $this->processPostback($messaging);
            }
        }
    }

    /**
     * ประมวลผลคอมเมนต์
     */
    protected function processComment(array $comment): void
    {
        $message = $comment['message'] ?? '';
        $fromId = $comment['from']['id'] ?? null;

        if (empty($fromId)) {
            return;
        }

        // ตรวจสอบว่าเป็นคำขอดูดวงเชิงลึกหรือพื้นฐาน
        $isDeepRequest = $this->facebookService->isDeepReadingRequest($message);
        $questions = $this->facebookService->parseQuestions($message);

        if (empty($questions)) {
            // ไม่ใช่คำสั่งดูดวง → ลอง engage ชวนดูดวง
            $this->handleCommentEngagement($comment);
            return;
        }

        // แยกวันเกิดจากข้อความ (ถ้ามี)
        $birthDate = $this->facebookService->parseBirthDate($message);

        // ตรวจสอบ limit ตามประเภทคำขอ
        if ($isDeepRequest && $this->settings->isDeepReadingEnabled()) {
            $deepLimitCheck = $this->facebookService->checkDeepFreeLimit($fromId);

            if ($deepLimitCheck['has_reached_limit']) {
                $this->sendDeepLimitMessage($comment);
                return;
            }

            $this->processFortuneTelling($comment, $questions, true, true, null, $birthDate);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($fromId);

            if ($limitCheck['has_reached_limit']) {
                $this->sendLimitMessage($comment);
                return;
            }

            $this->processFortuneTelling($comment, $questions, true, false, null, $birthDate);
        }
    }

    /**
     * จัดการ Comment Engagement - ชวนดูดวงเมื่อมีคนคอมเม้นต์
     *
     * เมื่อมีคอมเม้นต์ที่ไม่ใช่คำสั่งดูดวง:
     * 1. ตอบคอมเม้นต์สั้นๆ ชวนดูดวง
     * 2. ทัก inbox ส่วนตัว + Quick Replies
     */
    protected function handleCommentEngagement(array $comment): void
    {
        try {
            Log::info('🗨️ handleCommentEngagement called', [
                'from_id' => $comment['from']['id'] ?? null,
                'comment_id' => $comment['comment_id'] ?? null,
                'message' => substr($comment['message'] ?? '', 0, 100),
            ]);

            // ตรวจสอบว่าเปิดระบบ engagement หรือไม่
            if (!$this->settings->isCommentEngagementEnabled()) {
                Log::info('🗨️ Comment Engagement: DISABLED - skipping');
                return;
            }

            $fromId = $comment['from']['id'] ?? null;
            $commentId = $comment['comment_id'] ?? null;
            $postId = $comment['post_id'] ?? null;
            $message = $comment['message'] ?? '';
            $fromName = $comment['from']['name'] ?? null;

            if (empty($fromId) || empty($commentId) || empty($postId)) {
                Log::warning('🗨️ Comment Engagement: Missing data', [
                    'has_from_id' => !empty($fromId),
                    'has_comment_id' => !empty($commentId),
                    'has_post_id' => !empty($postId),
                ]);
                return;
            }

            // ไม่ตอบคอมเม้นต์จากเพจเอง
            if ($fromId === $this->settings->facebook_page_id) {
                Log::info('🗨️ Comment Engagement: Own page comment - skipping');
                return;
            }

            // ตรวจสอบว่าเคย engage ในโพสต์นี้แล้วหรือไม่
            if (FortuneCommentEngagement::hasEngaged($fromId, $postId)) {
                Log::info('Comment Engagement: เคย engage แล้ว ข้าม', [
                    'user_id' => $fromId,
                    'post_id' => $postId,
                ]);
                return;
            }

            $mode = $this->settings->getCommentEngagementMode();

            if ($mode === 'template') {
                // โหมดเทมเพลต: ส่งเลยไม่ต้องรอ AI
                $this->sendTemplateEngagement($comment);
            } else {
                // โหมด AI: dispatch job ให้ AI สร้างข้อความ
                ProcessCommentEngagement::dispatch([
                    'facebook_user_id' => $fromId,
                    'facebook_post_id' => $postId,
                    'facebook_comment_id' => $commentId,
                    'comment_text' => $message,
                    'user_name' => $fromName,
                ]);
            }

            Log::info('Comment Engagement: dispatched', [
                'mode' => $mode,
                'user_id' => $fromId,
                'post_id' => $postId,
            ]);

        } catch (\Exception $e) {
            Log::error('Comment Engagement Error: ' . $e->getMessage(), [
                'comment' => $comment,
            ]);
        }
    }

    /**
     * ส่ง engagement แบบเทมเพลต (ไม่ใช้ AI)
     */
    protected function sendTemplateEngagement(array $comment): void
    {
        $fromId = $comment['from']['id'];
        $commentId = $comment['comment_id'];
        $postId = $comment['post_id'];
        $commentText = $comment['message'] ?? '';
        $fromName = $comment['from']['name'] ?? 'คุณ';

        // ดึง user profile
        $userProfile = $this->facebookService->getUserProfile($fromId);
        $name = $userProfile['name'] ?? $fromName;

        // แทนที่ placeholders
        $commentReply = str_replace(
            ['{name}', '{comment}'],
            [$name, $commentText],
            $this->settings->getCommentReplyTemplate()
        );
        $dmMessage = str_replace(
            ['{name}', '{comment}'],
            [$name, $commentText],
            $this->settings->getCommentDmTemplate()
        );

        // 1. ตอบคอมเม้นต์
        $this->facebookService->replyToComment($commentId, $commentReply);

        // 2. ส่ง inbox + Quick Replies
        $quickReplies = [
            ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ['content_type' => 'text', 'title' => '🌟 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
        ];
        $this->facebookService->sendQuickReplies($fromId, $dmMessage, $quickReplies);

        // 3. บันทึก engagement
        FortuneCommentEngagement::create([
            'facebook_user_id' => $fromId,
            'facebook_post_id' => $postId,
            'facebook_comment_id' => $commentId,
            'comment_text' => $commentText,
            'comment_reply' => $commentReply,
            'dm_message' => $dmMessage,
            'user_profile' => $userProfile,
            'engaged_at' => now(),
        ]);
    }

    /**
     * จัดการ Echo Message (ข้อความที่แอดมินส่งจากเพจ)
     *
     * เมื่อแอดมินตอบ user ผ่าน Facebook Page Inbox จะมี message.is_echo = true
     * ใช้สำหรับ Admin Handover: บอทจะหยุดทำงานเมื่อแอดมินกำลังดูแลลูกค้า
     *
     * Facebook Echo Message structure:
     * - sender.id = page_id (เพจเป็นผู้ส่ง)
     * - recipient.id = user_id (user เป็นผู้รับ)
     * - message.is_echo = true
     * - message.app_id = app_id ของ bot (ถ้าส่งจาก bot จะมี app_id)
     *
     * @param array $messaging ข้อมูล messaging event จาก Facebook
     * @return void
     */
    protected function handleEchoMessage(array $messaging): void
    {
        $recipientId = $messaging['recipient']['id'] ?? null;
        $appId = $messaging['message']['app_id'] ?? null;
        $messageText = $messaging['message']['text'] ?? '';

        if (empty($recipientId)) {
            return;
        }

        // ถ้าระบบ Admin Handover ถูกปิด → ข้ามไม่ต้องทำอะไร
        if (!($this->settings->admin_handover_enabled ?? true)) {
            return;
        }

        // ถ้า echo มี app_id แสดงว่าเป็นข้อความที่บอทส่งเอง → ข้าม
        // เราสนใจเฉพาะข้อความที่แอดมิน (คน) พิมพ์ตอบเอง (ไม่มี app_id)
        if (!empty($appId)) {
            return;
        }

        // แอดมินกำลังตอบ user คนนี้ → ทำเครื่องหมายว่าแอดมินกำลังดูแล
        $timeoutMinutes = $this->settings->admin_handover_timeout ?? 15;
        Cache::put(
            "fortune_admin_active:{$recipientId}",
            [
                'active_at' => now()->toIso8601String(),
                'admin_message' => mb_substr($messageText, 0, 100),
            ],
            now()->addMinutes($timeoutMinutes)
        );

        Log::info('👨‍💼 Admin Handover: แอดมินกำลังดูแลลูกค้า', [
            'user_id' => $recipientId,
            'timeout_minutes' => $timeoutMinutes,
            'message_preview' => mb_substr($messageText, 0, 50),
        ]);
    }

    /**
     * ตรวจสอบว่าแอดมินกำลังดูแล user คนนี้อยู่หรือไม่
     *
     * @param string $userId Facebook User ID
     * @return bool true = แอดมินกำลังดูแล, บอทควรหยุดทำงาน
     */
    protected function isAdminActive(string $userId): bool
    {
        return Cache::has("fortune_admin_active:{$userId}");
    }

    /**
     * ประมวลผล direct message (Messenger)
     *
     * รองรับ:
     * - ข้อความ text
     * - รูปภาพ (image attachment)
     * - Quick reply payloads
     * - Conversational fortune telling flow (ใหม่)
     * - Admin Handover: บอทหยุดทำงานเมื่อแอดมินกำลังดูแล
     */
    protected function processMessage(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;

        if (empty($senderId)) {
            return;
        }

        // 🛑 Admin Handover: ถ้าแอดมินกำลังดูแล user คนนี้ → บอทหยุดทำงาน
        if ($this->isAdminActive($senderId)) {
            Log::info('👨‍💼 Admin Handover: บอทข้ามข้อความ (แอดมินกำลังดูแล)', [
                'user_id' => $senderId,
                'message_preview' => mb_substr($messaging['message']['text'] ?? '', 0, 50),
            ]);
            return;
        }

        // ตรวจสอบ Quick Reply payload
        $quickReplyPayload = $messaging['message']['quick_reply']['payload'] ?? null;
        if ($quickReplyPayload) {
            $this->handleQuickReply($senderId, $quickReplyPayload);
            return;
        }

        $messageText = $messaging['message']['text'] ?? '';
        $attachments = $messaging['message']['attachments'] ?? [];

        // ตรวจสอบว่ามีรูปภาพแนบมาหรือไม่
        $userImageUrl = null;
        if (!empty($attachments)) {
            $userImageUrl = $this->facebookService->extractImageFromAttachments($attachments);

            // ถ้าส่งมาเฉพาะรูป (ไม่มี text) ตอบกลับแนะนำวิธีใช้
            if (empty($messageText) && $userImageUrl) {
                $this->facebookService->sendMessage(
                    $senderId,
                    "📸 ได้รับรูปภาพแล้วค่ะ\n\nกรุณาพิมพ์ 'ดูดวง' เพื่อเริ่มดูดวง\n\nตัวอย่าง: ดูดวง เรื่องความรัก"
                );
                return;
            }
        }

        // ใช้ Conversational Flow ใหม่
        $this->processConversationalMessage($senderId, $messageText);
    }

    /**
     * ประมวลผล postback events (ปุ่ม Get Started, Persistent Menu, etc.)
     *
     * Facebook จะส่ง postback เมื่อ:
     * - ผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     * - ผู้ใช้เลือกรายการจาก Persistent Menu
     * - ผู้ใช้กดปุ่ม Template ที่เป็น postback
     *
     * @param array $messaging ข้อมูล messaging event จาก Facebook
     * @return void
     */
    protected function processPostback(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $payload = $messaging['postback']['payload'] ?? null;

        if (empty($senderId) || empty($payload)) {
            Log::warning('Facebook Postback: Missing sender or payload', [
                'messaging' => $messaging,
            ]);
            return;
        }

        // 🛑 Admin Handover: ถ้าแอดมินกำลังดูแล → บอทข้าม postback
        if ($this->isAdminActive($senderId)) {
            Log::info('👨‍💼 Admin Handover: บอทข้าม postback (แอดมินกำลังดูแล)', [
                'user_id' => $senderId,
                'payload' => $payload,
            ]);
            return;
        }

        Log::info('📬 Facebook Postback received', [
            'sender_id' => $senderId,
            'payload' => $payload,
        ]);

        // จัดการ postback ตาม payload
        match ($payload) {
            // ปุ่ม Get Started - ผู้ใช้เข้ามาใช้งานครั้งแรก
            'GET_STARTED', 'get_started' => $this->handleGetStarted($senderId),

            // Persistent Menu options
            'MENU_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'MENU_DEEP_FORTUNE' => $this->processConversationalMessage($senderId, 'ต้องการดูละเอียด'),
            'MENU_CHECK_REMAINING' => $this->processConversationalMessage($senderId, 'เช็คสิทธิ์'),
            'MENU_HELP' => $this->sendHelpMessage($senderId),

            // ส่งไปจัดการตาม Quick Reply (backward compatibility)
            default => $this->handleQuickReply($senderId, $payload),
        };
    }

    /**
     * จัดการเมื่อผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     *
     * ส่งข้อความต้อนรับและแนะนำวิธีใช้งาน
     *
     * @param string $senderId Facebook User ID
     * @return void
     */
    protected function handleGetStarted(string $senderId): void
    {
        try {
            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile
            $userProfile = $this->facebookService->getUserProfile($senderId);
            $userName = $userProfile['name'] ?? 'คุณ';

            Log::info('🎉 New user started conversation', [
                'sender_id' => $senderId,
                'user_name' => $userName,
            ]);

            // ข้อความต้อนรับ
            $welcomeMessage = $this->buildWelcomeMessage($userName);

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // ส่งข้อความต้อนรับพร้อม Quick Replies
            $quickReplies = [
                ['content_type' => 'text', 'title' => '🔮 ดูดวงฟรี', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
                ['content_type' => 'text', 'title' => '❓ วิธีใช้งาน', 'payload' => 'HELP'],
            ];

            $this->facebookService->sendQuickReplies($senderId, $welcomeMessage, $quickReplies);

        } catch (\Exception $e) {
            Log::error('Get Started Error: ' . $e->getMessage(), [
                'sender_id' => $senderId,
            ]);

            $this->facebookService->sendTypingIndicator($senderId, false);
            $this->facebookService->sendMessage(
                $senderId,
                "🔮 ยินดีต้อนรับค่ะ!\n\nพิมพ์ 'ดูดวง' เพื่อเริ่มต้นดูดวงได้เลยนะคะ ✨"
            );
        }
    }

    /**
     * สร้างข้อความต้อนรับสำหรับผู้ใช้ใหม่
     *
     * @param string $userName ชื่อผู้ใช้
     * @return string
     */
    protected function buildWelcomeMessage(string $userName): string
    {
        $message = "✨ *สวัสดีค่ะ คุณ{$userName}!*\n\n";
        $message .= "🔮 ยินดีต้อนรับสู่ระบบดูดวง AI\n";
        $message .= "ทางเพจพร้อมทำนายดวงชะตาให้คุณค่ะ\n\n";

        $message .= "═══════════════════════\n";
        $message .= "📋 *บริการของเรา*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "🆓 *ดูดวงพื้นฐาน (ฟรี)*\n";
        $message .= "ทำนายเรื่องทั่วไป ความรัก การงาน การเงิน\n\n";

        $message .= "💎 *ดูดวงละเอียด (49 บาท)*\n";
        $message .= "ทำนายเชิงลึก 3 คำถาม พร้อมวันเกิด\n\n";

        $message .= "═══════════════════════\n";
        $message .= "💡 *วิธีเริ่มต้น*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "พิมพ์อะไรก็ได้มาคุยกับทางเพจได้เลยค่ะ!\n\n";

        $message .= "ตัวอย่าง:\n";
        $message .= "• ดวงความรักปีนี้เป็นอย่างไร\n";
        $message .= "• ปีนี้จะได้เลื่อนตำแหน่งไหม\n";
        $message .= "• การเงินเดือนหน้าเป็นอย่างไร\n\n";

        $message .= "📊 พิมพ์ 'เช็คสิทธิ์' เพื่อดูจำนวนครั้งฟรีที่เหลือ\n\n";

        $message .= "กดปุ่มด้านล่างหรือพิมพ์เลยค่ะ 👇";

        return $message;
    }

    /**
     * ประมวลผลข้อความแบบ Conversational (flow ใหม่)
     *
     * Flow:
     * 1. ดูดวงพื้นฐานฟรี (ดึงโปรไฟล์ทำนายเบื้องต้น)
     * 2. เสนอดูดวงละเอียด 49 บาท (ถามวันเกิด + 3 คำถาม)
     * 3. สร้างบิล + unique amount + แสดงบัญชีธนาคาร
     * 4. SMS match → ส่งคำทำนายละเอียดผ่าน Messenger
     *
     * @param string $senderId Facebook User ID
     * @param string $messageText ข้อความที่ส่งมา
     * @return void
     */
    protected function processConversationalMessage(string $senderId, string $messageText): void
    {
        try {
            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile
            $userProfile = $this->facebookService->getUserProfile($senderId);

            // ประมวลผลข้อความ
            $result = $this->conversationService->processMessage($senderId, $messageText, $userProfile);

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // ส่งข้อความกลับ
            $message = $result['message'] ?? '';
            if (!empty($message)) {
                // แยกข้อความยาวออกเป็นหลายๆ ข้อความ
                $this->sendLongMessage($senderId, $message);
            }

            // ส่ง Quick Replies ถ้าต้องการ หรือสำหรับ actions ที่มี quick replies
            $actionsWithQuickReplies = ['basic_done', 'check_remaining'];
            if (!empty($result['show_quick_replies']) || in_array($result['action'] ?? '', $actionsWithQuickReplies)) {
                $this->sendConversationQuickReplies($senderId, $result['action']);
            }

            Log::info('Conversational Fortune: ประมวลผลสำเร็จ', [
                'facebook_user_id' => $senderId,
                'action' => $result['action'],
                'reading_id' => $result['reading']?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Conversational Fortune Error: ' . $e->getMessage(), [
                'facebook_user_id' => $senderId,
                'message' => $messageText,
                'error_class' => get_class($e),
                'trace' => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);

            try {
                $this->facebookService->sendTypingIndicator($senderId, false);
            } catch (\Exception $ignored) {}

            // ส่งข้อความที่เป็นมิตรกับผู้ใช้ แทนที่จะบอกว่า "ผิดพลาด"
            try {
                $this->facebookService->sendMessage(
                    $senderId,
                    "🔮 สวัสดีค่ะ\n\n" .
                    "ตอนนี้ระบบกำลังปรับปรุงชั่วคราวค่ะ\n" .
                    "กรุณาลองพิมพ์มาใหม่อีกครั้งนะคะ 🙏\n\n" .
                    "💡 ลองพิมพ์ 'เช็คสิทธิ์' เพื่อดูสิทธิ์ดูดวงฟรี\n" .
                    "หรือพิมพ์คำถามใหม่ได้เลยค่ะ ✨"
                );
            } catch (\Exception $sendError) {
                Log::error('ส่งข้อความ error response ไม่สำเร็จ: ' . $sendError->getMessage());
            }
        }
    }

    /**
     * ส่งข้อความยาวโดยแบ่งเป็นหลายข้อความ
     *
     * @param string $senderId
     * @param string $message
     * @return void
     */
    protected function sendLongMessage(string $senderId, string $message): void
    {
        // Facebook Messenger รองรับข้อความยาวสูงสุด 2000 ตัวอักษร
        $maxLength = 1800;

        if (mb_strlen($message) <= $maxLength) {
            $this->facebookService->sendMessage($senderId, $message);
            return;
        }

        // แบ่งข้อความตาม paragraph หรือ ═══
        $parts = preg_split('/(?=═══════════════════════)/', $message);

        $currentMessage = '';
        foreach ($parts as $part) {
            if (mb_strlen($currentMessage . $part) > $maxLength && !empty($currentMessage)) {
                $this->facebookService->sendMessage($senderId, trim($currentMessage));
                usleep(300000); // รอ 300ms ระหว่างข้อความ
                $currentMessage = $part;
            } else {
                $currentMessage .= $part;
            }
        }

        if (!empty($currentMessage)) {
            $this->facebookService->sendMessage($senderId, trim($currentMessage));
        }
    }

    /**
     * ส่ง Quick Replies ตาม action
     *
     * @param string $senderId
     * @param string $action
     * @return void
     */
    protected function sendConversationQuickReplies(string $senderId, string $action): void
    {
        $quickReplies = match ($action) {
            'basic_done' => [
                ['content_type' => 'text', 'title' => '✨ ต้องการดูละเอียด', 'payload' => 'DEEP_READING_ACCEPT'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
                ['content_type' => 'text', 'title' => '❌ ไม่ต้องการ', 'payload' => 'DEEP_READING_DECLINE'],
            ],
            'check_remaining' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
            ],
            default => null,
        };

        if ($quickReplies) {
            // ส่ง Quick Replies แยก
            usleep(500000); // รอ 500ms
            $this->facebookService->sendQuickReplies(
                $senderId,
                "เลือกได้เลยค่ะ 👇",
                $quickReplies
            );
        }
    }

    /**
     * ทำนายดวง (รองรับพื้นฐานและเชิงลึก)
     *
     * เมื่อ queue driver ไม่ใช่ 'sync' จะ dispatch job แบบ async
     * เพื่อไม่ให้ webhook response ช้า (Facebook timeout 20 วินาที)
     * ถ้าเป็น 'sync' จะประมวลผลแบบ inline ทันที
     *
     * @param array $data ข้อมูลจาก Facebook
     * @param array $questions คำถามที่แยกแล้ว
     * @param bool $isComment เป็นคอมเมนต์หรือ direct message
     * @param bool $isDeep เป็นคำทำนายเชิงลึกหรือไม่
     * @param string|null $userImageUrl URL รูปที่ผู้ใช้ส่งมา
     * @param string|null $birthDate วันเกิดรูปแบบ Y-m-d
     */
    protected function processFortuneTelling(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep = false,
        ?string $userImageUrl = null,
        ?string $birthDate = null
    ): void {
        $fromId = $isComment ? ($data['from']['id'] ?? null) : ($data['sender']['id'] ?? null);
        $fromName = $isComment ? ($data['from']['name'] ?? null) : null;

        if (empty($fromId)) {
            return;
        }

        // เตรียมข้อมูลสำหรับ job
        $jobData = [
            'facebook_user_id' => $fromId,
            'facebook_user_name' => $fromName,
            'questions' => $questions,
            'is_comment' => $isComment,
            'is_deep' => $isDeep,
            'is_paid' => false,
            'amount_paid' => 0,
            'user_image_url' => $userImageUrl,
            'birth_date' => $birthDate,
            'comment_id' => $isComment ? ($data['comment_id'] ?? null) : null,
            'post_id' => $isComment ? ($data['post_id'] ?? null) : null,
            'reply_type' => $isComment ? 'comment' : 'message',
        ];

        // ใช้ queue แบบ async เมื่อมี queue driver ที่รองรับ
        // เพื่อ return 200 ให้ Facebook ทันเวลา (Facebook timeout 20 วินาที)
        $queueDriver = config('queue.default', 'sync');

        if ($queueDriver !== 'sync') {
            // Async: dispatch job ให้ queue จัดการ
            ProcessFortuneTelling::dispatch($jobData);

            Log::info('Fortune telling job dispatched (async)', [
                'facebook_user_id' => $fromId,
                'reading_type' => $isDeep ? 'deep' : 'basic',
                'queue_driver' => $queueDriver,
            ]);
            return;
        }

        // Sync fallback: ประมวลผลทันที (สำหรับ dev หรือเมื่อไม่มี queue)
        $this->processFortuneSync($data, $questions, $isComment, $isDeep, $userImageUrl, $fromId, $fromName, $birthDate);
    }

    /**
     * ประมวลผลคำทำนายแบบ synchronous (ใช้เมื่อ queue = sync)
     *
     * @param array $data ข้อมูลจาก Facebook
     * @param array $questions คำถาม
     * @param bool $isComment เป็นคอมเมนต์
     * @param bool $isDeep เป็นเชิงลึก
     * @param string|null $userImageUrl รูปจากผู้ใช้
     * @param string $fromId Facebook User ID
     * @param string|null $fromName ชื่อผู้ใช้
     * @param string|null $birthDate วันเกิดรูปแบบ Y-m-d
     */
    protected function processFortuneSync(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep,
        ?string $userImageUrl,
        string $fromId,
        ?string $fromName,
        ?string $birthDate = null
    ): void {
        // ส่ง typing indicator ขณะ AI กำลังประมวลผล
        if (!$isComment) {
            $this->facebookService->sendTypingIndicator($fromId);
        }

        $userProfile = $this->facebookService->getUserProfile($fromId);
        // ดึงโพสล่าสุดเฉพาะคำทำนายเชิงลึก
        $userPosts = $isDeep ? $this->facebookService->getUserPosts($fromId, 3) : null;

        try {
            // เลือก prompt template ตามระดับ
            $promptTemplate = $isDeep
                ? $this->settings->getDeepPromptTemplate()
                : $this->settings->getBasicPromptTemplate();

            $readingType = $isDeep ? 'deep' : 'basic';

            $aiResponse = $this->aiService->generateFortuneTelling(
                $questions,
                $userProfile,
                $userPosts,
                $promptTemplate,
                $readingType,
                $birthDate
            );

            // บันทึกผลลงฐานข้อมูล
            $reading = FortuneReading::create([
                'facebook_user_id' => $fromId,
                'facebook_user_name' => $fromName ?? $userProfile['name'] ?? null,
                'facebook_comment_id' => $isComment ? ($data['comment_id'] ?? null) : null,
                'facebook_post_id' => $isComment ? ($data['post_id'] ?? null) : null,
                'questions' => $questions,
                'ai_response' => $aiResponse['response'],
                'user_profile' => $userProfile,
                'user_posts_context' => $userPosts,
                'birth_date' => $birthDate,
                'ai_provider' => $aiResponse['provider'],
                'ai_model' => $aiResponse['model'],
                'tokens_used' => $aiResponse['tokens_used'],
                'response_type' => ($isComment && $this->settings->respond_in_comment) ? 'comment' : 'private_message',
                'reading_type' => $readingType,
                'user_image_url' => $userImageUrl,
                'is_paid' => false,
            ]);

            // ปิด typing indicator
            if (!$isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            // ส่งคำทำนายกลับ (รองรับรูปภาพ + message splitting)
            if ($this->facebookService->sendFortuneTelling($reading, $aiResponse['response'])) {
                $reading->markAsResponded();
            }

            // หลังส่งคำทำนายเชิงลึกฟรี ส่ง quick replies แนะนำจ่ายเงิน/สมัครสมาชิก
            if ($isDeep && $this->settings->isTryBeforeBuyEnabled() && !$isComment) {
                $tryBeforeBuyMsg = $this->settings->getTryBeforeBuyMessage();
                $this->facebookService->sendQuickReplies($fromId, $tryBeforeBuyMsg, [
                    ['title' => 'ดูดวงอีกครั้ง', 'payload' => 'FORTUNE_BASIC'],
                    ['title' => 'ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                    ['title' => 'สมัครสมาชิก', 'payload' => 'SUBSCRIBE'],
                ]);
            }

            Log::info('ทำนายสำเร็จ (sync)', [
                'reading_id' => $reading->id,
                'type' => $readingType,
                'provider' => $aiResponse['provider'],
                'tokens' => $aiResponse['tokens_used'],
            ]);
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการทำนาย: ' . $e->getMessage(), [
                'from_id' => $fromId,
                'is_deep' => $isDeep,
                'trace' => $e->getTraceAsString(),
            ]);

            // ปิด typing indicator
            if (!$isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            $this->facebookService->sendMessage(
                $fromId,
                "ขออภัยค่ะ เกิดข้อผิดพลาดในการทำนาย กรุณาลองใหม่อีกครั้งในอีกสักครู่ 🙏"
            );
        }
    }

    /**
     * จัดการ Quick Reply payload
     */
    protected function handleQuickReply(string $senderId, string $payload): void
    {
        match ($payload) {
            // Quick Replies ใหม่สำหรับ conversational flow
            'DEEP_READING_ACCEPT' => $this->processConversationalMessage($senderId, 'ต้องการดูละเอียด'),
            'DEEP_READING_DECLINE' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),

            // Quick Replies เดิม (backward compatibility)
            'FORTUNE_BASIC' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'FORTUNE_DEEP' => $this->processConversationalMessage($senderId, 'ต้องการดูละเอียด'),
            'CHECK_REMAINING' => $this->processConversationalMessage($senderId, 'เช็คสิทธิ์'),
            'SUBSCRIBE' => $this->facebookService->sendMessage(
                $senderId,
                $this->settings->getSubscriptionMessage()
            ),
            'HELP' => $this->sendHelpMessage($senderId),
            default => $this->processConversationalMessage($senderId, $payload),
        };
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (พื้นฐาน)
     */
    protected function sendLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'] ?? '', $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'] ?? '', $message);
        }
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (เชิงลึก)
     */
    protected function sendDeepLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getDeepLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'] ?? '', $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'] ?? '', $message);
        }
    }

    /**
     * ส่งคำแนะนำการใช้งานพร้อม Quick Reply buttons
     */
    protected function sendHelpMessage(string $userId): void
    {
        $message = "🔮 ระบบดูดวง AI\n\n";
        $message .= "📌 ดูดวงพื้นฐาน (ฟรี):\n";
        $message .= "พิมพ์: ดูดวง ตามด้วยคำถาม\n";
        $message .= "ตัวอย่าง: ดูดวง เรื่องความรัก, เรื่องการเงิน\n\n";

        if ($this->settings->isDeepReadingEnabled()) {
            $message .= "🌟 ดูดวงเชิงลึก (ละเอียด):\n";
            $message .= "พิมพ์: ดูดวงละเอียด ตามด้วยคำถาม\n";
            $message .= "(ฟรี {$this->settings->free_deep_per_day} ครั้ง/วัน)\n\n";
        }

        $message .= "📸 ส่งรูปภาพ:\n";
        $message .= "ส่งรูปพร้อมข้อความ 'ดูดวง' หรือ 'ดูดวงละเอียด'\n";

        // ส่งพร้อม quick reply buttons
        $quickReplies = [
            ['title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ['title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
        ];

        if ($this->settings->isDeepReadingEnabled()) {
            $quickReplies[] = ['title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'];
        }

        $this->facebookService->sendQuickReplies($userId, $message, $quickReplies);
    }
}
