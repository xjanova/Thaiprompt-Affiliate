<?php

namespace App\Services\LineOa;

use App\Models\LineOaConfig;
use App\Models\LineOaMessage;
use App\Models\LineOaWebhook;
use App\Models\User;
use App\Models\UserKycVerification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Exception;

class LineOaService
{
    protected Client $client;
    protected ?LineOaConfig $config;

    const LINE_API_URL = 'https://api.line.me/v2/bot';
    const LINE_PROFILE_URL = 'https://api.line.me/v2/bot/profile';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
        $this->config = LineOaConfig::getActive();
    }

    /**
     * Get LINE user profile
     */
    public function getUserProfile(string $lineUserId): ?array
    {
        if (!$this->config) {
            throw new Exception('LINE OA configuration not found.');
        }

        try {
            $response = $this->client->get(
                self::LINE_PROFILE_URL . '/' . $lineUserId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->channel_access_token,
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'userId' => $data['userId'] ?? null,
                'displayName' => $data['displayName'] ?? null,
                'pictureUrl' => $data['pictureUrl'] ?? null,
                'statusMessage' => $data['statusMessage'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('LINE OA: Failed to get user profile', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send message to LINE user
     */
    public function sendMessage(string $lineUserId, array $messages): bool
    {
        if (!$this->config) {
            throw new Exception('LINE OA configuration not found.');
        }

        try {
            $response = $this->client->post(
                self::LINE_API_URL . '/message/push',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->channel_access_token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'to' => $lineUserId,
                        'messages' => $messages,
                    ],
                ]
            );

            // Log the message
            foreach ($messages as $message) {
                $this->logMessage(
                    $lineUserId,
                    'outgoing',
                    $message['type'] ?? 'text',
                    $message['text'] ?? json_encode($message),
                    $message,
                    'sent'
                );
            }

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('LINE OA: Failed to send message', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);

            // Log failed message
            foreach ($messages as $message) {
                $this->logMessage(
                    $lineUserId,
                    'outgoing',
                    $message['type'] ?? 'text',
                    $message['text'] ?? json_encode($message),
                    $message,
                    'failed',
                    $e->getMessage()
                );
            }

            return false;
        }
    }

    /**
     * Reply to message
     */
    public function replyMessage(string $replyToken, array $messages): bool
    {
        if (!$this->config) {
            throw new Exception('LINE OA configuration not found.');
        }

        try {
            $response = $this->client->post(
                self::LINE_API_URL . '/message/reply',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->channel_access_token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'replyToken' => $replyToken,
                        'messages' => $messages,
                    ],
                ]
            );

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('LINE OA: Failed to reply message', [
                'reply_token' => $replyToken,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process webhook event
     */
    public function processWebhook(array $event): bool
    {
        if (!$this->config) {
            return false;
        }

        try {
            // Store webhook
            $webhook = LineOaWebhook::create([
                'line_oa_config_id' => $this->config->id,
                'event_type' => $event['type'] ?? 'unknown',
                'line_user_id' => $event['source']['userId'] ?? null,
                'payload' => $event,
                'processed' => false,
            ]);

            // Process based on event type
            switch ($event['type']) {
                case 'message':
                    $this->handleMessageEvent($event, $webhook);
                    break;

                case 'follow':
                    $this->handleFollowEvent($event, $webhook);
                    break;

                case 'unfollow':
                    $this->handleUnfollowEvent($event, $webhook);
                    break;

                case 'postback':
                    $this->handlePostbackEvent($event, $webhook);
                    break;

                default:
                    Log::info('LINE OA: Unhandled event type', ['type' => $event['type']]);
            }

            $webhook->markAsProcessed();
            return true;
        } catch (Exception $e) {
            Log::error('LINE OA: Failed to process webhook', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            if (isset($webhook)) {
                $webhook->markAsProcessed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Handle message event
     */
    protected function handleMessageEvent(array $event, LineOaWebhook $webhook): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $message = $event['message'] ?? null;

        if (!$lineUserId || !$message) {
            return;
        }

        // Log incoming message
        $this->logMessage(
            $lineUserId,
            'incoming',
            $message['type'] ?? 'text',
            $message['text'] ?? json_encode($message),
            $message,
            'delivered',
            null,
            $event['replyToken'] ?? null
        );

        // Find user by LINE user ID
        $user = User::where('line_user_id', $lineUserId)->first();
        if ($user) {
            $webhook->update(['user_id' => $user->id]);
        }

        // Handle text message
        if ($message['type'] === 'text') {
            $text = strtolower(trim($message['text']));

            // Check for KYC verification command
            if (in_array($text, ['verify', 'kyc', 'ยืนยันตัวตน'])) {
                $this->handleKycVerification($lineUserId, $event['replyToken'] ?? null);
            }
        }
    }

    /**
     * Handle follow event (user adds bot as friend)
     */
    protected function handleFollowEvent(array $event, LineOaWebhook $webhook): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        // Get user profile
        $profile = $this->getUserProfile($lineUserId);

        // Find or create user
        $user = User::where('line_user_id', $lineUserId)->first();

        if ($user) {
            $webhook->update(['user_id' => $user->id]);
        }

        // Send welcome message
        $welcomeMessage = $this->config->getWelcomeMessage();
        $this->replyMessage($event['replyToken'], [$welcomeMessage]);
    }

    /**
     * Handle unfollow event (user blocks or removes bot)
     */
    protected function handleUnfollowEvent(array $event, LineOaWebhook $webhook): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        // Find user
        $user = User::where('line_user_id', $lineUserId)->first();

        if ($user) {
            $webhook->update(['user_id' => $user->id]);

            // Optionally mark KYC as expired
            $kyc = $user->kycVerification;
            if ($kyc && $kyc->isVerified()) {
                Log::info('LINE OA: User unfollowed', [
                    'user_id' => $user->id,
                    'line_user_id' => $lineUserId,
                ]);
            }
        }
    }

    /**
     * Handle postback event
     */
    protected function handlePostbackEvent(array $event, LineOaWebhook $webhook): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $data = $event['postback']['data'] ?? null;

        if (!$lineUserId || !$data) {
            return;
        }

        parse_str($data, $params);

        // Handle KYC verification action
        if (isset($params['action']) && $params['action'] === 'verify_kyc') {
            $this->handleKycVerification($lineUserId, $event['replyToken'] ?? null);
        }
    }

    /**
     * Handle KYC verification
     */
    protected function handleKycVerification(string $lineUserId, ?string $replyToken = null): void
    {
        // Get user profile
        $profile = $this->getUserProfile($lineUserId);

        if (!$profile) {
            $this->sendMessage($lineUserId, [[
                'type' => 'text',
                'text' => 'ไม่สามารถดึงข้อมูลโปรไฟล์ของคุณได้ กรุณาลองใหม่อีกครั้ง',
            ]]);
            return;
        }

        // Find user by LINE user ID
        $user = User::where('line_user_id', $lineUserId)->first();

        if (!$user) {
            $message = 'ไม่พบบัญชีผู้ใช้ของคุณในระบบ กรุณาเชื่อมต่อบัญชีก่อน';
        } else {
            // Create or update KYC verification
            $kyc = UserKycVerification::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'line_user_id' => $lineUserId,
                    'line_display_name' => $profile['displayName'],
                    'line_picture_url' => $profile['pictureUrl'],
                    'verification_method' => 'line_oa',
                    'verification_status' => 'verified',
                    'verified_at' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            $kyc->verify();

            $message = $this->config->getKycSuccessMessage()['text'] ??
                'ยืนยันตัวตนสำเร็จ! คุณสามารถใช้งานระบบได้เต็มรูปแบบแล้ว';
        }

        // Reply or send message
        if ($replyToken) {
            $this->replyMessage($replyToken, [[
                'type' => 'text',
                'text' => $message,
            ]]);
        } else {
            $this->sendMessage($lineUserId, [[
                'type' => 'text',
                'text' => $message,
            ]]);
        }
    }

    /**
     * Link user account with LINE
     */
    public function linkUserAccount(User $user, string $lineUserId): bool
    {
        // Get LINE profile
        $profile = $this->getUserProfile($lineUserId);

        if (!$profile) {
            return false;
        }

        // Update user
        $user->update([
            'line_user_id' => $lineUserId,
        ]);

        // Create KYC verification
        $kyc = UserKycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'line_user_id' => $lineUserId,
                'line_display_name' => $profile['displayName'],
                'line_picture_url' => $profile['pictureUrl'],
                'verification_method' => 'line_oa',
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]
        );

        $kyc->verify();

        // Send success message
        $message = $this->config->getKycSuccessMessage();
        $this->sendMessage($lineUserId, [$message]);

        return true;
    }

    /**
     * Check if user can withdraw (KYC verified)
     */
    public function canWithdraw(User $user, float $amount): array
    {
        if (!$this->config || !$this->config->require_kyc_for_withdrawal) {
            return ['can_withdraw' => true];
        }

        if (!$this->config->requiresKycForWithdrawal($amount)) {
            return ['can_withdraw' => true];
        }

        $kyc = $user->kycVerification;

        if (!$kyc || !$kyc->isVerified()) {
            return [
                'can_withdraw' => false,
                'reason' => 'KYC verification required',
                'message' => 'คุณต้องยืนยันตัวตนผ่าน LINE OA ก่อนถอนเงิน กรุณาเพิ่มเพื่อนและยืนยันตัวตน',
            ];
        }

        return ['can_withdraw' => true];
    }

    /**
     * Log message
     */
    protected function logMessage(
        string $lineUserId,
        string $direction,
        string $messageType,
        string $messageContent,
        array $messageData,
        string $status = 'pending',
        ?string $error = null,
        ?string $replyToken = null
    ): void {
        if (!$this->config) {
            return;
        }

        $user = User::where('line_user_id', $lineUserId)->first();

        LineOaMessage::create([
            'line_oa_config_id' => $this->config->id,
            'user_id' => $user->id ?? null,
            'line_user_id' => $lineUserId,
            'direction' => $direction,
            'message_type' => $messageType,
            'message_content' => $messageContent,
            'message_data' => $messageData,
            'reply_token' => $replyToken,
            'status' => $status,
            'sent_at' => $direction === 'outgoing' ? now() : null,
            'delivered_at' => $direction === 'incoming' ? now() : null,
            'error_message' => $error,
        ]);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $body, string $signature): bool
    {
        if (!$this->config) {
            return false;
        }

        $hash = hash_hmac('sha256', $body, $this->config->channel_secret, true);
        $expectedSignature = base64_encode($hash);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Send notification to user
     */
    public function sendNotification(User $user, string $message, ?string $type = 'text'): bool
    {
        if (!$user->line_user_id) {
            return false;
        }

        return $this->sendMessage($user->line_user_id, [[
            'type' => $type,
            'text' => $message,
        ]]);
    }

    /**
     * Send withdrawal notification
     */
    public function sendWithdrawalNotification(User $user, string $status, float $amount): bool
    {
        $messages = [
            'pending' => "คำขอถอนเงินจำนวน {$amount} บาท อยู่ระหว่างการตรวจสอบ",
            'approved' => "คำขอถอนเงินจำนวน {$amount} บาท ได้รับการอนุมัติแล้ว",
            'rejected' => "คำขอถอนเงินจำนวน {$amount} บาท ถูกปฏิเสธ",
            'completed' => "โอนเงินจำนวน {$amount} บาท เรียบร้อยแล้ว",
        ];

        $message = $messages[$status] ?? "สถานะการถอนเงินของคุณ: {$status}";

        return $this->sendNotification($user, $message);
    }
}
