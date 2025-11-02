<?php

namespace App\Http\Controllers;

use App\Models\LineOaSetting;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    /**
     * Handle LINE webhook events
     */
    public function handle(Request $request)
    {
        $settings = LineOaSetting::getActive();

        if (!$settings) {
            Log::warning('LINE webhook received but settings not configured');
            return response()->json(['status' => 'error', 'message' => 'Settings not configured'], 400);
        }

        // Verify webhook signature
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if (!$this->verifySignature($signature, $body, $settings->channel_secret)) {
            Log::warning('LINE webhook signature verification failed');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->handleEvent($event, $settings);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify LINE webhook signature
     */
    private function verifySignature(string $signature, string $body, string $channelSecret): bool
    {
        $hash = hash_hmac('sha256', $body, $channelSecret, true);
        $calculatedSignature = base64_encode($hash);

        return hash_equals($calculatedSignature, $signature);
    }

    /**
     * Handle individual LINE event
     */
    private function handleEvent(array $event, LineOaSetting $settings): void
    {
        $type = $event['type'] ?? null;

        switch ($type) {
            case 'message':
                $this->handleMessageEvent($event, $settings);
                break;

            case 'follow':
                $this->handleFollowEvent($event, $settings);
                break;

            case 'unfollow':
                $this->handleUnfollowEvent($event);
                break;

            default:
                Log::info('Unhandled LINE event type', ['type' => $type]);
        }
    }

    /**
     * Handle message event
     */
    private function handleMessageEvent(array $event, LineOaSetting $settings): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $message = $event['message'] ?? [];
        $messageType = $message['type'] ?? null;
        $messageText = $message['text'] ?? null;

        if (!$lineUserId || $messageType !== 'text') {
            return;
        }

        Log::info('LINE message received', [
            'line_user_id' => $lineUserId,
            'message' => $messageText,
        ]);

        // Find user by LINE user ID
        $user = User::where('line_user_id', $lineUserId)->first();

        // Handle commands
        $lineService = app(LineService::class);

        // Check for special commands first
        $command = strtolower(trim($messageText));

        if ($command === 'info' || $command === 'ข้อมูล') {
            if ($user) {
                $lineService->sendUserInfoCard($user);
            } else {
                $lineService->sendPushMessage(
                    $lineUserId,
                    'คุณยังไม่ได้ลงทะเบียนในระบบ กรุณาสมัครสมาชิกที่เว็บไซต์ของเรา'
                );
            }
            return;
        }

        // Check if AI Bot is enabled
        $aiSetting = \App\Models\LineBotAiSetting::getActive();

        if ($aiSetting && $aiSetting->is_active) {
            $this->handleAiChat($lineUserId, $messageText, $user, $aiSetting);
        } else {
            // Default response when AI is not enabled
            $lineService->sendPushMessage(
                $lineUserId,
                "ขอบคุณสำหรับข้อความของคุณ 😊\n\nพิมพ์ 'info' หรือ 'ข้อมูล' เพื่อดูข้อมูลบัญชีของคุณ"
            );
        }
    }

    /**
     * Handle AI chat
     */
    private function handleAiChat(
        string $lineUserId,
        string $messageText,
        ?User $user,
        \App\Models\LineBotAiSetting $aiSetting
    ): void {
        try {
            // Get or create conversation
            $conversation = \App\Models\LineBotConversation::where('line_user_id', $lineUserId)
                ->where('status', 'active')
                ->first();

            if (!$conversation) {
                $conversation = \App\Models\LineBotConversation::create([
                    'line_user_id' => $lineUserId,
                    'user_id' => $user?->id,
                    'ai_setting_id' => $aiSetting->id,
                    'status' => 'active',
                ]);
            }

            // Save user message
            $conversation->addMessage('user', $messageText);

            // Generate AI response
            $aiService = new \App\Services\LineBotAiService($aiSetting);
            $response = $aiService->generateResponse($messageText, $conversation);

            // Save AI response
            $conversation->addMessage('assistant', $response['message'], [
                'tokens_used' => $response['tokens_used'],
                'response_time' => $response['response_time'],
                'provider' => $response['provider'],
                'is_ai_generated' => true,
            ]);

            // Send response to user
            $lineService = app(LineService::class);
            $lineService->sendPushMessage($lineUserId, $response['message']);

        } catch (\Exception $e) {
            Log::error('AI chat error', [
                'error' => $e->getMessage(),
                'line_user_id' => $lineUserId,
            ]);

            // Send fallback message
            $fallbackMessage = $aiSetting->fallback_message;
            $lineService = app(LineService::class);
            $lineService->sendPushMessage($lineUserId, $fallbackMessage);
        }
    }

    /**
     * Handle follow event (user adds bot as friend)
     */
    private function handleFollowEvent(array $event, LineOaSetting $settings): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        Log::info('LINE follow event', ['line_user_id' => $lineUserId]);

        // Check if user exists
        $user = User::where('line_user_id', $lineUserId)->first();

        $lineService = app(LineService::class);

        if ($user) {
            // Existing user followed again
            $lineService->sendPushMessage(
                $lineUserId,
                "ยินดีต้อนรับกลับมา {$user->name}! 🎉"
            );
        } else {
            // New follower
            $lineService->sendPushMessage(
                $lineUserId,
                $settings->welcome_message
            );
        }
    }

    /**
     * Handle unfollow event (user removes bot)
     */
    private function handleUnfollowEvent(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        Log::info('LINE unfollow event', ['line_user_id' => $lineUserId]);

        // Optionally mark user as unfollowed
        // User::where('line_user_id', $lineUserId)->update(['line_verified' => false]);
    }
}
