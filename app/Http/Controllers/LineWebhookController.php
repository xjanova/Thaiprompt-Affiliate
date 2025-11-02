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

        if (strtolower($messageText) === 'info' || strtolower($messageText) === 'ข้อมูล') {
            if ($user) {
                $lineService->sendUserInfoCard($user);
            } else {
                $lineService->sendPushMessage(
                    $lineUserId,
                    'คุณยังไม่ได้ลงทะเบียนในระบบ กรุณาสมัครสมาชิกที่เว็บไซต์ของเรา'
                );
            }
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
