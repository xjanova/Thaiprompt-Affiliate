<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommentEngagement;
use App\Jobs\ProcessFortuneTelling;
use App\Models\FortuneCommentEngagement;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneTakeoverService;
use Illuminate\Http\Request;
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

    /**
     * FortuneChannelManager — ตัวกลางจัดการ routing + Rich Message response
     * ใช้ตรรกะเดียวกันกับ LINE Bot (keyword matching, state machine, AI chat)
     */
    protected $channelManager;

    /**
     * FortuneTakeoverService — ระบบเทคโอเวอร์ (แม่หมอ/แอดมินคุยแทน AI)
     * ใช้ร่วมกันกับ LINE เพื่อให้โฟลและ cache ไม่ขัดแย้งกัน
     */
    protected FortuneTakeoverService $takeoverService;

    public function __construct()
    {
        try {
            $this->settings = FortuneTellingSetting::getSettings();
            $this->facebookService = new FacebookWebhookService($this->settings);
            $this->aiService = new FortuneAIService($this->settings);
            $this->conversationService = new FortuneConversationService($this->settings);
            $this->channelManager = new FortuneChannelManager($this->settings);
            $this->takeoverService = app(FortuneTakeoverService::class);
        } catch (\Exception $e) {
            // ป้องกัน controller พังทั้งหมดถ้า DB/Pool มีปัญหา
            Log::error('FacebookWebhookController: เริ่มต้นระบบไม่สำเร็จ', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            // สร้าง fallback services เพื่อให้ระบบยังทำงานได้
            if (! $this->settings) {
                $this->settings = new FortuneTellingSetting;
            }
            if (! $this->facebookService) {
                try {
                    $this->facebookService = new FacebookWebhookService($this->settings);
                } catch (\Exception $fbError) {
                    Log::error('FacebookWebhookController: สร้าง FacebookWebhookService ไม่ได้', [
                        'error' => $fbError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ aiService และ conversationService ด้วย
            if (! $this->aiService) {
                try {
                    $this->aiService = new FortuneAIService($this->settings);
                } catch (\Exception $aiError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneAIService ไม่ได้', [
                        'error' => $aiError->getMessage(),
                    ]);
                }
            }
            if (! $this->conversationService) {
                try {
                    $this->conversationService = new FortuneConversationService($this->settings);
                } catch (\Exception $convError) {
                    Log::error('FacebookWebhookController: สร้าง ConversationService ไม่ได้', [
                        'error' => $convError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ channelManager
            if (! $this->channelManager) {
                try {
                    $this->channelManager = new FortuneChannelManager($this->settings);
                } catch (\Exception $cmError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneChannelManager ไม่ได้', [
                        'error' => $cmError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ takeoverService
            if (! isset($this->takeoverService)) {
                try {
                    $this->takeoverService = app(FortuneTakeoverService::class);
                } catch (\Exception $toError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneTakeoverService ไม่ได้', [
                        'error' => $toError->getMessage(),
                    ]);
                }
            }
        }
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
                'has_signature' => ! empty($request->header('X-Hub-Signature-256')),
                'content_length' => strlen($request->getContent()),
                'ip' => $request->ip(),
            ]);

            // ตรวจสอบ webhook signature (security)
            $signature = $request->header('X-Hub-Signature-256', '');
            if (! $this->facebookService->verifyWebhookSignature(
                $request->getContent(),
                $signature
            )) {
                Log::warning('Facebook Webhook: Invalid signature', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['status' => 'ok']); // ยังคง return 200
            }

            if (! $this->settings->isServiceEnabled()) {
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
                $hasChanges = ! empty($entry['changes']);
                $hasMessaging = ! empty($entry['messaging']);
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
            Log::error('Facebook Webhook Error: '.$e->getMessage(), [
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
            if (! empty($messaging['message']['is_echo'])) {
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
                // ✅ ครบ limit → ใช้ engagement template ชวนดูดวงแทนการส่ง limit message
                Log::info('🚫 Deep limit reached → redirect to engagement', [
                    'user_id' => $fromId,
                ]);
                $this->handleCommentEngagement($comment);

                return;
            }

            $this->processFortuneTelling($comment, $questions, true, true, null, $birthDate);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($fromId);

            if ($limitCheck['has_reached_limit']) {
                // ✅ ครบ limit → ใช้ engagement template ชวนดูดวงแทนการส่ง limit message
                Log::info('🚫 Free limit reached → redirect to engagement', [
                    'user_id' => $fromId,
                ]);
                $this->handleCommentEngagement($comment);

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
            if (! $this->settings->isCommentEngagementEnabled()) {
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
                    'has_from_id' => ! empty($fromId),
                    'has_comment_id' => ! empty($commentId),
                    'has_post_id' => ! empty($postId),
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
            Log::error('Comment Engagement Error: '.$e->getMessage(), [
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

        // ดึง user profile พร้อม fallback
        $userProfile = $this->facebookService->getUserProfile($fromId);
        if (! is_array($userProfile)) {
            $userProfile = ['name' => $fromName, 'id' => $fromId];
        }
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
        // ส่ง comment_id เพื่อให้ใช้ Private Replies endpoint (bypass 24hr window
        // และแก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ)
        $quickReplies = [
            ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ['content_type' => 'text', 'title' => '🌟 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
        ];
        $this->facebookService->sendQuickReplies($fromId, $dmMessage, $quickReplies, [
            'from_comment_engagement' => true,
            'comment_id' => $commentId,
        ]);

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
     * @param  array  $messaging  ข้อมูล messaging event จาก Facebook
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
        // ⚠️ Default เป็น false เพื่อป้องกันบอทถูกบล็อกโดยไม่ตั้งใจ
        if (! ($this->settings->admin_handover_enabled ?? false)) {
            return;
        }

        // ถ้า echo มี app_id แสดงว่าเป็นข้อความที่บอทส่งเอง → ข้าม
        // เราสนใจเฉพาะข้อความที่แอดมิน (คน) พิมพ์ตอบเอง (ไม่มี app_id)
        if (! empty($appId)) {
            return;
        }

        // หา reading ล่าสุดของ user นี้ (เฉพาะที่ยัง active อยู่)
        $reading = FortuneReading::where(function ($q) use ($recipientId) {
            $q->where('facebook_user_id', $recipientId)
                ->orWhere(function ($sub) use ($recipientId) {
                    $sub->where('platform', 'facebook')
                        ->where('platform_user_id', $recipientId);
                });
        })
            ->whereNotIn('conversation_status', [
                FortuneReading::STATUS_COMPLETED,
            ])
            ->latest()
            ->first();

        if (! $reading) {
            // ไม่มี active conversation — ข้าม (ถ้าลูกค้าสร้าง conversation ใหม่
            // webhook processMessage จะเช็ค takeover ผ่าน isActiveByPlatform ใหม่อีกครั้ง)
            Log::debug('Facebook Takeover: echo โดยไม่มี active reading — ข้าม', [
                'user_id' => $recipientId,
            ]);

            return;
        }

        // ตรวจว่าแอดมินพิมพ์คำสั่งให้ AI กลับมาหรือไม่
        if ($this->takeoverService->detectAdminResumeCommand($messageText)) {
            $this->takeoverService->resume($reading, null, true);
            Log::info('✨ Facebook: แอดมินพิมพ์คำสั่งให้ AI กลับมา', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // แอดมินพิมพ์ปกติ → เทคโอเวอร์อัตโนมัติ
        $this->takeoverService->takeover(
            $reading,
            FortuneReading::TAKEOVER_REASON_AUTO_REPLY,
            null,
            null,
            $messageText,
        );
    }

    /**
     * ตรวจสอบว่าแอดมินกำลังดูแล user คนนี้อยู่หรือไม่
     *
     * @param  string  $userId  Facebook User ID
     * @return bool true = แอดมินกำลังดูแล, บอทควรหยุดทำงาน
     */
    protected function isAdminActive(string $userId): bool
    {
        // ใช้ service กลางเพื่อ sync กับ LINE
        return $this->takeoverService->isActiveByPlatform('facebook', $userId);
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

        // 🙋 Customer Handoff: ลูกค้าพิมพ์ขอคุยกับคนจริง → เทคโอเวอร์ + แจ้งลูกค้า
        if (! empty($messageText) && $this->takeoverService->detectCustomerHandoffRequest($messageText)) {
            $this->handleCustomerHandoffRequest($senderId, $messageText);

            return;
        }

        // ตรวจสอบว่ามีรูปภาพแนบมาหรือไม่
        $userImageUrl = null;
        if (! empty($attachments)) {
            $userImageUrl = $this->facebookService->extractImageFromAttachments($attachments);

            // ถ้าส่งมาเฉพาะรูป (ไม่มี text) ตอบกลับแนะนำวิธีใช้
            if (empty($messageText) && $userImageUrl) {
                $this->facebookService->sendMessage(
                    $senderId,
                    "📸 ได้รับรูปภาพแล้ว\n\nกรุณาพิมพ์ 'ดูดวง' เพื่อเริ่มดูดวง\n\nตัวอย่าง: ดูดวง เรื่องความรัก"
                );

                return;
            }
        }

        // ใช้ Conversational Flow ใหม่
        $this->processConversationalMessage($senderId, $messageText);
    }

    /**
     * จัดการเมื่อลูกค้าขอคุยกับคนจริง (customer handoff request)
     *
     * Flow:
     * 1. หาหรือสร้าง active reading
     * 2. เทคโอเวอร์ด้วย reason=customer_request
     * 3. แจ้งลูกค้า (ถ้า settings เปิดไว้)
     */
    protected function handleCustomerHandoffRequest(string $senderId, string $messageText): void
    {
        // หา active reading ของ user นี้
        $reading = FortuneReading::where(function ($q) use ($senderId) {
            $q->where('facebook_user_id', $senderId)
                ->orWhere(function ($sub) use ($senderId) {
                    $sub->where('platform', 'facebook')
                        ->where('platform_user_id', $senderId);
                });
        })
            ->latest()
            ->first();

        // ถ้าไม่มี reading เลย → สร้าง placeholder เพื่อให้ track ได้
        if (! $reading) {
            $reading = FortuneReading::create([
                'facebook_user_id' => $senderId,
                'platform' => 'facebook',
                'platform_user_id' => $senderId,
                'reading_type' => 'basic',
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
                'conversation_state' => ['placeholder' => true, 'source' => 'takeover_only'],
                'questions' => [],
                'ai_response' => '',
                'ai_provider' => 'none',
            ]);
        }

        $this->takeoverService->takeover(
            $reading,
            FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
            null,
            null,
            $messageText,
        );

        // แจ้งลูกค้า (ถ้าเปิดไว้)
        if ($this->settings->shouldNotifyTakeoverToCustomer()) {
            $this->facebookService->sendMessage(
                $senderId,
                $this->settings->getTakeoverCustomerMessage()
            );
        }
    }

    /**
     * ประมวลผล postback events (ปุ่ม Get Started, Persistent Menu, etc.)
     *
     * Facebook จะส่ง postback เมื่อ:
     * - ผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     * - ผู้ใช้เลือกรายการจาก Persistent Menu
     * - ผู้ใช้กดปุ่ม Template ที่เป็น postback
     *
     * @param  array  $messaging  ข้อมูล messaging event จาก Facebook
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

            // ✅ ปุ่มจาก Rich Templates — ดูดวงละเอียด flow
            'REPORT_PAYMENT' => $this->processConversationalMessage($senderId, 'แจ้งชำระเงิน'),
            'CANCEL_PAYMENT' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            'SHOW_BANK_ACCOUNT' => $this->processConversationalMessage($senderId, 'แสดงบัญชี'),
            'CANCEL_DEEP' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),

            // ✅ ปุ่ม LINE Invite + Affiliate Share
            'LINE_ADD_FRIEND' => $this->handleLineAddFriend($senderId),
            'LINE_INVITE' => $this->handleLineAddFriend($senderId),
            'AFFILIATE_SHARE' => $this->processConversationalMessage($senderId, 'แชร์'),

            // ส่งไปจัดการตาม Quick Reply (backward compatibility)
            default => $this->handleQuickReply($senderId, $payload),
        };
    }

    /**
     * จัดการเมื่อผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     *
     * ส่ง Rich Welcome Template พร้อมปุ่มดูดวง + เพิ่มเพื่อน LINE
     *
     * @param  string  $senderId  Facebook User ID
     */
    protected function handleGetStarted(string $senderId): void
    {
        try {
            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile พร้อม fallback
            $userProfile = $this->facebookService->getUserProfile($senderId);
            $userName = (is_array($userProfile) && ! empty($userProfile['name'])) ? $userProfile['name'] : 'คุณ';

            Log::info('🎉 New user started conversation', [
                'sender_id' => $senderId,
                'user_name' => $userName,
            ]);

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // ✅ ส่ง Rich Welcome Template พร้อม Quick Replies
            $richService = new \App\Services\FacebookRichMessageService($this->settings);
            $welcomeTemplate = $richService->buildWelcomeTemplate($userName);
            $welcomeQuickReplies = $richService->getQuickRepliesForAction('help');

            if ($welcomeTemplate && ! empty($welcomeTemplate['elements'])) {
                // ส่ง Generic Template + Quick Replies
                $this->facebookService->sendTemplateWithQuickReplies(
                    $senderId,
                    [
                        'template_type' => 'generic',
                        'elements' => $welcomeTemplate['elements'],
                    ],
                    $welcomeQuickReplies
                );
            } else {
                // Fallback: ส่งข้อความต้อนรับธรรมดา + Quick Replies
                $welcomeMessage = $this->buildWelcomeMessage($userName);
                $quickReplies = [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวงฟรี', 'payload' => 'FORTUNE_BASIC'],
                    ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
                    ['content_type' => 'text', 'title' => '❓ วิธีใช้งาน', 'payload' => 'HELP'],
                ];
                $this->facebookService->sendQuickReplies($senderId, $welcomeMessage, $quickReplies);
            }

        } catch (\Exception $e) {
            Log::error('Get Started Error: '.$e->getMessage(), [
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
     * @param  string  $userName  ชื่อผู้ใช้
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
        $message .= "ทำนายเชิงลึก 2 คำถาม พร้อมวันเกิด\n\n";

        $message .= "═══════════════════════\n";
        $message .= "💡 *วิธีเริ่มต้น*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "พิมพ์อะไรก็ได้มาคุยกับทางเพจได้เลยค่ะ!\n\n";

        $message .= "ตัวอย่าง:\n";
        $message .= "• ดวงความรักปีนี้เป็นอย่างไร\n";
        $message .= "• ปีนี้จะได้เลื่อนตำแหน่งไหม\n";
        $message .= "• การเงินเดือนหน้าเป็นอย่างไร\n\n";

        $message .= "📊 พิมพ์ 'เช็คสิทธิ์' เพื่อดูจำนวนครั้งฟรีที่เหลือ\n\n";

        $message .= 'กดปุ่มด้านล่างหรือพิมพ์เลยค่ะ 👇';

        return $message;
    }

    /**
     * ประมวลผลข้อความแบบ Conversational ผ่าน FortuneChannelManager
     *
     * ใช้ตรรกะเดียวกับ LINE Bot:
     * - keyword matching → state machine → AI fallback
     * - Rich Message Templates (Button/Generic แทน Flex)
     * - Quick Replies อัตโนมัติตาม action
     * - LINE invite + affiliate share
     *
     * Flow:
     * 1. ดูดวงพื้นฐานฟรี (ดึงโปรไฟล์ทำนายเบื้องต้น)
     * 2. เสนอดูดวงละเอียด 49 บาท (ถามวันเกิด + 2 คำถาม)
     * 3. สร้างบิล + unique amount + แสดงบัญชีธนาคาร
     * 4. SMS match → ส่งคำทำนายละเอียดผ่าน Messenger
     *
     * ⚠️ ไม่แตะ SMS Payment / UniquePaymentAmount / confirmPayment()
     *
     * @param  string  $senderId  Facebook User ID
     * @param  string  $messageText  ข้อความที่ส่งมา
     */
    protected function processConversationalMessage(string $senderId, string $messageText): void
    {
        try {
            // ตรวจสอบว่า channelManager พร้อมใช้งาน (fallback ไป conversationService ถ้าไม่มี)
            if (! $this->channelManager && ! $this->conversationService) {
                Log::error('ChannelManager และ ConversationService ไม่พร้อม');
                $this->facebookService->sendMessage(
                    $senderId,
                    "🔮 สวัสดีค่ะ\n\nระบบกำลังเตรียมพร้อมอยู่ค่ะ กรุณาลองพิมพ์มาใหม่ในอีกสักครู่นะคะ 🙏✨"
                );

                return;
            }

            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile พร้อม fallback กรณี API ล้มเหลว
            $userProfile = $this->facebookService->getUserProfile($senderId);
            if (! is_array($userProfile)) {
                $userProfile = [
                    'name' => 'คุณ',
                    'id' => $senderId,
                ];
                Log::info('Facebook: ดึงโปรไฟล์ไม่สำเร็จ ใช้ค่าเริ่มต้น', [
                    'sender_id' => $senderId,
                ]);
            }

            // ✅ ใช้ FortuneChannelManager เพื่อ routing + Rich Message response
            // ChannelManager จะเรียก conversationService->processMessage() ภายใน
            // แล้วส่ง Rich Message (Button/Generic Template) ตอบกลับอัตโนมัติ
            if ($this->channelManager) {
                $result = $this->channelManager->processMessage(
                    FortuneChannelManager::PLATFORM_FACEBOOK,
                    $senderId,
                    $messageText,
                    $userProfile
                );

                // ปิด typing indicator
                $this->facebookService->sendTypingIndicator($senderId, false);

                Log::info('Conversational Fortune (via ChannelManager): ประมวลผลสำเร็จ', [
                    'facebook_user_id' => $senderId,
                    'action' => $result['action'] ?? 'unknown',
                    'reading_id' => ($result['reading'] ?? null)?->id,
                ]);

                return;
            }

            // 🔄 Fallback: ใช้ conversationService โดยตรง (กรณี channelManager พัง)
            Log::warning('Facebook: ChannelManager ไม่พร้อม ใช้ fallback conversationService', [
                'sender_id' => $senderId,
            ]);

            // ✅ ต้องตั้ง platform ก่อน processMessage เพื่อให้ saveQuestionForAdmin() เก็บค่าถูก
            $this->conversationService->setPlatform('facebook');

            $result = $this->conversationService->processMessage($senderId, $messageText, $userProfile);

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // Fallback: ส่งภาพ Birth Chart ก่อนข้อความทำนาย (ถ้ามี)
            $chartUrl = $result['chart_image_url'] ?? null;
            if ($chartUrl) {
                try {
                    $this->facebookService->sendImage($senderId, $chartUrl);
                    usleep(500000);
                } catch (\Exception $imgErr) {
                    Log::warning('Fortune: Failed to send chart image', [
                        'error' => $imgErr->getMessage(),
                        'chart_url' => $chartUrl,
                    ]);
                }
            }

            // Fallback: ส่งข้อความกลับ
            $message = $result['message'] ?? '';
            if (! empty($message)) {
                $this->sendLongMessage($senderId, $message);
            }

            // Fallback: ส่งภาพ QR Code ชำระเงิน (ถ้ามี)
            $paymentQrUrl = $result['payment_qr_url'] ?? null;
            if ($paymentQrUrl) {
                try {
                    usleep(300000);
                    $this->facebookService->sendImage($senderId, $paymentQrUrl);
                } catch (\Exception $qrErr) {
                    Log::warning('Fortune: ส่งภาพ QR Code ไม่สำเร็จ', [
                        'error' => $qrErr->getMessage(),
                    ]);
                }
            }

            // Fallback: ส่ง Quick Replies
            $actionsWithQuickReplies = [
                'awaiting_confirmation', 'basic_done', 'check_remaining',
                'collecting_questions', 'need_more_questions', 'retry_question',
                'ai_limit', 'declined', 'payment_expired', 'completed',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty',
            ];
            if (! empty($result['show_quick_replies']) || in_array($result['action'] ?? '', $actionsWithQuickReplies)) {
                $this->sendConversationQuickReplies($senderId, $result['action']);
            }

            Log::info('Conversational Fortune (fallback): ประมวลผลสำเร็จ', [
                'facebook_user_id' => $senderId,
                'action' => $result['action'],
                'reading_id' => $result['reading']?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Conversational Fortune Error: '.$e->getMessage(), [
                'facebook_user_id' => $senderId,
                'message' => $messageText,
                'error_class' => get_class($e),
                'trace' => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);

            try {
                $this->facebookService->sendTypingIndicator($senderId, false);
            } catch (\Exception $ignored) {
            }

            // ส่งข้อความที่เป็นมิตรกับผู้ใช้ แทนที่จะบอกว่า "ผิดพลาด"
            try {
                $this->facebookService->sendMessage(
                    $senderId,
                    "🔮 สวัสดีค่ะ\n\n".
                    "ตอนนี้ระบบกำลังปรับปรุงชั่วคราวค่ะ\n".
                    "กรุณาลองพิมพ์มาใหม่อีกครั้งนะคะ 🙏\n\n".
                    "💡 ลองพิมพ์ 'เช็คสิทธิ์' เพื่อดูสิทธิ์ดูดวงฟรี\n".
                    'หรือพิมพ์คำถามใหม่ได้เลยค่ะ ✨'
                );
            } catch (\Exception $sendError) {
                Log::error('ส่งข้อความ error response ไม่สำเร็จ: '.$sendError->getMessage());
            }
        }
    }

    /**
     * ส่งข้อความยาวโดยแบ่งเป็นหลายข้อความ
     */
    protected function sendLongMessage(string $senderId, string $message): void
    {
        // Facebook Messenger รองรับข้อความยาวสูงสุด 2000 ตัวอักษร
        $maxLength = 1800;

        if (mb_strlen($message) <= $maxLength) {
            $result = $this->facebookService->sendMessage($senderId, $message);
            if (! $result) {
                Log::error('❌ sendLongMessage: ส่งข้อความล้มเหลว', [
                    'recipient' => $senderId,
                    'message_length' => mb_strlen($message),
                ]);
            }

            return;
        }

        // แบ่งข้อความตาม paragraph หรือ ═══
        $parts = preg_split('/(?=═══════════════════════)/', $message);

        $currentMessage = '';
        $chunkIndex = 0;
        foreach ($parts as $part) {
            if (mb_strlen($currentMessage.$part) > $maxLength && ! empty($currentMessage)) {
                $chunkResult = $this->facebookService->sendMessage($senderId, trim($currentMessage));
                if (! $chunkResult) {
                    Log::error('❌ sendLongMessage: ส่งข้อความส่วนที่ '.($chunkIndex + 1).' ล้มเหลว', [
                        'recipient' => $senderId,
                        'chunk_length' => mb_strlen($currentMessage),
                    ]);
                }
                $chunkIndex++;
                usleep(300000); // รอ 300ms ระหว่างข้อความ
                $currentMessage = $part;
            } else {
                $currentMessage .= $part;
            }
        }

        if (! empty($currentMessage)) {
            $chunkResult = $this->facebookService->sendMessage($senderId, trim($currentMessage));
            if (! $chunkResult) {
                Log::error('❌ sendLongMessage: ส่งข้อความส่วนสุดท้ายล้มเหลว', [
                    'recipient' => $senderId,
                    'chunk_length' => mb_strlen($currentMessage),
                ]);
            }
        }
    }

    /**
     * ส่ง Quick Replies ตาม action
     *
     * ถ้า user มีบิลดูดวงที่ชำระเงินแล้ววันนี้ → แสดงปุ่ม "📜 คำทำนายล่าสุด" เพิ่ม
     */
    protected function sendConversationQuickReplies(string $senderId, string $action): void
    {
        $quickReplies = match ($action) {
            'awaiting_confirmation' => [
                ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'FORTUNE_LOVE'],
                ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'FORTUNE_WORK'],
                ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'FORTUNE_MONEY'],
                ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'FORTUNE_HEALTH'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงรวม', 'payload' => 'FORTUNE_OVERVIEW'],
            ],
            'basic_done' => [
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'DEEP_READING_ACCEPT'],
                ['content_type' => 'text', 'title' => '❌ ไม่ต้องค่ะ', 'payload' => 'DEEP_READING_NO'],
                ['content_type' => 'text', 'title' => '💕 ถามเรื่องรัก', 'payload' => 'FORTUNE_LOVE'],
                ['content_type' => 'text', 'title' => '💼 ถามเรื่องงาน', 'payload' => 'FORTUNE_WORK'],
                ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
            ],
            'check_remaining' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
            ],
            'collecting_questions', 'need_more_questions', 'retry_question' => [
                ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'QUESTION_LOVE'],
                ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'QUESTION_WORK'],
                ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'QUESTION_MONEY'],
                ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'QUESTION_HEALTH'],
                ['content_type' => 'text', 'title' => '✏️ พิมพ์เอง', 'payload' => 'QUESTION_CUSTOM'],
            ],
            'ai_limit', 'payment_expired' => [
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ],
            'declined', 'completed' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
            ],
            'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '📊 เช็คสิทธิ์', 'payload' => 'CHECK_REMAINING'],
            ],
            default => null,
        };

        // เพิ่มปุ่ม "📜 คำทำนายล่าสุด" ถ้า user มีบิลที่จ่ายวันนี้
        // แสดงเฉพาะ actions ที่ไม่ได้อยู่กลาง flow (ไม่ใส่ตอนเก็บคำถาม/รอชำระ)
        if ($quickReplies) {
            $showViewLastButton = in_array($action, [
                'awaiting_confirmation', 'basic_done', 'check_remaining',
                'ai_limit', 'payment_expired', 'declined', 'completed',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing',
                'view_reading_empty',
            ]);

            if ($showViewLastButton && $this->hasTodayPaidReading($senderId)) {
                // Facebook Quick Replies รองรับสูงสุด 13 ปุ่ม — ต่อท้ายได้เลย
                $quickReplies[] = ['content_type' => 'text', 'title' => '📜 คำทำนายล่าสุด', 'payload' => 'VIEW_LAST_READING'];
            }
        }

        if ($quickReplies) {
            // ส่ง Quick Replies แยก
            usleep(500000); // รอ 500ms
            $this->facebookService->sendQuickReplies(
                $senderId,
                'เลือกได้เลยค่ะ 👇',
                $quickReplies
            );
        }
    }

    /**
     * ตรวจสอบว่า user มีบิลดูดวงที่ชำระเงินแล้ววันนี้หรือไม่
     *
     * ใช้สำหรับแสดง/ซ่อนปุ่ม "📜 คำทำนายล่าสุด"
     */
    protected function hasTodayPaidReading(string $facebookUserId): bool
    {
        return FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where('is_paid', true)
            ->whereDate('paid_at', today())
            ->exists();
    }

    /**
     * ทำนายดวง (รองรับพื้นฐานและเชิงลึก)
     *
     * เมื่อ queue driver ไม่ใช่ 'sync' จะ dispatch job แบบ async
     * เพื่อไม่ให้ webhook response ช้า (Facebook timeout 20 วินาที)
     * ถ้าเป็น 'sync' จะประมวลผลแบบ inline ทันที
     *
     * @param  array  $data  ข้อมูลจาก Facebook
     * @param  array  $questions  คำถามที่แยกแล้ว
     * @param  bool  $isComment  เป็นคอมเมนต์หรือ direct message
     * @param  bool  $isDeep  เป็นคำทำนายเชิงลึกหรือไม่
     * @param  string|null  $userImageUrl  URL รูปที่ผู้ใช้ส่งมา
     * @param  string|null  $birthDate  วันเกิดรูปแบบ Y-m-d
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
     * @param  array  $data  ข้อมูลจาก Facebook
     * @param  array  $questions  คำถาม
     * @param  bool  $isComment  เป็นคอมเมนต์
     * @param  bool  $isDeep  เป็นเชิงลึก
     * @param  string|null  $userImageUrl  รูปจากผู้ใช้
     * @param  string  $fromId  Facebook User ID
     * @param  string|null  $fromName  ชื่อผู้ใช้
     * @param  string|null  $birthDate  วันเกิดรูปแบบ Y-m-d
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
        if (! $isComment) {
            $this->facebookService->sendTypingIndicator($fromId);
        }

        $userProfile = $this->facebookService->getUserProfile($fromId);
        // ✅ ป้องกัน null userProfile
        if (! is_array($userProfile)) {
            $userProfile = [
                'name' => $fromName ?? 'คุณ',
                'id' => $fromId,
            ];
        }
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
            if (! $isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            // ส่งคำทำนายกลับ (รองรับรูปภาพ + message splitting)
            if ($this->facebookService->sendFortuneTelling($reading, $aiResponse['response'])) {
                $reading->markAsResponded();
            }

            // หลังส่งคำทำนายเชิงลึกฟรี ส่ง quick replies แนะนำจ่ายเงิน/สมัครสมาชิก
            if ($isDeep && $this->settings->isTryBeforeBuyEnabled() && ! $isComment) {
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
            Log::error('เกิดข้อผิดพลาดในการทำนาย: '.$e->getMessage(), [
                'from_id' => $fromId,
                'is_deep' => $isDeep,
                'trace' => $e->getTraceAsString(),
            ]);

            // ปิด typing indicator
            if (! $isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            try {
                $this->facebookService->sendMessage(
                    $fromId,
                    "🔮 สวัสดีค่ะ\n\nตอนนี้ระบบกำลังปรับปรุงชั่วคราวค่ะ\nกรุณาลองพิมพ์มาใหม่อีกครั้งนะคะ 🙏\n\n💡 พิมพ์ 'เช็คสิทธิ์' เพื่อดูสิทธิ์ดูดวงฟรี\nหรือพิมพ์คำถามใหม่ได้เลยค่ะ ✨"
                );
            } catch (\Exception $sendError) {
                Log::error('ส่งข้อความ error ไม่สำเร็จ: '.$sendError->getMessage());
            }
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
            'DEEP_READING_NO' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),

            // Quick Replies หมวดคำทำนาย (ดูดวงฟรี)
            'FORTUNE_LOVE' => $this->processConversationalMessage($senderId, 'ดูดวงความรัก เนื้อคู่ คู่ครอง'),
            'FORTUNE_WORK' => $this->processConversationalMessage($senderId, 'ดูดวงการงาน อาชีพ เลื่อนตำแหน่ง'),
            'FORTUNE_MONEY' => $this->processConversationalMessage($senderId, 'ดูดวงการเงิน รายได้ การลงทุน'),
            'FORTUNE_HEALTH' => $this->processConversationalMessage($senderId, 'ดูดวงสุขภาพ สิ่งที่ต้องระวัง'),
            'FORTUNE_OVERVIEW' => $this->processConversationalMessage($senderId, 'ดูดวงภาพรวมทุกด้าน ความรัก การงาน การเงิน สุขภาพ'),

            // Quick Replies สำหรับเลือกหมวดคำถาม (ดูดวงละเอียด — เก็บทีละข้อ)
            'QUESTION_LOVE' => $this->handleCategoryQuestion($senderId, 'love'),
            'QUESTION_WORK' => $this->handleCategoryQuestion($senderId, 'work'),
            'QUESTION_MONEY' => $this->handleCategoryQuestion($senderId, 'money'),
            'QUESTION_HEALTH' => $this->handleCategoryQuestion($senderId, 'health'),
            'QUESTION_CUSTOM' => $this->sendCustomQuestionPrompt($senderId),

            // Quick Reply ดูคำทำนายล่าสุด
            'VIEW_LAST_READING' => $this->processConversationalMessage($senderId, 'ดูคำทำนาย'),

            // ✅ LINE Invite + Affiliate Share (จาก Rich Templates)
            'LINE_ADD_FRIEND' => $this->handleLineAddFriend($senderId),
            'LINE_INVITE' => $this->handleLineAddFriend($senderId),
            'AFFILIATE_SHARE' => $this->processConversationalMessage($senderId, 'แชร์'),

            // ✅ ปุ่มจาก Button Templates
            'REPORT_PAYMENT' => $this->processConversationalMessage($senderId, 'แจ้งชำระเงิน'),
            'CANCEL_PAYMENT' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            'SHOW_BANK_ACCOUNT' => $this->processConversationalMessage($senderId, 'แสดงบัญชี'),
            'CANCEL_DEEP' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),
            'NEW_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'VIEW_READING' => $this->processConversationalMessage($senderId, 'ดูคำทำนาย'),

            // ✅ Phase A quick reply payloads (จาก FortuneChannelManager getFacebookFallbackQuickReplies)
            'TALK_HUMAN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            'START_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'DEEP_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวงละเอียด'),
            'CHECK_STATUS' => $this->processConversationalMessage($senderId, 'เช็คสถานะ'),
            'RESTART' => $this->processConversationalMessage($senderId, 'เริ่มใหม่'),
            'CANCEL' => $this->processConversationalMessage($senderId, 'ยกเลิก'),

            // ✅ Post-reading payloads (จบคำทำนาย → เชิญแชร์/ชวนเพื่อน)
            'VIEW_LATER' => $this->facebookService->sendMessage($senderId, "✨ ได้เลย! เมื่อพร้อมดูแล้ว พิมพ์ 'ดูคำทำนาย' ได้ทุกเมื่อ 🔮"),
            'FORTUNE_EARN_INFO' => $this->handleFortuneEarnInfo($senderId),
            'SHARE_PAGE' => $this->handleSharePage($senderId),

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
     * จัดการเมื่อ user กดปุ่มเลือกหมวดคำถาม (Quick Reply)
     *
     * ดึงคำถามสำเร็จรูปจาก CATEGORY_QUESTION_MAP แล้วส่งเข้า conversation
     * เหมือนกับว่า user พิมพ์คำถามเอง
     *
     * @param  string  $senderId  Facebook user ID
     * @param  string  $category  หมวดคำถาม (love, work, money, health)
     */
    protected function handleCategoryQuestion(string $senderId, string $category): void
    {
        try {
            // ดึง active reading เพื่อเช็คคำถามที่เก็บไปแล้ว
            $reading = FortuneReading::where('facebook_user_id', $senderId)
                ->where('conversation_status', FortuneReading::STATUS_COLLECTING_QUESTIONS)
                ->latest()
                ->first();

            $existingQuestions = [];
            if ($reading) {
                $existingQuestions = $reading->getCollectedQuestions();
            }

            // สร้างคำถามจากหมวดที่เลือก (ไม่ซ้ำกับที่เก็บไปแล้ว)
            $question = $this->conversationService->getQuestionForCategory($category, $existingQuestions);

            // ส่งเข้า processConversationalMessage เหมือน user พิมพ์เอง
            $this->processConversationalMessage($senderId, $question);
        } catch (\Exception $e) {
            Log::error('handleCategoryQuestion error: '.$e->getMessage(), [
                'sender_id' => $senderId,
                'category' => $category,
            ]);
            // Fallback: ส่งข้อความแจ้ง
            $this->facebookService->sendMessage($senderId, "🔮 ขอโทษค่ะ ลองกดเลือกอีกครั้งนะคะ ✨");
        }
    }

    /**
     * ส่ง prompt ให้ user พิมพ์คำถามเอง
     *
     * เมื่อ user กดปุ่ม "✏️ พิมพ์เอง" จะส่งข้อความตัวอย่างให้
     *
     * @param  string  $senderId  Facebook user ID
     */
    protected function sendCustomQuestionPrompt(string $senderId): void
    {
        $message = "✏️ *พิมพ์คำถามที่ต้องการถามได้เลยค่ะ*\n\n";
        $message .= "💡 ตัวอย่าง:\n";
        $message .= "• ปีนี้จะมีคู่ครองไหม\n";
        $message .= "• ควรเปลี่ยนงานตอนนี้ไหม\n";
        $message .= "• การเงินช่วงนี้จะเป็นอย่างไร\n\n";
        $message .= 'พิมพ์คำถามมาได้เลยค่ะ 👇';

        $this->facebookService->sendMessage($senderId, $message);
    }

    /**
     * ส่งคำแนะนำการใช้งานพร้อม Quick Reply buttons
     */
    protected function sendHelpMessage(string $userId): void
    {
        // ✅ ใช้ Rich Welcome Template แทนข้อความธรรมดา
        try {
            $richService = new \App\Services\FacebookRichMessageService($this->settings);
            $helpTemplate = $richService->buildWelcomeTemplate('คุณ');
            $helpQuickReplies = $richService->getQuickRepliesForAction('help');

            if ($helpTemplate && ! empty($helpTemplate['elements'])) {
                $this->facebookService->sendTemplateWithQuickReplies(
                    $userId,
                    [
                        'template_type' => 'generic',
                        'elements' => $helpTemplate['elements'],
                    ],
                    $helpQuickReplies
                );

                return;
            }
        } catch (\Exception $e) {
            Log::warning('sendHelpMessage: Rich Template ล้มเหลว ใช้ fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: ข้อความธรรมดา
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

    /**
     * จัดการเมื่อ user กดปุ่ม "เพิ่มเพื่อน LINE"
     *
     * ส่ง LINE Add-Friend Template พร้อม URL + Quick Replies
     * ถ้าไม่มี LINE configured จะส่งข้อความทั่วไปแทน
     *
     * @param  string  $senderId  Facebook User ID
     */
    /**
     * ส่งข้อมูลการชวนเพื่อน/รับรายได้ พร้อมลิงก์ไปหน้าศึกษา
     */
    protected function handleFortuneEarnInfo(string $senderId): void
    {
        $appUrl = config('app.url', 'https://main.thaiprompt.online');
        $recruitUrl = $appUrl.'/auth/line?redirect=/user/fortune-referral/recruit';
        $wealthUrl = $appUrl.'/wealth-guide';

        $this->facebookService->sendButtonTemplate($senderId, [
            'template_type' => 'button',
            'text' => "📢 เชิญเพื่อนมาดูดวง — ได้รายได้จริง!\n\n"
                . "💰 ทุกครั้งที่เพื่อนคุณดูดวงเชิงลึก คุณจะได้ค่าแนะนำเข้า Wallet ทันที\n"
                . "🔗 กดศึกษาวิธีและรับลิงก์เชิญเพื่อนได้ด้านล่าง",
            'buttons' => [
                ['type' => 'web_url', 'title' => '🔗 รับลิงก์เชิญเพื่อน', 'url' => $recruitUrl],
                ['type' => 'web_url', 'title' => '📚 ศึกษาวิธีสร้างรายได้', 'url' => $wealthUrl],
                ['type' => 'postback', 'title' => '🔮 ดูดวงต่อ', 'payload' => 'FORTUNE_BASIC'],
            ],
        ]);
    }

    /**
     * แชร์เพจ Facebook ให้เพื่อน
     */
    protected function handleSharePage(string $senderId): void
    {
        $pageId = $this->settings->facebook_page_id ?? null;
        $pageUrl = $pageId ? "https://www.facebook.com/{$pageId}" : config('app.url');

        $this->facebookService->sendButtonTemplate($senderId, [
            'template_type' => 'button',
            'text' => "🙏 ขอบคุณที่ใช้บริการ!\n\n"
                . "📢 แชร์เพจนี้ให้เพื่อน — ทุกครั้งที่เพื่อนมาดูดวงเชิงลึก คุณได้ค่าแนะนำเข้า Wallet",
            'buttons' => [
                ['type' => 'web_url', 'title' => '📤 แชร์เพจให้เพื่อน', 'url' => $pageUrl],
                ['type' => 'postback', 'title' => '📢 ดูวิธีรับรายได้', 'payload' => 'FORTUNE_EARN_INFO'],
                ['type' => 'postback', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'FORTUNE_BASIC'],
            ],
        ]);
    }

    protected function handleLineAddFriend(string $senderId): void
    {
        try {
            $richService = new \App\Services\FacebookRichMessageService($this->settings);
            $lineUrl = $richService->getLineAddFriendUrl();

            if ($lineUrl) {
                // ส่ง LINE Invite Template
                $lineTemplate = $richService->buildLineInviteTemplate();
                if ($lineTemplate) {
                    $this->facebookService->sendButtonTemplate($senderId, $lineTemplate);
                } else {
                    // Fallback: ส่ง URL โดยตรง
                    $this->facebookService->sendMessage(
                        $senderId,
                        "💚 เพิ่มเพื่อน LINE เพื่อดูดวงแบบสวยงาม!\n\n" .
                        "👉 {$lineUrl}\n\n" .
                        "✨ ดูดวง Flex Message สวยๆ ได้ที่ LINE ค่ะ"
                    );
                }
            } else {
                // ไม่มี LINE configured — แนะนำดูดวงผ่าน Facebook ต่อ
                $quickReplies = $richService->getQuickRepliesForAction('declined');
                $this->facebookService->sendQuickReplies(
                    $senderId,
                    "🔮 ดูดวงต่อได้เลยค่ะ!\n\nพิมพ์คำถามมาได้เลย หรือเลือกจากปุ่มด้านล่าง 👇",
                    $quickReplies
                );
            }

            Log::info('💚 LINE Add Friend: ส่ง invite สำเร็จ', [
                'sender_id' => $senderId,
                'has_line_url' => ! empty($lineUrl),
            ]);
        } catch (\Exception $e) {
            Log::error('handleLineAddFriend error: ' . $e->getMessage(), [
                'sender_id' => $senderId,
            ]);
            $this->facebookService->sendMessage(
                $senderId,
                "🔮 ขอโทษค่ะ ลองพิมพ์ 'ดูดวง' เพื่อเริ่มต้นใหม่นะคะ ✨"
            );
        }
    }
}
