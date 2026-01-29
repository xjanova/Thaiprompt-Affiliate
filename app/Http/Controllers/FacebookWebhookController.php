<?php

namespace App\Http\Controllers;

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
 * รองรับการรับคอมเมนต์และส่งคำทำนาย
 * รองรับระบบ Freemium: คำทำนายพื้นฐาน + เชิงลึก
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

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * รับ webhook events (POST request จาก Facebook)
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            if (!$this->settings->isServiceEnabled()) {
                return response()->json(['status' => 'ok']);
            }

            $data = $request->all();
            Log::info('Received Facebook Webhook', ['data' => $data]);

            if ($data['object'] !== 'page') {
                return response()->json(['status' => 'ok']);
            }

            foreach ($data['entry'] ?? [] as $entry) {
                $this->processEntry($entry);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Facebook Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * ประมวลผล entry จาก webhook
     */
    protected function processEntry(array $entry): void
    {
        foreach ($entry['changes'] ?? [] as $change) {
            if ($change['field'] === 'feed' && $change['value']['item'] === 'comment') {
                $this->processComment($change['value']);
            }
        }

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
                // ครบจำนวนฟรีเชิงลึก - แนะนำจ่ายเงิน/สมัครสมาชิก
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
     * ประมวลผล direct message
     */
    protected function processMessage(array $messaging): void
    {
        $messageText = $messaging['message']['text'] ?? '';
        $senderId = $messaging['sender']['id'];

        // ตรวจสอบว่าเป็นคำขอดูดวงเชิงลึกหรือพื้นฐาน
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
                // ครบจำนวนฟรีเชิงลึก - แนะนำจ่ายเงิน/สมัครสมาชิก
                $limitMsg = $this->facebookService->getDeepLimitExceededMessage();
                $this->facebookService->sendMessage($senderId, $limitMsg);
                return;
            }

            $this->processFortuneTelling($messaging, $questions, false, true);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($senderId);

            if ($limitCheck['has_reached_limit']) {
                $this->facebookService->sendMessage(
                    $senderId,
                    $this->facebookService->getLimitExceededMessage()
                );
                return;
            }

            $this->processFortuneTelling($messaging, $questions, false, false);
        }
    }

    /**
     * ทำนายดวง (รองรับพื้นฐานและเชิงลึก)
     *
     * @param array $data ข้อมูลจาก Facebook
     * @param array $questions คำถามที่แยกแล้ว
     * @param bool $isComment เป็นคอมเมนต์หรือ direct message
     * @param bool $isDeep เป็นคำทำนายเชิงลึกหรือไม่
     */
    protected function processFortuneTelling(array $data, array $questions, bool $isComment, bool $isDeep = false): void
    {
        $fromId = $isComment ? ($data['from']['id'] ?? null) : ($data['sender']['id'] ?? null);
        $fromName = $isComment ? ($data['from']['name'] ?? null) : null;

        $userProfile = $this->facebookService->getUserProfile($fromId);
        // ดึงโพสล่าสุดเฉพาะคำทำนายเชิงลึก (เพื่อประหยัด API calls)
        $userPosts = $isDeep ? $this->facebookService->getUserPosts($fromId, 3) : null;

        try {
            // เลือก prompt template ตามระดับ
            $promptTemplate = $isDeep
                ? $this->settings->getDeepPromptTemplate()
                : $this->settings->getBasicPromptTemplate();

            $aiResponse = $this->aiService->generateFortuneTelling(
                $questions,
                $userProfile,
                $userPosts,
                $promptTemplate
            );

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
                'is_paid' => false,
            ]);

            if ($this->facebookService->sendFortuneTelling($reading, $aiResponse['response'])) {
                $reading->markAsResponded();
            }

            // หลังส่งคำทำนายเชิงลึกฟรี ส่งข้อความแนะนำจ่ายเงิน/สมัครสมาชิก
            if ($isDeep && $this->settings->isTryBeforeBuyEnabled()) {
                $tryBeforeBuyMsg = $this->settings->getTryBeforeBuyMessage();
                $this->facebookService->sendMessage($fromId, $tryBeforeBuyMsg);
            }
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการทำนาย: ' . $e->getMessage());
            $this->facebookService->sendMessage($fromId, "ขออภัยค่ะ เกิดข้อผิดพลาดในการทำนาย กรุณาลองใหม่อีกครั้ง");
        }
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (พื้นฐาน)
     */
    protected function sendLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'], $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'], $message);
        }
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (เชิงลึก)
     */
    protected function sendDeepLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getDeepLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'], $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'], $message);
        }
    }

    /**
     * ส่งคำแนะนำการใช้งาน
     */
    protected function sendHelpMessage(string $userId): void
    {
        $message = "🔮 วิธีใช้งานระบบดูดวง:\n\n";
        $message .= "📌 ดูดวงพื้นฐาน (ฟรี):\n";
        $message .= "พิมพ์: ดูดวง ตามด้วยคำถาม 1-3 ข้อ\n";
        $message .= "ตัวอย่าง: ดูดวง เรื่องความรัก, เรื่องการเงิน\n\n";

        if ($this->settings->isDeepReadingEnabled()) {
            $message .= "🌟 ดูดวงเชิงลึก (ละเอียด):\n";
            $message .= "พิมพ์: ดูดวงละเอียด ตามด้วยคำถาม\n";
            $message .= "ตัวอย่าง: ดูดวงละเอียด เรื่องความรัก\n";
            $message .= "(ฟรี {$this->settings->free_deep_per_day} ครั้ง/วัน)\n";
        }

        $this->facebookService->sendMessage($userId, $message);
    }
}
