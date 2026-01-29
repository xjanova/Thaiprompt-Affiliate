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
        $questions = $this->facebookService->parseQuestions($message);

        if (empty($questions)) {
            return;
        }

        $fromId = $comment['from']['id'] ?? null;
        $limitCheck = $this->facebookService->checkFreeLimit($fromId);

        if ($limitCheck['has_reached_limit']) {
            $this->sendLimitMessage($comment);
            return;
        }

        $this->processFortuneTelling($comment, $questions, true);
    }

    /**
     * ประมวลผล direct message
     */
    protected function processMessage(array $messaging): void
    {
        $messageText = $messaging['message']['text'] ?? '';
        $questions = $this->facebookService->parseQuestions($messageText);

        if (empty($questions)) {
            $this->sendHelpMessage($messaging['sender']['id']);
            return;
        }

        $limitCheck = $this->facebookService->checkFreeLimit($messaging['sender']['id']);

        if ($limitCheck['has_reached_limit']) {
            $this->facebookService->sendMessage(
                $messaging['sender']['id'],
                $this->facebookService->getLimitExceededMessage()
            );
            return;
        }

        $this->processFortuneTelling($messaging, $questions, false);
    }

    /**
     * ทำนายดวง
     */
    protected function processFortuneTelling(array $data, array $questions, bool $isComment): void
    {
        $fromId = $isComment ? ($data['from']['id'] ?? null) : ($data['sender']['id'] ?? null);
        $fromName = $isComment ? ($data['from']['name'] ?? null) : null;

        $userProfile = $this->facebookService->getUserProfile($fromId);
        $userPosts = $this->facebookService->getUserPosts($fromId, 3);

        try {
            $aiResponse = $this->aiService->generateFortuneTelling($questions, $userProfile, $userPosts);

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
            ]);

            if ($this->facebookService->sendFortuneTelling($reading, $aiResponse['response'])) {
                $reading->markAsResponded();
            }
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการทำนาย: ' . $e->getMessage());
            $this->facebookService->sendMessage($fromId, "ขออภัยค่ะ เกิดข้อผิดพลาดในการทำนาย");
        }
    }

    /**
     * ส่งข้อความครบจำนวนฟรี
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
     * ส่งคำแนะนำการใช้งาน
     */
    protected function sendHelpMessage(string $userId): void
    {
        $message = "🔮 วิธีใช้งานระบบดูดวง:\n\n";
        $message .= "พิมพ์: ดูดวง ตามด้วยคำถาม 1-3 ข้อ\n\n";
        $message .= "ตัวอย่าง:\nดูดวง เรื่องความรัก, เรื่องการเงิน, เรื่องสุขภาพ";

        $this->facebookService->sendMessage($userId, $message);
    }
}
