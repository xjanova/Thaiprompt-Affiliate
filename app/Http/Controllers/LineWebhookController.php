<?php

namespace App\Http\Controllers;

use App\Models\LineOaSetting;
use App\Models\User;
use App\Models\AiBotProfile;
use App\Services\LineService;
use App\Services\MlmProspectService;
use App\Services\LineSignupService;
use App\Services\LineKycService;
use App\Services\LineHybridBotService;
use App\Services\LineVoiceMessageService;
use App\Services\AI\ConversationManager;
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
     *
     * รองรับการรับข้อความทั้ง text, image, และ audio
     * - Text: commands (KYC, info, reset) และ AI chat
     * - Image: KYC verification (ID card และ Selfie)
     * - Audio: Speech-to-Text แล้วส่งต่อไปยัง AI Bot
     */
    private function handleMessageEvent(array $event, LineOaSetting $settings): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $message = $event['message'] ?? [];
        $messageType = $message['type'] ?? null;
        $messageText = $message['text'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$lineUserId) {
            return;
        }

        Log::info('LINE message received', [
            'line_user_id' => $lineUserId,
            'type' => $messageType,
            'message' => $messageText,
        ]);

        // Find user by LINE user ID
        $user = User::where('line_user_id', $lineUserId)->first();

        // ✅ Handle IMAGE messages for KYC
        if ($messageType === 'image') {
            $this->handleKycImageMessage($messageId, $lineUserId, $user);
            return;
        }

        // ✅ Handle AUDIO/VOICE messages
        if ($messageType === 'audio') {
            $this->handleVoiceMessage($messageId, $lineUserId, $user);
            return;
        }

        // ⚠️ Only text messages from here on
        if ($messageType !== 'text') {
            Log::info('Unhandled message type', ['type' => $messageType]);
            return;
        }

        // Step 1: Check if user is in signup process
        $prospectService = app(MlmProspectService::class);
        $prospect = $prospectService->getProspectByLineUserId($lineUserId);

        if ($prospect && in_array($prospect->status, ['pending', 'in_progress'])) {
            // ยังอยู่ในกระบวนการสมัครสมาชิก → ตอบไปยัง signup flow
            $signupService = app(LineSignupService::class);
            $signupService->handleConversationMessage($prospect, $messageText);
            return;
        }

        // Step 2: Use Hybrid Bot (Keyword matching + AI fallback)
        $hybridBot = app(LineHybridBotService::class);
        $aiBot = AiBotProfile::where('is_active', true)
            ->first(); // ใช้ bot แรกที่ active

        $result = $hybridBot->processMessage($lineUserId, $messageText, $user, $aiBot);

        Log::info('Hybrid Bot processed message', [
            'line_user_id' => $lineUserId,
            'result' => $result,
        ]);
    }

    /**
     * Handle AI Bot chat (New version with ConversationManager)
     */
    private function handleAiBotChat(
        string $lineUserId,
        string $messageText,
        ?User $user,
        AiBotProfile $bot
    ): void {
        try {
            // Create Conversation Manager
            $manager = new ConversationManager($bot);

            // Find or create conversation
            $conversation = $manager->findOrCreateConversation($user, $lineUserId);

            // Send message to AI and get response
            $result = $manager->sendMessage($conversation, $messageText);

            if ($result['success']) {
                // Send AI response to LINE
                $lineService = app(LineService::class);
                $lineService->sendPushMessage($lineUserId, $result['message']);

                Log::info('AI response sent to LINE', [
                    'line_user_id' => $lineUserId,
                    'bot_id' => $bot->id,
                    'tokens_used' => $result['tokens_used'],
                ]);
            } else {
                // Error occurred
                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            Log::error('AI Bot chat error', [
                'error' => $e->getMessage(),
                'line_user_id' => $lineUserId,
                'bot_id' => $bot->id,
            ]);

            // Send fallback message
            $lineService = app(LineService::class);
            $lineService->sendPushMessage(
                $lineUserId,
                "ขออภัยค่ะ ขณะนี้ระบบ AI ประสบปัญหาชั่วคราว กรุณาลองใหม่อีกครั้งในภายหลัง 🙏"
            );
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

    /**
     * Handle KYC image message from LINE
     *
     * รองรับการรับรูปภาพจาก LINE สำหรับการยืนยันตัวตน (KYC)
     * - รูปภาพแรก: บัตรประชาชน (ID Card)
     * - รูปภาพที่สอง: รูปถ่ายตัวเอง (Selfie)
     *
     * ระบบจะตรวจสอบสถานะ KYC ของผู้ใช้เพื่อกำหนดว่ารูปที่ส่งมาคือประเภทใด
     *
     * @param string $messageId LINE Message ID ของรูปภาพ
     * @param string $lineUserId LINE User ID
     * @param User|null $user User model
     * @return void
     */
    private function handleKycImageMessage(string $messageId, string $lineUserId, ?User $user): void
    {
        $lineService = app(LineService::class);

        // 1. ตรวจสอบว่าผู้ใช้ลงทะเบียนแล้วหรือยัง
        if (!$user) {
            $lineService->sendPushMessage(
                $lineUserId,
                "❌ คุณยังไม่ได้ลงทะเบียนในระบบ\n\n" .
                "กรุณาสมัครสมาชิกที่เว็บไซต์ของเราก่อน\n" .
                "แล้วจึงสามารถทำ KYC ได้"
            );
            return;
        }

        // 2. ตรวจสอบสถานะ KYC เพื่อกำหนดประเภทรูปภาพ
        $kycService = app(LineKycService::class);
        $kyc = \App\Models\KycVerification::where('user_id', $user->id)->first();

        // กำหนดประเภทรูปภาพ
        $imageType = 'id_card'; // default: บัตรประชาชน

        if ($kyc && $kyc->id_card_image) {
            // ถ้ามีรูปบัตรแล้ว แสดงว่ารูปนี้คือ Selfie
            $imageType = 'selfie';
        }

        // 3. แจ้งผู้ใช้ว่ากำลังประมวลผล
        $lineService->sendPushMessage(
            $lineUserId,
            "⏳ กำลังประมวลผลรูปภาพของคุณ...\n\nโปรดรอสักครู่"
        );

        // 4. ประมวลผลรูปภาพ
        $result = $kycService->processImageFromLine(
            $messageId,
            $lineUserId,
            $user,
            $imageType
        );

        // 5. บันทึก log ผลลัพธ์
        Log::info('LINE KYC: Image processed', [
            'user_id' => $user->id,
            'line_user_id' => $lineUserId,
            'image_type' => $imageType,
            'success' => $result['success'],
        ]);

        // ข้อความ response ถูกส่งโดย LineKycService แล้ว
        // ไม่ต้องส่งซ้ำที่นี่
    }

    /**
     * จัดการ Voice Message
     *
     * ขั้นตอน:
     * 1. Download voice message จาก LINE
     * 2. แปลง voice เป็น text ด้วย Speech-to-Text
     * 3. ส่งต่อไปยัง Hybrid Bot (เหมือน text message)
     * 4. ตอบกลับผู้ใช้
     *
     * @param string $messageId
     * @param string $lineUserId
     * @param User|null $user
     * @return void
     */
    private function handleVoiceMessage(string $messageId, string $lineUserId, ?User $user): void
    {
        Log::info('🎤 Processing voice message', [
            'message_id' => $messageId,
            'line_user_id' => $lineUserId,
        ]);

        $lineService = app(LineService::class);
        $voiceService = app(LineVoiceMessageService::class);

        try {
            // ส่งข้อความแจ้งว่ากำลังประมวลผล
            $lineService->sendPushMessage(
                $lineUserId,
                '🎤 กำลังฟังและแปลงเสียงของคุณ... กรุณารอสักครู่ค่ะ'
            );

            // Step 1: Process voice message (download + speech-to-text)
            $result = $voiceService->processVoiceMessage($messageId, 'th-TH');

            if (!$result['success']) {
                // แปลงไม่สำเร็จ
                $errorMsg = '😔 ขออภัยค่ะ ไม่สามารถแปลงเสียงเป็นข้อความได้\n\n';

                if (str_contains($result['error'], 'not configured')) {
                    $errorMsg .= 'ระบบยังไม่ได้ตั้งค่า Speech-to-Text\nกรุณาติดต่อผู้ดูแลระบบค่ะ';
                } else {
                    $errorMsg .= 'เหตุผล: ' . $result['error'];
                }

                $lineService->sendPushMessage($lineUserId, $errorMsg);

                Log::error('Voice-to-Text failed', [
                    'message_id' => $messageId,
                    'error' => $result['error'],
                ]);

                return;
            }

            $transcribedText = $result['text'];
            $confidence = $result['confidence'];

            Log::info('✅ Voice-to-Text successful', [
                'message_id' => $messageId,
                'text' => $transcribedText,
                'confidence' => round($confidence * 100, 2) . '%',
            ]);

            // Step 2: ส่งข้อความที่แปลงได้กลับไปให้ผู้ใช้เห็น (เป็น confirmation)
            $confirmationMsg = '🎧 คุณพูดว่า: "' . $transcribedText . '"';

            if ($confidence < 0.8) {
                $confirmationMsg .= "\n\n⚠️ ระบบไม่แน่ใจกับการแปลง (" . round($confidence * 100) . "%)";
            }

            $lineService->sendPushMessage($lineUserId, $confirmationMsg);

            // Step 3: ส่งต่อไปยัง Hybrid Bot (เหมือน text message)
            $hybridBot = app(LineHybridBotService::class);
            $aiBot = AiBotProfile::where('is_active', true)->first();

            if (!$aiBot) {
                Log::warning('No active AI bot found for voice message processing');
                return;
            }

            $hybridBot->processMessage($lineUserId, $transcribedText, $user, $aiBot);

            Log::info('📨 Voice message forwarded to Hybrid Bot', [
                'line_user_id' => $lineUserId,
                'transcribed_text' => $transcribedText,
                'bot_id' => $aiBot->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Voice message handling error', [
                'message_id' => $messageId,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // แจ้งผู้ใช้
            $lineService->sendPushMessage(
                $lineUserId,
                '😔 เกิดข้อผิดพลาดในการประมวลผลเสียง กรุณาลองใหม่อีกครั้งค่ะ'
            );
        }
    }
}
