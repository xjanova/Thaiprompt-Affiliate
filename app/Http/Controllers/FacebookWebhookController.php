<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFortuneTelling;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    protected $settings;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->facebookService = new FacebookWebhookService($this->settings);
        $this->aiService = new FortuneAIService($this->settings);
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
                return response()->json(['status' => 'ok']);
            }

            $data = $request->all();
            Log::info('Received Facebook Webhook', [
                'object' => $data['object'] ?? 'unknown',
            ]);

            if (($data['object'] ?? '') !== 'page') {
                return response()->json(['status' => 'ok']);
            }

            foreach ($data['entry'] ?? [] as $entry) {
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
            if (isset($messaging['message'])) {
                $this->processMessage($messaging);
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
            return;
        }

        // ตรวจสอบ limit ตามประเภทคำขอ
        if ($isDeepRequest && $this->settings->isDeepReadingEnabled()) {
            $deepLimitCheck = $this->facebookService->checkDeepFreeLimit($fromId);

            if ($deepLimitCheck['has_reached_limit']) {
                $this->sendDeepLimitMessage($comment);
                return;
            }

            $this->processFortuneTelling($comment, $questions, true, true);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($fromId);

            if ($limitCheck['has_reached_limit']) {
                $this->sendLimitMessage($comment);
                return;
            }

            $this->processFortuneTelling($comment, $questions, true, false);
        }
    }

    /**
     * ประมวลผล direct message (Messenger)
     *
     * รองรับ:
     * - ข้อความ text
     * - รูปภาพ (image attachment)
     * - Quick reply payloads
     */
    protected function processMessage(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;

        if (empty($senderId)) {
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
                    "📸 ได้รับรูปภาพแล้วค่ะ\n\nกรุณาพิมพ์ 'ดูดวง' หรือ 'ดูดวงละเอียด' ตามด้วยคำถาม เพื่อเริ่มดูดวง\n\nตัวอย่าง: ดูดวงละเอียด เรื่องความรัก"
                );
                return;
            }
        }

        // ตรวจสอบคำถาม
        $isDeepRequest = $this->facebookService->isDeepReadingRequest($messageText);
        $questions = $this->facebookService->parseQuestions($messageText);

        if (empty($questions)) {
            $this->sendHelpMessage($senderId);
            return;
        }

        // ตรวจสอบ limit ตามประเภทคำขอ
        if ($isDeepRequest && $this->settings->isDeepReadingEnabled()) {
            $deepLimitCheck = $this->facebookService->checkDeepFreeLimit($senderId);

            if ($deepLimitCheck['has_reached_limit']) {
                $limitMsg = $this->facebookService->getDeepLimitExceededMessage();
                $this->facebookService->sendMessage($senderId, $limitMsg);
                return;
            }

            $this->processFortuneTelling($messaging, $questions, false, true, $userImageUrl);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($senderId);

            if ($limitCheck['has_reached_limit']) {
                $this->facebookService->sendMessage(
                    $senderId,
                    $this->facebookService->getLimitExceededMessage()
                );
                return;
            }

            $this->processFortuneTelling($messaging, $questions, false, false, $userImageUrl);
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
     */
    protected function processFortuneTelling(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep = false,
        ?string $userImageUrl = null
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
        $this->processFortuneSync($data, $questions, $isComment, $isDeep, $userImageUrl, $fromId, $fromName);
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
     */
    protected function processFortuneSync(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep,
        ?string $userImageUrl,
        string $fromId,
        ?string $fromName
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
                $readingType
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
            'FORTUNE_BASIC' => $this->processFortuneTelling(
                ['sender' => ['id' => $senderId]],
                ['ดูดวงทั่วไป ความรัก การเงิน การงาน สุขภาพ'],
                false,
                false
            ),
            'FORTUNE_DEEP' => $this->processFortuneTelling(
                ['sender' => ['id' => $senderId]],
                ['ดูดวงทั่วไป ความรัก การเงิน การงาน สุขภาพ'],
                false,
                true
            ),
            'SUBSCRIBE' => $this->facebookService->sendMessage(
                $senderId,
                $this->settings->getSubscriptionMessage()
            ),
            'HELP' => $this->sendHelpMessage($senderId),
            default => $this->sendHelpMessage($senderId),
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
            ['title' => 'ดูดวง', 'payload' => 'FORTUNE_BASIC'],
        ];

        if ($this->settings->isDeepReadingEnabled()) {
            $quickReplies[] = ['title' => 'ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'];
        }

        $this->facebookService->sendQuickReplies($userId, $message, $quickReplies);
    }
}
