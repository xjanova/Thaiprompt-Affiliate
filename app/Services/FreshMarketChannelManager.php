<?php

namespace App\Services;

use App\Models\FreshMarketConversation;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketReferral;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FreshMarketChannelManager - จัดการการสนทนาตลาดสด
 *
 * State Machine v2: Dispatcher Pattern
 * route ตาม {state}:{inputType} ไปหา handler เฉพาะ
 *
 * บอทรู้ชัดเจนว่ากำลังทำอะไร ยูสเซอร์กำลังทำอะไร
 * ทุกสถานะมี handler แยก + error recovery + timeout
 */
class FreshMarketChannelManager
{
    protected FreshMarketSetting $settings;

    protected FreshMarketAIService $aiService;

    protected FreshMarketService $marketService;

    protected FreshMarketLineService $lineService;

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();
        $this->aiService = new FreshMarketAIService($this->settings);
        $this->marketService = new FreshMarketService;
        $this->lineService = new FreshMarketLineService($this->settings);
    }

    // ╔══════════════════════════════════════════╗
    // ║  MAIN ENTRY POINTS                       ║
    // ╚══════════════════════════════════════════╝

    /**
     * ประมวลผลข้อความจากผู้ใช้
     */
    public function processMessage(string $lineUserId, string $message, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // 1. เช็ค timeout
        $timeoutResult = $this->checkAndHandleTimeout($conversation);
        if ($timeoutResult) {
            if ($replyToken) {
                $this->sendReply($replyToken, $timeoutResult);
            }

            return $timeoutResult;
        }

        // 2. เช็คคำสั่ง "ยกเลิก" (ทุกสถานะ)
        if ($this->isCancel($message)) {
            $wasInFlow = $conversation->conversation_state !== FreshMarketConversation::STATE_IDLE;
            $conversation->resetToIdle();
            $result = [
                'text' => $wasInFlow
                    ? "❌ ยกเลิกแล้วค่ะ\n\nมีอะไรให้พี่ตลาดช่วยพิมพ์มาได้เลยนะคะ 😊"
                    : "มีอะไรให้พี่ตลาดช่วยค่ะ? 😊",
            ];
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // 3. เช็ค referral token (ทุกสถานะ)
        if (preg_match('/^ref_([A-Za-z0-9]{32})$/i', trim($message), $matches)) {
            $result = $this->handleReferralToken($lineUserId, $matches[1], $conversation);
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // 4. เช็คคำสั่งพิเศษ (ทุกสถานะ)
        $command = $this->detectCommand($message);
        if ($command) {
            $result = $this->handleCommand($command, $lineUserId, $conversation, $extra);
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // 5. บันทึกข้อความผู้ใช้
        $conversation->addMessage('user', $message);

        // 6. Dispatch ตาม state
        $result = $this->dispatchByState($conversation, 'text', $message, $extra);

        // 7. บันทึกคำตอบ + ส่งกลับ
        if (! empty($result['text'])) {
            $conversation->addMessage('assistant', $result['text'], $result['metadata'] ?? []);
        }

        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    /**
     * ประมวลผลรูปภาพ
     */
    public function processImage(string $lineUserId, string $messageId, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // เช็ค timeout
        $timeoutResult = $this->checkAndHandleTimeout($conversation);
        if ($timeoutResult) {
            if ($replyToken) {
                $this->sendReply($replyToken, $timeoutResult);
            }

            return $timeoutResult;
        }

        // ดาวน์โหลดรูปจาก LINE
        $imageContent = $this->lineService->getMessageContent($messageId);
        if (! $imageContent) {
            $result = ['text' => 'ขอโทษค่ะ ไม่สามารถรับรูปภาพได้ กรุณาลองส่งใหม่อีกครั้งค่ะ 🙏'];
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // อัพโหลดรูป
        $imageUrl = $this->uploadImage($imageContent);
        if (empty($imageUrl)) {
            $result = ['text' => 'ขอโทษค่ะ ไม่สามารถบันทึกรูปภาพได้ กรุณาลองส่งใหม่ค่ะ 🙏'];
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // Dispatch ตาม state
        $result = $this->dispatchByState($conversation, 'image', $imageUrl, $extra);

        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    /**
     * ประมวลผลพิกัด GPS
     */
    public function processLocation(string $lineUserId, float $lat, float $lng, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // เช็ค timeout
        $timeoutResult = $this->checkAndHandleTimeout($conversation);
        if ($timeoutResult) {
            if ($replyToken) {
                $this->sendReply($replyToken, $timeoutResult);
            }

            return $timeoutResult;
        }

        // อัพเดทพิกัดในทุกกรณี
        $conversation->updateSearchLocation($lat, $lng);

        // Dispatch ตาม state
        $result = $this->dispatchByState($conversation, 'location', ['lat' => $lat, 'lng' => $lng], $extra);

        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    /**
     * ผู้ใช้เพิ่มเพื่อน (Follow) → Auto-Register ทันที
     *
     * สร้าง User + MlmMember + FreshMarketSeller อัตโนมัติ
     * ไม่ต้อง signup 8 ขั้นตอนอีกต่อไป
     */
    public function handleFollow(string $lineUserId, array $extra = []): void
    {
        $replyToken = $extra['reply_token'] ?? null;

        // 1. ดึง LINE Profile
        $profile = $this->lineService->getUserProfile($lineUserId);
        $displayName = $profile['displayName'] ?? $profile['name'] ?? 'ผู้ใช้ใหม่';
        $pictureUrl = $profile['pictureUrl'] ?? $profile['picture_url'] ?? null;

        // 2. Auto-register (User + MLM + Seller)
        $result = $this->autoRegisterUser($lineUserId, $displayName, $pictureUrl);

        // 3. สร้าง/อัพเดท conversation
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        if ($result['user']) {
            $conversation->update([
                'user_id' => $result['user']->id,
                'seller_id' => $result['seller']?->id,
            ]);
        }

        // 4. ตรวจสอบ referral → mark converted
        $referral = FreshMarketReferral::findByReferredLineUser($lineUserId);
        if ($referral && in_array($referral->status, ['pending', 'followed'])) {
            $referral->update([
                'referred_user_id' => $result['user']?->id,
                'status' => 'converted',
            ]);
        }

        // 5. ส่ง welcome message พร้อมปุ่มเมนู (replyMessage = ฟรี!)
        $greeting = $displayName !== 'ผู้ใช้ใหม่' ? " คุณ{$displayName}" : '';
        $template = $this->settings->greeting_message_template
            ?? "สวัสดีค่ะ{name}! ยินดีต้อนรับสู่ตลาดสดไทยพร้อม 🏪\n\n✅ สมัครสมาชิกอัตโนมัติเรียบร้อยแล้วค่ะ\n\nเลือกสิ่งที่ต้องการได้เลยนะคะ:";
        $welcomeMessage = str_replace('{name}', $greeting, $template);

        $welcomeResult = $this->buildGreetingWithButtons($conversation);
        $welcomeResult['text'] = $welcomeMessage;

        if ($replyToken) {
            $this->sendReply($replyToken, $welcomeResult);
        }

        Log::info('FreshMarket: Auto-registered on follow', [
            'line_user_id' => $lineUserId,
            'display_name' => $displayName,
            'user_id' => $result['user']?->id,
            'seller_id' => $result['seller']?->id,
            'mlm_member_id' => $result['mlm_member']?->id,
            'is_new' => $result['is_new'],
        ]);
    }

    /**
     * Auto-register: สร้าง User + MlmMember + Seller อัตโนมัติ
     *
     * @return array{user: ?User, seller: ?FreshMarketSeller, mlm_member: ?MlmMember, is_new: bool}
     */
    protected function autoRegisterUser(string $lineUserId, string $displayName, ?string $pictureUrl): array
    {
        // เช็คว่ามี User อยู่แล้ว
        $existingUser = User::where('line_user_id', $lineUserId)->first();
        if ($existingUser) {
            // อัพเดท LINE profile ล่าสุด
            $existingUser->update(array_filter([
                'line_display_name' => $displayName,
                'line_picture_url' => $pictureUrl,
            ]));

            $seller = FreshMarketSeller::findByLineUserId($lineUserId);

            return [
                'user' => $existingUser,
                'seller' => $seller,
                'mlm_member' => null,
                'is_new' => false,
            ];
        }

        // สร้างใหม่ทั้งหมดใน transaction
        try {
            return DB::transaction(function () use ($lineUserId, $displayName, $pictureUrl) {
                // 1. สร้าง User
                $user = User::create([
                    'name' => $displayName,
                    'email' => "line_{$lineUserId}@thaiprompt.local",
                    'password' => Hash::make(Str::random(16)),
                    'line_user_id' => $lineUserId,
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                    'line_verified' => true,
                    'line_linked_at' => now(),
                ]);

                // 2. สร้าง MlmMember (binary tree)
                $mlmMember = $this->createMlmMember($user, $lineUserId);

                // 3. สร้าง FreshMarketSeller
                $seller = FreshMarketSeller::create([
                    'user_id' => $user->id,
                    'line_user_id' => $lineUserId,
                    'shop_name' => $displayName,
                    'line_display_name' => $displayName,
                    'shop_image' => $pictureUrl,
                    'mlm_member_id' => $mlmMember?->id,
                    'is_active' => true,
                    'subscription_type' => 'free',
                ]);

                return [
                    'user' => $user,
                    'seller' => $seller,
                    'mlm_member' => $mlmMember,
                    'is_new' => true,
                ];
            });
        } catch (\Exception $e) {
            Log::error('FreshMarket: Auto-register failed', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);

            return ['user' => null, 'seller' => null, 'mlm_member' => null, 'is_new' => false];
        }
    }

    /**
     * สร้าง MlmMember (binary tree) สำหรับผู้ใช้ใหม่
     */
    protected function createMlmMember(User $user, string $lineUserId): ?MlmMember
    {
        try {
            // หา sponsor จาก referral (ถ้ามี)
            $sponsor = null;
            $referral = FreshMarketReferral::findByReferredLineUser($lineUserId);

            if ($referral && $referral->referrer_user_id) {
                $sponsor = MlmMember::where('user_id', $referral->referrer_user_id)
                    ->where('status', 'active')
                    ->first();
            }

            // Fallback: Super Admin
            if (! $sponsor) {
                $superAdmin = User::find(1);
                $sponsor = $superAdmin ? MlmMember::where('user_id', $superAdmin->id)->first() : null;
            }

            if (! $sponsor) {
                Log::warning('FreshMarket: ไม่มี sponsor สำหรับ MLM', ['user_id' => $user->id]);

                return null;
            }

            // หาตำแหน่งใน binary tree
            $placement = $this->findBinaryPlacement($sponsor);

            // หา default MLM plan
            $defaultPlan = MlmPlan::where('is_default', true)->first();

            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $defaultPlan?->id,
                'unilevel_sponsor_id' => $sponsor->id,
                'unilevel_level' => ($sponsor->unilevel_level ?? 0) + 1,
                'unilevel_path' => ($sponsor->unilevel_path ?? '') . '/' . $sponsor->id,
                'binary_sponsor_id' => $sponsor->id,
                'binary_parent_id' => $placement['parent_id'],
                'binary_position' => $placement['position'],
                'status' => 'active',
                'joined_at' => now(),
                'member_code' => MlmMember::generateMemberCode(),
                'is_qualified' => true,
            ]);

            // อัพเดทสถิติ sponsor
            $sponsor->increment('total_direct_referrals');

            return $member;
        } catch (\Exception $e) {
            Log::error('FreshMarket: MLM member creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * หาตำแหน่งว่างใน binary tree (ซ้าย-ขวา)
     */
    protected function findBinaryPlacement(MlmMember $sponsor): array
    {
        // ใช้ MlmBinaryService ถ้ามี
        if (class_exists(\App\Services\MlmBinaryService::class)) {
            try {
                $binaryService = app(\App\Services\MlmBinaryService::class);

                return $binaryService->findPlacementPosition($sponsor);
            } catch (\Exception $e) {
                // Fallback
            }
        }

        // Fallback: หาตำแหน่งว่างง่ายๆ
        $leftChild = MlmMember::where('binary_parent_id', $sponsor->id)
            ->where('binary_position', 'left')
            ->first();

        if (! $leftChild) {
            return ['parent_id' => $sponsor->id, 'position' => 'left'];
        }

        $rightChild = MlmMember::where('binary_parent_id', $sponsor->id)
            ->where('binary_position', 'right')
            ->first();

        if (! $rightChild) {
            return ['parent_id' => $sponsor->id, 'position' => 'right'];
        }

        // ทั้ง 2 ฝั่งเต็ม → ลงซ้ายสุด (spillover)
        return $this->findBinaryPlacement($leftChild);
    }

    /**
     * ผู้ใช้ลบเพื่อน (Unfollow)
     */
    public function handleUnfollow(string $lineUserId): void
    {
        Log::info('FreshMarket: User unfollowed', ['line_user_id' => $lineUserId]);
    }

    /**
     * จัดการ Postback (กดปุ่ม Flex)
     */
    public function handlePostback(string $lineUserId, string $data, array $extra = []): void
    {
        $replyToken = $extra['reply_token'] ?? null;
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);

        parse_str($data, $params);
        $action = $params['action'] ?? '';

        $result = match ($action) {
            'menu' => $this->handleMenuPostback($lineUserId, $params, $conversation),
            'order' => $this->handleOrderSelectPostback($lineUserId, $params, $conversation),
            'confirm_listing' => $this->handleListingConfirmPostback($conversation),
            'edit_listing' => $this->handleListingEditPostback($conversation),
            'confirm_order' => $this->handleConfirmOrderPostback($lineUserId, $params, $conversation),
            'cancel_order' => $this->handleCancelOrderPostback($lineUserId, $params, $conversation),
            'sell_more' => $this->handleSellMorePostback($conversation),
            default => ['text' => 'ขอโทษค่ะ พี่ตลาดไม่เข้าใจคำสั่งนี้ 🤔'],
        };

        if ($replyToken && $result) {
            $this->sendReply($replyToken, $result);
        }
    }

    /**
     * จัดการ postback จากเมนูปุ่มหลัก
     */
    protected function handleMenuPostback(string $lineUserId, array $params, FreshMarketConversation $conversation): array
    {
        $choice = $params['choice'] ?? '';

        return match ($choice) {
            'buy' => $this->handleCommand('buy', $lineUserId, $conversation, []),
            'sell' => $this->handleCommand('sell', $lineUserId, $conversation, []),
            'rider' => $this->handleCommand('rider', $lineUserId, $conversation, []),
            'chat_ai' => $this->enterAIChatMode($conversation),
            'help' => $this->handleCommand('help', $lineUserId, $conversation, []),
            'back_to_menu' => $this->buildGreetingWithButtons($conversation),
            default => $this->buildGreetingWithButtons($conversation),
        };
    }

    /**
     * เข้าสู่โหมด AI chat — AI ตอบ free text + สร้างปุ่มเอง
     */
    protected function enterAIChatMode(FreshMarketConversation $conversation): array
    {
        $conversation->setFlowContext('ai_chat', [
            'active' => true,
            'started_at' => now()->toIso8601String(),
        ]);

        $botName = $this->settings->bot_name ?? 'พี่ตลาด';

        return [
            'text' => "💬 เข้าสู่โหมดคุยกับ{$botName}ค่ะ!\n\nถามอะไรก็ได้เกี่ยวกับตลาดสดนะคะ 😊\nพิมพ์ \"กลับเมนู\" เพื่อกลับหน้าหลัก",
            'quick_replies' => [
                ['label' => '🔙 กลับเมนู', 'postback' => 'action=menu&choice=back_to_menu', 'display_text' => 'กลับเมนู'],
            ],
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  CORE DISPATCHER                         ║
    // ╚══════════════════════════════════════════╝

    /**
     * Dispatch ตาม {state}:{inputType}
     */
    protected function dispatchByState(FreshMarketConversation $conversation, string $inputType, mixed $inputData, array $extra): array
    {
        $state = $conversation->conversation_state ?? FreshMarketConversation::STATE_IDLE;

        return match ("{$state}:{$inputType}") {
            // === IDLE ===
            'idle:text' => $this->handleIdle_Text($conversation, $inputData, $extra),
            'idle:image' => $this->handleIdle_Image($conversation, $inputData, $extra),
            'idle:location' => $this->handleIdle_Location($conversation, $inputData['lat'], $inputData['lng'], $extra),

            // === SELLER OTP VERIFICATION ===
            'seller_phone:text' => $this->handleSellerPhone_Text($conversation, $inputData, $extra),
            'seller_otp:text' => $this->handleSellerOtp_Text($conversation, $inputData, $extra),

            // === LISTING FLOW ===
            'listing_photos:image' => $this->handleListingPhotos_Image($conversation, $inputData, $extra),
            'listing_photos:text' => $this->handleListingPhotos_Text($conversation, $inputData, $extra),
            'listing_photos:location' => $this->handleWrongInputType($conversation, 'location'),

            'listing_details:text' => $this->handleListingDetails_Text($conversation, $inputData, $extra),
            'listing_details:image' => $this->handleListingPhotos_Image($conversation, $inputData, $extra),
            'listing_details:location' => $this->handleWrongInputType($conversation, 'location'),

            'listing_location:location' => $this->handleListingLocation_Location($conversation, $inputData['lat'], $inputData['lng'], $extra),
            'listing_location:text' => $this->handleListingLocation_Text($conversation, $inputData, $extra),
            'listing_location:image' => $this->handleWrongInputType($conversation, 'image'),

            'listing_review:text' => $this->handleListingReview_Text($conversation, $inputData, $extra),
            'listing_review:image' => $this->handleWrongInputType($conversation, 'image'),
            'listing_review:location' => $this->handleWrongInputType($conversation, 'location'),

            // === SEARCH FLOW ===
            'search_location:location' => $this->handleSearchLocation_Location($conversation, $inputData['lat'], $inputData['lng'], $extra),
            'search_location:text' => $this->handleSearchLocation_Text($conversation, $inputData, $extra),
            'search_browsing:text' => $this->handleSearchBrowsing_Text($conversation, $inputData, $extra),
            'search_browsing:location' => $this->handleSearchLocation_Location($conversation, $inputData['lat'], $inputData['lng'], $extra),

            // === ORDER FLOW ===
            'order_quantity:text' => $this->handleOrderQuantity_Text($conversation, $inputData, $extra),
            'order_quantity:location' => $this->handleOrderQuantity_Location($conversation, $inputData['lat'], $inputData['lng'], $extra),
            'order_review:text' => $this->handleOrderReview_Text($conversation, $inputData, $extra),
            'order_tracking:text' => $this->handleOrderTracking_Text($conversation, $inputData, $extra),

            // === DEFAULT ===
            default => $this->handleWrongInputType($conversation, $inputType),
        };
    }

    // ╔══════════════════════════════════════════╗
    // ║  IDLE HANDLERS                           ║
    // ╚══════════════════════════════════════════╝

    /**
     * idle + text → Button-first greeting / AI chat mode
     *
     * ลำดับ:
     * 1. ถ้าอยู่ในโหมด AI chat → ส่งไป AI (พร้อมปุ่มที่ AI สร้างเอง)
     * 2. ถ้า admin เปิด ai_enabled_in_idle → AI ตอบ free text
     * 3. Default: แสดงเมนูปุ่มให้เลือก (บังคับกดปุ่ม)
     */
    protected function handleIdle_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        // 1. ถ้าอยู่ในโหมด AI chat (ผู้ใช้กด "คุยกับ AI" แล้ว)
        $flowContext = $conversation->getFlowContext('ai_chat');
        if (! empty($flowContext['active'])) {
            // ถ้าพิมพ์ "กลับเมนู" → ออกจาก AI chat
            if (in_array(mb_strtolower(trim($message)), ['กลับเมนู', 'เมนู', 'menu'])) {
                $conversation->clearFlowContext('ai_chat');

                return $this->buildGreetingWithButtons($conversation);
            }

            return $this->handleAIChatResponse($conversation, $message, $extra);
        }

        // 2. ถ้า admin เปิดให้ AI ตอบ free text ใน idle
        if ($this->settings->ai_enabled_in_idle ?? false) {
            return $this->handleAIChatResponse($conversation, $message, $extra);
        }

        // 3. Default: แสดงเมนูปุ่มให้เลือก
        return $this->buildGreetingWithButtons($conversation);
    }

    /**
     * สร้าง greeting message พร้อม Quick Reply buttons
     */
    protected function buildGreetingWithButtons(FreshMarketConversation $conversation): array
    {
        $labels = $this->settings->menu_button_labels ?? [];
        $greeting = $this->settings->greeting_message_template
            ?? "สวัสดีค่ะ! พี่ตลาดพร้อมช่วยเหลือค่ะ 😊\n\nเลือกสิ่งที่ต้องการได้เลยนะคะ:";

        return [
            'text' => $greeting,
            'quick_replies' => [
                ['label' => $labels['buy'] ?? '🛒 ซื้อของ', 'postback' => 'action=menu&choice=buy', 'display_text' => 'อยากซื้อ'],
                ['label' => $labels['sell'] ?? '🏷️ ลงขาย', 'postback' => 'action=menu&choice=sell', 'display_text' => 'ลงขาย'],
                ['label' => $labels['rider'] ?? '🏍️ ไรเดอร์', 'postback' => 'action=menu&choice=rider', 'display_text' => 'สมัครไรเดอร์'],
                ['label' => $labels['chat_ai'] ?? '💬 คุยกับ AI', 'postback' => 'action=menu&choice=chat_ai', 'display_text' => 'คุยกับพี่ตลาด'],
                ['label' => $labels['help'] ?? '📋 ช่วยเหลือ', 'postback' => 'action=menu&choice=help', 'display_text' => 'ช่วยเหลือ'],
            ],
        ];
    }

    /**
     * ส่งข้อความไป AI พร้อม parse structured response (JSON with buttons)
     */
    protected function handleAIChatResponse(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        try {
            $context = $this->buildContext($conversation);
            $maxMessages = $this->settings->ai_max_context_messages ?? 10;
            $aiResult = $this->aiService->generateResponse(
                $message,
                $conversation->getMessageHistory($maxMessages),
                $context
            );

            $response = $aiResult['response'];

            // พยายาม parse JSON (AI structured response with buttons)
            $parsed = $this->parseAIStructuredResponse($response);

            $result = [
                'text' => $parsed['text'],
                'metadata' => [
                    'tokens_used' => $aiResult['tokens_used'],
                    'ai_provider' => $aiResult['provider'],
                    'ai_model' => $aiResult['model'],
                ],
            ];

            // ถ้า AI แนะนำปุ่ม → แปลงเป็น Quick Reply
            if (! empty($parsed['buttons'])) {
                $result['quick_replies'] = array_map(function ($btn) {
                    // ปุ่ม "กลับเมนู" → ใช้ postback เพื่อ routing ชัดเจน
                    if (is_string($btn) && (str_contains($btn, 'กลับเมนู') || str_contains($btn, 'เมนู'))) {
                        return ['label' => '🔙 กลับเมนู', 'postback' => 'action=menu&choice=back_to_menu', 'display_text' => 'กลับเมนู'];
                    }

                    return $btn; // Simple text button
                }, $parsed['buttons']);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('FreshMarketChannelManager: AI chat error', [
                'error' => $e->getMessage(),
            ]);

            return $this->buildGreetingWithButtons($conversation);
        }
    }

    /**
     * Parse AI structured response (JSON with text + buttons)
     *
     * AI ตอบเป็น: {"text": "ข้อความ", "buttons": ["ปุ่ม1", "ปุ่ม2"]}
     * Fallback: ถ้าไม่ใช่ JSON → ใช้ text ตรงๆ + ปุ่มกลับเมนู
     */
    protected function parseAIStructuredResponse(string $response): array
    {
        // ลอง parse JSON
        $cleaned = preg_replace('/^```json\s*|```$/m', '', trim($response));
        $data = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['text'])) {
            return [
                'text' => $data['text'],
                'buttons' => $data['buttons'] ?? ['🔙 กลับเมนู'],
            ];
        }

        // Fallback: ใช้ text ตรงๆ + ปุ่มกลับเมนู
        return [
            'text' => $response,
            'buttons' => ['🔙 กลับเมนู'],
        ];
    }

    /**
     * idle + image → เริ่ม listing flow อัตโนมัติ
     */
    protected function handleIdle_Image(FreshMarketConversation $conversation, string $imageUrl, array $extra): array
    {
        // เริ่ม listing flow ทันทีเมื่อส่งรูปมา
        $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_PHOTOS, [
            'listing' => [
                'images' => [$imageUrl],
                'image_count' => 1,
                'started_at' => now()->toIso8601String(),
            ],
        ]);

        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\n📸 ได้รับรูปที่ 1 แล้วค่ะ! เริ่มลงขายสินค้าให้เลยนะคะ\n\nส่งรูปเพิ่มได้อีก 4 รูป\nหรือพิมพ์ \"เสร็จ\" เพื่อไปขั้นตอนถัดไปค่ะ\n\nพิมพ์ \"ยกเลิก\" ถ้าไม่ได้ต้องการลงขาย",
        ];
    }

    /**
     * idle + location → เริ่ม search flow อัตโนมัติ
     */
    protected function handleIdle_Location(FreshMarketConversation $conversation, float $lat, float $lng, array $extra): array
    {
        return $this->searchNearbyAndRespond($conversation, $lat, $lng);
    }

    // ╔══════════════════════════════════════════╗
    // ║  SELLER OTP VERIFICATION HANDLERS        ║
    // ╚══════════════════════════════════════════╝

    /**
     * seller_phone + text → รับเบอร์โทร ส่ง OTP ทาง SMS
     */
    protected function handleSellerPhone_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $phone = preg_replace('/[^0-9]/', '', trim($message));

        // ตรวจสอบรูปแบบเบอร์โทร (0xx-xxx-xxxx, 10 หลัก)
        if (strlen($phone) !== 10 || ! preg_match('/^0[689]\d{8}$/', $phone)) {
            return [
                'text' => "⚠️ เบอร์โทรไม่ถูกต้องค่ะ\n\nกรุณาพิมพ์เบอร์มือถือ 10 หลัก\nเช่น 0812345678\n\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
            ];
        }

        // ส่ง OTP ทาง SMS
        try {
            $otpService = app(OtpService::class);

            if (! $otpService->isConfigured()) {
                // OTP ไม่พร้อม → ข้าม verify ไปลงขายเลย
                Log::warning('FreshMarket: OTP service ไม่พร้อม, ข้าม verification');

                $seller = FreshMarketSeller::findByLineUserId($conversation->line_user_id);
                if ($seller) {
                    $seller->update([
                        'phone' => $phone,
                        'phone_verified_at' => now(),
                    ]);
                }

                $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_PHOTOS, [
                    'listing' => [
                        'images' => [],
                        'image_count' => 0,
                        'started_at' => now()->toIso8601String(),
                    ],
                ]);

                $progress = $conversation->getProgressText();

                return [
                    'text' => "✅ บันทึกเบอร์โทรเรียบร้อยค่ะ\n\n{$progress}\n\n📸 ส่งรูปสินค้ามาเลยค่ะ (ได้สูงสุด 5 รูป)",
                ];
            }

            $result = $otpService->sendOTP($phone, 'phone_verification');

            if (! $result['success']) {
                return [
                    'text' => "⚠️ ส่ง OTP ไม่สำเร็จค่ะ: {$result['message']}\n\nกรุณาลองใหม่ หรือพิมพ์ \"ยกเลิก\"",
                ];
            }

            // บันทึกเบอร์โทรใน context + seller
            $conversation->setFlowContext('otp', ['phone' => $phone]);

            $seller = FreshMarketSeller::findByLineUserId($conversation->line_user_id);
            if ($seller) {
                $seller->update(['phone' => $phone]);
            }

            // ไป seller_otp state
            $conversation->transitionTo(FreshMarketConversation::STATE_SELLER_OTP);

            $formatted = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);

            return [
                'text' => "📤 ส่งรหัส OTP ไปที่ {$formatted} แล้วค่ะ\n\nกรุณาพิมพ์รหัส 6 หลักที่ได้รับทาง SMS\n\nพิมพ์ \"ส่งใหม่\" ถ้าไม่ได้รับ\nพิมพ์ \"เปลี่ยนเบอร์\" ถ้าต้องการเปลี่ยน\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
            ];

        } catch (\Exception $e) {
            Log::error('FreshMarket: OTP send error', ['error' => $e->getMessage()]);

            return [
                'text' => "⚠️ เกิดข้อผิดพลาดในการส่ง OTP ค่ะ\n\nกรุณาลองใหม่อีกครั้ง หรือพิมพ์ \"ยกเลิก\"",
            ];
        }
    }

    /**
     * seller_otp + text → รับรหัส OTP ยืนยันตัวตน
     */
    protected function handleSellerOtp_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $msg = mb_strtolower(trim($message));
        $otpCtx = $conversation->getFlowContext('otp');
        $phone = $otpCtx['phone'] ?? null;

        // "ส่งใหม่" → resend OTP
        if (in_array($msg, ['ส่งใหม่', 'resend', 'ส่งอีกครั้ง', 'ขอใหม่'])) {
            if (! $phone) {
                $conversation->transitionTo(FreshMarketConversation::STATE_SELLER_PHONE);

                return [
                    'text' => "📱 กรุณาพิมพ์เบอร์โทรมาใหม่ค่ะ\nเช่น 0812345678",
                ];
            }

            try {
                $otpService = app(OtpService::class);
                $result = $otpService->sendOTP($phone, 'phone_verification');

                if ($result['success']) {
                    $formatted = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);

                    return [
                        'text' => "📤 ส่งรหัส OTP ใหม่ไปที่ {$formatted} แล้วค่ะ\n\nกรุณาพิมพ์รหัส 6 หลักที่ได้รับ",
                    ];
                }

                return [
                    'text' => "⚠️ {$result['message']}\n\nกรุณารอสักครู่แล้วลองใหม่ค่ะ",
                ];
            } catch (\Exception $e) {
                return [
                    'text' => "⚠️ ส่ง OTP ไม่สำเร็จค่ะ กรุณาลองใหม่อีกครั้ง",
                ];
            }
        }

        // "เปลี่ยนเบอร์" → กลับ seller_phone
        if (in_array($msg, ['เปลี่ยนเบอร์', 'เปลี่ยน', 'change'])) {
            $conversation->transitionTo(FreshMarketConversation::STATE_SELLER_PHONE);

            return [
                'text' => "📱 กรุณาพิมพ์เบอร์โทรใหม่ค่ะ\nเช่น 0812345678",
            ];
        }

        // ตรวจ OTP code (6 หลัก)
        $code = preg_replace('/[^0-9]/', '', $msg);
        if (strlen($code) !== 6) {
            return [
                'text' => "⚠️ กรุณาพิมพ์รหัส OTP 6 หลักค่ะ\n\nพิมพ์ \"ส่งใหม่\" ถ้าไม่ได้รับ\nพิมพ์ \"เปลี่ยนเบอร์\" ถ้าต้องการเปลี่ยน\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
            ];
        }

        if (! $phone) {
            $conversation->transitionTo(FreshMarketConversation::STATE_SELLER_PHONE);

            return [
                'text' => "⚠️ ไม่พบข้อมูลเบอร์โทรค่ะ\n\n📱 กรุณาพิมพ์เบอร์โทรมาใหม่ค่ะ\nเช่น 0812345678",
            ];
        }

        // Verify OTP
        try {
            $otpService = app(OtpService::class);
            $result = $otpService->verifyOTP($phone, $code, 'phone_verification');

            if (! $result['success']) {
                return [
                    'text' => "❌ รหัส OTP ไม่ถูกต้องหรือหมดอายุค่ะ\n\nกรุณาพิมพ์รหัส 6 หลักอีกครั้ง\nหรือพิมพ์ \"ส่งใหม่\" เพื่อขอรหัสใหม่",
                ];
            }

            // OTP ถูกต้อง → อัพเดท seller
            $seller = FreshMarketSeller::findByLineUserId($conversation->line_user_id);
            if ($seller) {
                $seller->update(['phone_verified_at' => now()]);
            }

            // เริ่มลงขาย → listing_photos
            $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_PHOTOS, [
                'listing' => [
                    'images' => [],
                    'image_count' => 0,
                    'started_at' => now()->toIso8601String(),
                ],
            ]);

            $progress = $conversation->getProgressText();

            return [
                'text' => "✅ ยืนยันเบอร์โทรเรียบร้อยค่ะ!\n\n{$progress}\n\n📸 ส่งรูปสินค้ามาเลยค่ะ (ได้สูงสุด 5 รูป)\n\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
            ];

        } catch (\Exception $e) {
            Log::error('FreshMarket: OTP verify error', ['error' => $e->getMessage()]);

            return [
                'text' => "⚠️ เกิดข้อผิดพลาดค่ะ กรุณาลองใหม่อีกครั้ง",
            ];
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║  SELLER LISTING FLOW HANDLERS            ║
    // ╚══════════════════════════════════════════╝

    /**
     * listing_photos + image → รับรูป เก็บใน context
     */
    protected function handleListingPhotos_Image(FreshMarketConversation $conversation, string $imageUrl, array $extra): array
    {
        $ctx = $conversation->getFlowContext('listing');
        $images = $ctx['images'] ?? [];

        // ป้องกันรูปซ้ำ
        if (in_array($imageUrl, $images)) {
            return ['text' => '📸 รูปนี้มีอยู่แล้วค่ะ ส่งรูปอื่นมาเพิ่มได้เลยค่ะ'];
        }

        $images[] = $imageUrl;
        $imageCount = count($images);

        // จำกัด 5 รูป
        if ($imageCount > 5) {
            $progress = $conversation->getProgressText();

            return [
                'text' => "{$progress}\n\n📸 รับได้สูงสุด 5 รูปค่ะ (ตอนนี้มี 5 รูปแล้ว)\n\nพิมพ์ \"เสร็จ\" เพื่อไปขั้นตอนถัดไปค่ะ",
            ];
        }

        $conversation->setFlowContext('listing', [
            'images' => $images,
            'image_count' => $imageCount,
            'started_at' => $ctx['started_at'] ?? now()->toIso8601String(),
        ]);

        $progress = $conversation->getProgressText();

        // ครบ 5 รูป → auto-advance
        if ($imageCount >= 5) {
            $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_DETAILS);
            $newProgress = $conversation->getProgressText();

            return [
                'text' => "{$newProgress}\n\n📸 ได้ 5 รูปครบแล้วค่ะ!\n\n📝 บอกรายละเอียดสินค้าได้เลยค่ะ\n• ชื่อสินค้า\n• ราคา\n• หน่วย (กก./ถุง/กำ/ชิ้น)\n\nเช่น \"มะม่วงน้ำดอกไม้ 120 บาท/กก.\"",
            ];
        }

        $remaining = 5 - $imageCount;

        return [
            'text' => "{$progress}\n\n📸 ได้รับรูปที่ {$imageCount} แล้วค่ะ!\n\nส่งรูปเพิ่มได้อีก {$remaining} รูป\nหรือพิมพ์ \"เสร็จ\" เพื่อไปขั้นตอนถัดไปค่ะ",
        ];
    }

    /**
     * listing_photos + text → "เสร็จ" ไป details หรือแนะนำ
     */
    protected function handleListingPhotos_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $msg = mb_strtolower(trim($message));
        $ctx = $conversation->getFlowContext('listing');
        $imageCount = $ctx['image_count'] ?? 0;

        // เช็คคำสั่ง "เสร็จ"/"done"/"ต่อ"
        if (in_array($msg, ['เสร็จ', 'done', 'ต่อ', 'ถัดไป', 'next', 'เสร็จแล้ว'])) {
            if ($imageCount === 0) {
                return [
                    'text' => "⚠️ ยังไม่มีรูปสินค้าเลยค่ะ\n\n📸 กรุณาส่งรูปสินค้าอย่างน้อย 1 รูปก่อนนะคะ",
                ];
            }

            $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_DETAILS);
            $progress = $conversation->getProgressText();

            return [
                'text' => "{$progress}\n\n📝 บอกรายละเอียดสินค้าได้เลยค่ะ\n• ชื่อสินค้า\n• ราคา\n• หน่วย (กก./ถุง/กำ/ชิ้น)\n\nเช่น \"ผักบุ้งจีน 25 บาท/กำ\"",
            ];
        }

        // ข้อความอื่น → แนะนำ
        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\nตอนนี้รอรูปสินค้าอยู่ค่ะ 📸\n".($imageCount > 0 ? "มีรูปแล้ว {$imageCount} รูป\n\n" : "\n")."• ส่งรูปมาได้เลยค่ะ (สูงสุด 5 รูป)\n• พิมพ์ \"เสร็จ\" เมื่อส่งรูปครบ\n• พิมพ์ \"ยกเลิก\" เพื่อเริ่มใหม่",
        ];
    }

    /**
     * listing_details + text → AI parse ชื่อ/ราคา/หน่วย
     */
    protected function handleListingDetails_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $ctx = $conversation->getFlowContext('listing');

        // ใช้ AI สกัดข้อมูลจากข้อความ
        try {
            $parsed = $this->aiService->parseListingDetailsFromText($message, $ctx);
        } catch (\Exception $e) {
            $parsed = null;
        }

        // ถ้า parse ได้
        if ($parsed && ! empty($parsed['title']) && ! empty($parsed['price'])) {
            // Merge ข้อมูลที่ได้เข้า context
            $conversation->setFlowContext('listing', [
                'title' => $parsed['title'],
                'price' => (float) $parsed['price'],
                'unit' => $parsed['unit'] ?? 'ชิ้น',
                'description' => $parsed['description'] ?? '',
                'category_hint' => $parsed['category_hint'] ?? null,
                'is_organic' => $parsed['is_organic'] ?? false,
            ]);

            // ไปขั้นตอนพิกัด
            $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_LOCATION);
            $progress = $conversation->getProgressText();

            $title = $parsed['title'];
            $price = number_format($parsed['price'], 0);
            $unit = $parsed['unit'] ?? 'ชิ้น';

            return [
                'text' => "{$progress}\n\n✅ ข้อมูลสินค้า:\n• ชื่อ: {$title}\n• ราคา: ฿{$price}/{$unit}\n\n📍 กดปุ่ม \"ส่งตำแหน่ง\" เพื่อระบุพิกัดร้านค่ะ\n\nหรือพิมพ์ \"ข้าม\" ถ้าจะใช้พิกัดเดิม",
            ];
        }

        // ถ้า parse ไม่สำเร็จ → ถามเพิ่ม
        $missing = [];
        if (empty($parsed['title'] ?? $ctx['title'] ?? null)) {
            $missing[] = '• ชื่อสินค้า';
        }
        if (empty($parsed['price'] ?? $ctx['price'] ?? null)) {
            $missing[] = '• ราคา';
        }
        if (empty($parsed['unit'] ?? $ctx['unit'] ?? null)) {
            $missing[] = '• หน่วย (กก./ถุง/กำ/ชิ้น)';
        }

        // ถ้ามีข้อมูลบางส่วน ให้ merge ไว้ก่อน
        if ($parsed) {
            $partialData = array_filter([
                'title' => $parsed['title'] ?? null,
                'price' => isset($parsed['price']) ? (float) $parsed['price'] : null,
                'unit' => $parsed['unit'] ?? null,
                'description' => $parsed['description'] ?? null,
            ]);
            if (! empty($partialData)) {
                $conversation->setFlowContext('listing', $partialData);
            }
        }

        $progress = $conversation->getProgressText();
        $missingText = ! empty($missing) ? "\n\nยังขาด:\n".implode("\n", $missing) : '';

        return [
            'text' => "{$progress}\n\nพี่ตลาดต้องการข้อมูลเพิ่มค่ะ{$missingText}\n\nพิมพ์มาได้เลยค่ะ เช่น \"ผักบุ้งจีน 25 บาท/กำ\"",
        ];
    }

    /**
     * listing_location + location → รับพิกัด ไป review
     */
    protected function handleListingLocation_Location(FreshMarketConversation $conversation, float $lat, float $lng, array $extra): array
    {
        $conversation->setFlowContext('listing', [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        return $this->showListingReview($conversation);
    }

    /**
     * listing_location + text → "ข้าม" ใช้พิกัดเดิม
     */
    protected function handleListingLocation_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $msg = mb_strtolower(trim($message));

        // "ข้าม" → ใช้พิกัดเดิมของ seller หรือพิกัดค้นหาล่าสุด
        if (in_array($msg, ['ข้าม', 'skip', 'ใช้เดิม', 'ใช้พิกัดเดิม'])) {
            $lat = $conversation->last_search_latitude;
            $lng = $conversation->last_search_longitude;

            // ลองหาจาก seller
            if (! $lat && $conversation->seller_id) {
                $seller = FreshMarketSeller::find($conversation->seller_id);
                $lat = $seller?->latitude;
                $lng = $seller?->longitude;
            }

            if ($lat && $lng) {
                $conversation->setFlowContext('listing', [
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                ]);

                return $this->showListingReview($conversation);
            }

            return [
                'text' => "⚠️ ยังไม่มีพิกัดที่บันทึกไว้ค่ะ\n\n📍 กรุณากดปุ่ม \"ส่งตำแหน่ง\" เพื่อระบุพิกัดร้านค่ะ",
            ];
        }

        // ข้อความอื่น → แนะนำ
        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\nตอนนี้รอพิกัดร้านค่ะ 📍\n\n• กดปุ่ม \"+\" → \"ส่งตำแหน่ง\" ใน LINE\n• หรือพิมพ์ \"ข้าม\" ถ้าจะใช้พิกัดเดิม\n• พิมพ์ \"ยกเลิก\" เพื่อเริ่มใหม่",
        ];
    }

    /**
     * listing_review + text → "ยืนยัน"/"แก้ไข"
     */
    protected function handleListingReview_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $msg = mb_strtolower(trim($message));

        // ยืนยัน
        if (in_array($msg, ['ยืนยัน', 'ตกลง', 'ok', 'confirm', 'ใช่', 'โอเค'])) {
            return $this->createListingFromContext($conversation);
        }

        // แก้ไข
        if (in_array($msg, ['แก้ไข', 'edit', 'แก้', 'เปลี่ยน'])) {
            return $this->handleListingEditPostback($conversation);
        }

        // ข้อความอื่น → แนะนำ
        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\nกรุณาพิมพ์:\n• \"ยืนยัน\" - ลงขายสินค้า\n• \"แก้ไข\" - กลับไปแก้ไขข้อมูล\n• \"ยกเลิก\" - ยกเลิกการลงขาย",
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  BUYER SEARCH FLOW HANDLERS              ║
    // ╚══════════════════════════════════════════╝

    /**
     * search_location + location → ค้นหาสินค้าใกล้ตัว
     */
    protected function handleSearchLocation_Location(FreshMarketConversation $conversation, float $lat, float $lng, array $extra): array
    {
        return $this->searchNearbyAndRespond($conversation, $lat, $lng);
    }

    /**
     * search_location + text → ใช้เป็น query ค้นหา
     */
    protected function handleSearchLocation_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $progress = $conversation->getProgressText();

        // ถ้ามีพิกัดเดิม ลองค้นหาด้วย query
        if ($conversation->last_search_latitude) {
            $conversation->update(['last_search_query' => $message]);

            return $this->searchNearbyAndRespond(
                $conversation,
                (float) $conversation->last_search_latitude,
                (float) $conversation->last_search_longitude,
                ['query' => $message]
            );
        }

        return [
            'text' => "{$progress}\n\n📍 ส่งตำแหน่งมาให้พี่ตลาดก่อนนะคะ\nจะได้หาของใกล้บ้านให้ค่ะ\n\nกดปุ่ม \"+\" → \"ส่งตำแหน่ง\" ใน LINE",
        ];
    }

    /**
     * search_browsing + text → ค้นใหม่หรือถามเพิ่ม
     */
    protected function handleSearchBrowsing_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        // ลองค้นหาใหม่ด้วย query
        if ($conversation->last_search_latitude) {
            return $this->searchNearbyAndRespond(
                $conversation,
                (float) $conversation->last_search_latitude,
                (float) $conversation->last_search_longitude,
                ['query' => $message]
            );
        }

        $conversation->transitionTo(FreshMarketConversation::STATE_SEARCH_LOCATION);

        return [
            'text' => "📍 ส่งตำแหน่งมาใหม่ จะค้นหา \"{$message}\" ใกล้บ้านให้ค่ะ",
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  BUYER ORDER FLOW HANDLERS               ║
    // ╚══════════════════════════════════════════╝

    /**
     * order_quantity + text → AI parse จำนวนและวิธีรับ
     */
    protected function handleOrderQuantity_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $ctx = $conversation->getFlowContext('order');

        // ลอง parse จำนวนจากข้อความ
        $parsed = $this->parseOrderQuantityFromText($message, $ctx);

        if ($parsed && ! empty($parsed['quantity'])) {
            $quantity = (int) $parsed['quantity'];
            $deliveryType = $parsed['delivery_type'] ?? 'pickup';
            $unitPrice = $ctx['listing_price'] ?? 0;
            $totalAmount = $unitPrice * $quantity;
            $deliveryFee = $deliveryType === 'rider' ? 30 : 0;

            $conversation->setFlowContext('order', [
                'quantity' => $quantity,
                'delivery_type' => $deliveryType,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
            ]);

            // ไปขั้นตอน review
            $conversation->transitionTo(FreshMarketConversation::STATE_ORDER_REVIEW);

            return $this->showOrderReview($conversation);
        }

        // parse ไม่ได้ → ถามใหม่
        $progress = $conversation->getProgressText();
        $title = $ctx['listing_title'] ?? 'สินค้า';
        $unit = $ctx['listing_unit'] ?? 'ชิ้น';

        return [
            'text' => "{$progress}\n\nจะสั่ง {$title} กี่ {$unit} คะ?\nแล้วจะนัดรับเองหรือให้ส่ง?\n\nเช่น \"3 {$unit} นัดรับ\" หรือ \"2 {$unit} ส่งให้\"",
        ];
    }

    /**
     * order_quantity + location → บันทึกเป็นที่อยู่จัดส่ง
     */
    protected function handleOrderQuantity_Location(FreshMarketConversation $conversation, float $lat, float $lng, array $extra): array
    {
        $conversation->setFlowContext('order', [
            'buyer_latitude' => $lat,
            'buyer_longitude' => $lng,
            'delivery_type' => 'rider',
        ]);

        $progress = $conversation->getProgressText();
        $ctx = $conversation->getFlowContext('order');
        $title = $ctx['listing_title'] ?? 'สินค้า';
        $unit = $ctx['listing_unit'] ?? 'ชิ้น';

        return [
            'text' => "{$progress}\n\n📍 บันทึกตำแหน่งจัดส่งแล้วค่ะ\n\nจะสั่ง {$title} กี่ {$unit} คะ?",
        ];
    }

    /**
     * order_review + text → "ยืนยัน"/"ยกเลิก"
     */
    protected function handleOrderReview_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $msg = mb_strtolower(trim($message));

        if (in_array($msg, ['ยืนยัน', 'ตกลง', 'ok', 'confirm', 'ใช่', 'โอเค', 'สั่ง'])) {
            return $this->createOrderFromContext($conversation);
        }

        if (in_array($msg, ['แก้ไข', 'edit', 'เปลี่ยนจำนวน'])) {
            $conversation->transitionTo(FreshMarketConversation::STATE_ORDER_QUANTITY);
            $ctx = $conversation->getFlowContext('order');
            $title = $ctx['listing_title'] ?? 'สินค้า';
            $unit = $ctx['listing_unit'] ?? 'ชิ้น';

            return [
                'text' => "📝 จะสั่ง {$title} กี่ {$unit} คะ?\nนัดรับเองหรือให้ส่ง?",
            ];
        }

        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\nกรุณาพิมพ์:\n• \"ยืนยัน\" - ยืนยันคำสั่งซื้อ\n• \"แก้ไข\" - เปลี่ยนจำนวน\n• \"ยกเลิก\" - ยกเลิก",
        ];
    }

    /**
     * order_tracking + text → แสดงสถานะ
     */
    protected function handleOrderTracking_Text(FreshMarketConversation $conversation, string $message, array $extra): array
    {
        $ctx = $conversation->getFlowContext('order');
        $orderNumber = $ctx['order_number'] ?? '-';
        $orderStatus = $ctx['order_status'] ?? 'pending';

        $statusLabels = [
            'pending' => '⏳ รอผู้ขายรับออเดอร์',
            'accepted' => '✅ ผู้ขายรับออเดอร์แล้ว',
            'preparing' => '👨‍🍳 กำลังเตรียมสินค้า',
            'ready' => '📦 สินค้าพร้อมแล้ว',
            'delivering' => '🚗 กำลังจัดส่ง',
            'delivered' => '🎉 ส่งถึงแล้ว',
            'completed' => '✅ สำเร็จ',
            'cancelled' => '❌ ยกเลิกแล้ว',
        ];

        $statusText = $statusLabels[$orderStatus] ?? $orderStatus;

        // กลับ idle ถ้า order จบแล้ว
        if (in_array($orderStatus, ['completed', 'cancelled', 'delivered'])) {
            $conversation->resetToIdle();
        }

        return [
            'text' => "📦 ออเดอร์ {$orderNumber}\n\nสถานะ: {$statusText}\n\nดูรายละเอียดเพิ่มเติม:\n".url('/taladsod/orders'),
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  POSTBACK HANDLERS                       ║
    // ╚══════════════════════════════════════════╝

    /**
     * Postback: เลือกสินค้าสั่งซื้อ
     */
    protected function handleOrderSelectPostback(string $lineUserId, array $params, FreshMarketConversation $conversation): array
    {
        $listingId = $params['listing_id'] ?? null;
        if (! $listingId) {
            return ['text' => 'ไม่พบข้อมูลสินค้าค่ะ'];
        }

        $listing = FreshMarketListing::find($listingId);
        if (! $listing || ! $listing->isAvailableForPurchase()) {
            return ['text' => 'ขอโทษค่ะ สินค้านี้ไม่พร้อมขายแล้วค่ะ 😅'];
        }

        // เริ่ม order flow
        $conversation->transitionTo(FreshMarketConversation::STATE_ORDER_QUANTITY, [
            'order' => [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'listing_price' => $listing->price,
                'listing_unit' => $listing->unit,
                'seller_shop_name' => $listing->seller?->shop_name ?? 'ร้านค้า',
                'started_at' => now()->toIso8601String(),
            ],
        ]);

        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\n🛒 {$listing->title}\n💰 ฿".number_format($listing->price, 0)." / {$listing->unit}\n🏪 ".($listing->seller?->shop_name ?? 'ร้านค้า')."\n\nจะสั่งกี่ {$listing->unit} คะ?\nนัดรับเองหรือให้ส่ง?\n\nเช่น \"3 {$listing->unit} นัดรับ\"",
        ];
    }

    /**
     * Postback: ยืนยัน listing
     */
    protected function handleListingConfirmPostback(FreshMarketConversation $conversation): array
    {
        return $this->createListingFromContext($conversation);
    }

    /**
     * Postback: แก้ไข listing → กลับไป details
     */
    protected function handleListingEditPostback(FreshMarketConversation $conversation): array
    {
        $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_DETAILS);
        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\n📝 บอกรายละเอียดใหม่ได้เลยค่ะ\n• ชื่อสินค้า\n• ราคา\n• หน่วย\n\nเช่น \"มะม่วง 150 บาท/กก.\"",
        ];
    }

    /**
     * Postback: ยืนยันออเดอร์
     */
    protected function handleConfirmOrderPostback(string $lineUserId, array $params, FreshMarketConversation $conversation): array
    {
        return $this->createOrderFromContext($conversation);
    }

    /**
     * Postback: ยกเลิกออเดอร์
     */
    protected function handleCancelOrderPostback(string $lineUserId, array $params, FreshMarketConversation $conversation): array
    {
        $conversation->resetToIdle();

        return ['text' => '❌ ยกเลิกคำสั่งซื้อเรียบร้อยค่ะ ไว้มาอุดหนุนใหม่นะคะ 😊'];
    }

    /**
     * Postback: ลงขายเพิ่ม
     */
    protected function handleSellMorePostback(FreshMarketConversation $conversation): array
    {
        $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_PHOTOS, [
            'listing' => [
                'images' => [],
                'image_count' => 0,
                'started_at' => now()->toIso8601String(),
            ],
        ]);

        $progress = $conversation->getProgressText();

        return [
            'text' => "{$progress}\n\n📸 ส่งรูปสินค้ามาเลยค่ะ (ได้สูงสุด 5 รูป)\n\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  HELPER: LISTING CREATION                ║
    // ╚══════════════════════════════════════════╝

    /**
     * แสดงหน้า review ก่อนลงขาย
     */
    protected function showListingReview(FreshMarketConversation $conversation): array
    {
        $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_REVIEW);
        $ctx = $conversation->getFlowContext('listing');
        $progress = $conversation->getProgressText();

        $title = $ctx['title'] ?? 'สินค้า';
        $price = number_format($ctx['price'] ?? 0, 0);
        $unit = $ctx['unit'] ?? 'ชิ้น';
        $description = $ctx['description'] ?? '-';
        $imageCount = $ctx['image_count'] ?? 0;

        $text = "{$progress}\n\n";
        $text .= "📦 สรุปข้อมูลสินค้า:\n";
        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "📝 ชื่อ: {$title}\n";
        $text .= "💰 ราคา: ฿{$price}/{$unit}\n";
        $text .= "📸 รูป: {$imageCount} รูป\n";
        if ($description && $description !== '-') {
            $text .= "📋 รายละเอียด: {$description}\n";
        }
        $text .= "📍 มีพิกัดร้าน\n";
        $text .= "━━━━━━━━━━━━━━━\n\n";
        $text .= "พิมพ์ \"ยืนยัน\" เพื่อลงขาย\n";
        $text .= "พิมพ์ \"แก้ไข\" เพื่อแก้ข้อมูล\n";
        $text .= "พิมพ์ \"ยกเลิก\" เพื่อยกเลิก";

        // สร้าง Flex message (ถ้า LINE Service รองรับ)
        $flex = $this->lineService->buildListingReviewFlex($ctx);

        $result = ['text' => $text];
        if ($flex) {
            $result['flex'] = $flex;
            $result['flex_alt_text'] = "ตรวจสอบสินค้า: {$title}";
        }

        return $result;
    }

    /**
     * สร้าง listing จาก context
     */
    protected function createListingFromContext(FreshMarketConversation $conversation): array
    {
        $ctx = $conversation->getFlowContext('listing');

        // ตรวจสอบข้อมูลครบ
        if (empty($ctx['title']) || empty($ctx['price'])) {
            return ['text' => '⚠️ ข้อมูลไม่ครบค่ะ กรุณาบอกชื่อสินค้าและราคาก่อนนะคะ'];
        }

        try {
            // หา/สร้าง seller
            $seller = $this->resolveOrCreateSeller($conversation);

            if (! $seller) {
                return ['text' => '⚠️ ไม่สามารถสร้างร้านค้าได้ กรุณาลองใหม่ค่ะ'];
            }

            // สร้าง listing ผ่าน service
            $listing = $this->marketService->createListing($seller, [
                'title' => $ctx['title'],
                'price' => $ctx['price'],
                'unit' => $ctx['unit'] ?? 'ชิ้น',
                'description' => $ctx['description'] ?? '',
                'images' => $ctx['images'] ?? [],
                'main_image_url' => $ctx['images'][0] ?? null,
                'latitude' => $ctx['latitude'] ?? $seller->latitude,
                'longitude' => $ctx['longitude'] ?? $seller->longitude,
                'category_hint' => $ctx['category_hint'] ?? null,
                'is_organic' => $ctx['is_organic'] ?? false,
                'created_via' => 'line_chat',
            ]);

            // Transition ไป complete
            $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_COMPLETE, [
                'listing' => array_merge($ctx, ['listing_id' => $listing->id]),
            ]);

            $progress = $conversation->getProgressText();

            // Reset กลับ idle
            $conversation->resetToIdle();

            return [
                'text' => "{$progress}\n\n🎉 ลงขายสินค้าเรียบร้อยแล้วค่ะ!\n\n📦 {$ctx['title']}\n💰 ฿".number_format($ctx['price'], 0)."/{$ctx['unit']}\n\nดูรายการสินค้าของคุณ:\n".url('/taladsod/seller-dashboard')."\n\nพิมพ์ \"ลงขาย\" เพื่อลงขายสินค้าเพิ่มค่ะ 😊",
            ];

        } catch (\Exception $e) {
            Log::error('FreshMarket: สร้าง listing ล้มเหลว', [
                'error' => $e->getMessage(),
                'context' => $ctx,
            ]);

            return ['text' => "⚠️ เกิดข้อผิดพลาดในการลงขายค่ะ กรุณาลองใหม่\n\nพิมพ์ \"ลงขาย\" เพื่อเริ่มใหม่"];
        }
    }

    /**
     * หรือสร้าง seller จาก LINE profile
     */
    protected function resolveOrCreateSeller(FreshMarketConversation $conversation): ?FreshMarketSeller
    {
        // ถ้ามี seller_id แล้ว
        if ($conversation->seller_id) {
            return FreshMarketSeller::find($conversation->seller_id);
        }

        // หาจาก line_user_id
        $seller = FreshMarketSeller::where('line_user_id', $conversation->line_user_id)->first();

        if (! $seller) {
            // สร้างใหม่จาก LINE profile
            $profile = $this->lineService->getUserProfile($conversation->line_user_id);
            $displayName = $profile['displayName'] ?? 'ร้านค้า';

            $seller = FreshMarketSeller::create([
                'user_id' => $conversation->user_id,
                'line_user_id' => $conversation->line_user_id,
                'shop_name' => "ร้าน {$displayName}",
                'description' => "ร้านค้าของ {$displayName}",
                'status' => 'active',
                'latitude' => $conversation->last_search_latitude,
                'longitude' => $conversation->last_search_longitude,
            ]);
        }

        // บันทึก seller_id ใน conversation
        $conversation->update(['seller_id' => $seller->id, 'role' => 'seller']);

        return $seller;
    }

    // ╔══════════════════════════════════════════╗
    // ║  HELPER: ORDER CREATION                  ║
    // ╚══════════════════════════════════════════╝

    /**
     * แสดง order review
     */
    protected function showOrderReview(FreshMarketConversation $conversation): array
    {
        $ctx = $conversation->getFlowContext('order');
        $progress = $conversation->getProgressText();

        $title = $ctx['listing_title'] ?? 'สินค้า';
        $quantity = $ctx['quantity'] ?? 1;
        $unit = $ctx['listing_unit'] ?? 'ชิ้น';
        $unitPrice = $ctx['listing_price'] ?? 0;
        $total = $ctx['total_amount'] ?? ($unitPrice * $quantity);
        $deliveryFee = $ctx['delivery_fee'] ?? 0;
        $deliveryLabel = ($ctx['delivery_type'] ?? 'pickup') === 'rider' ? '🚗 ให้ส่ง' : '🏪 นัดรับเอง';

        $text = "{$progress}\n\n";
        $text .= "🧾 สรุปคำสั่งซื้อ:\n";
        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "📦 {$title} x{$quantity} {$unit}\n";
        $text .= "💰 ฿".number_format($unitPrice, 0)." x {$quantity} = ฿".number_format($total, 0)."\n";
        if ($deliveryFee > 0) {
            $text .= "🚗 ค่าส่ง: ฿".number_format($deliveryFee, 0)."\n";
            $text .= "💵 รวมทั้งหมด: ฿".number_format($total + $deliveryFee, 0)."\n";
        }
        $text .= "📍 {$deliveryLabel}\n";
        $text .= "━━━━━━━━━━━━━━━\n\n";
        $text .= "พิมพ์ \"ยืนยัน\" เพื่อสั่งซื้อ\n";
        $text .= "พิมพ์ \"แก้ไข\" เพื่อเปลี่ยนจำนวน\n";
        $text .= "พิมพ์ \"ยกเลิก\" เพื่อยกเลิก";

        // Flex message
        $flex = $this->lineService->buildOrderSummaryFlex([
            'title' => $title,
            'quantity' => $quantity,
            'total' => $total + $deliveryFee,
            'delivery_type_label' => $deliveryLabel,
            'id' => 0,
        ]);

        $result = ['text' => $text];
        if ($flex) {
            $result['flex'] = $flex;
            $result['flex_alt_text'] = "สรุปคำสั่งซื้อ: {$title}";
        }

        return $result;
    }

    /**
     * สร้าง order จาก context
     */
    protected function createOrderFromContext(FreshMarketConversation $conversation): array
    {
        $ctx = $conversation->getFlowContext('order');

        try {
            $listing = FreshMarketListing::find($ctx['listing_id'] ?? 0);
            if (! $listing) {
                return ['text' => '⚠️ ไม่พบสินค้านี้แล้วค่ะ กรุณาค้นหาใหม่'];
            }

            $quantity = $ctx['quantity'] ?? 1;
            $deliveryType = $ctx['delivery_type'] ?? 'pickup';
            $totalAmount = $ctx['total_amount'] ?? ($listing->price * $quantity);

            $order = $this->marketService->createOrder([
                'listing_id' => $listing->id,
                'buyer_line_user_id' => $conversation->line_user_id,
                'buyer_id' => $conversation->user_id,
                'seller_id' => $listing->seller_id,
                'quantity' => $quantity,
                'unit_price' => $listing->price,
                'total_amount' => $totalAmount,
                'delivery_type' => $deliveryType,
                'delivery_fee' => $ctx['delivery_fee'] ?? 0,
                'buyer_latitude' => $ctx['buyer_latitude'] ?? $conversation->last_search_latitude,
                'buyer_longitude' => $ctx['buyer_longitude'] ?? $conversation->last_search_longitude,
                'payment_method' => 'cod',
            ]);

            // Transition ไป tracking แล้ว idle
            $conversation->setFlowContext('order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => 'pending',
            ]);

            $conversation->resetToIdle();

            $orderNumber = $order->order_number ?? 'TSD-NEW';

            return [
                'text' => "✅ สั่งซื้อสำเร็จค่ะ!\n\n📦 หมายเลข: {$orderNumber}\n🛒 {$listing->title} x{$quantity}\n💰 ฿".number_format($totalAmount, 0)."\n\nรอผู้ขายรับออเดอร์ค่ะ\nพี่ตลาดจะแจ้งให้ทราบเมื่อมีอัพเดทนะคะ 😊",
            ];

        } catch (\Exception $e) {
            Log::error('FreshMarket: สร้าง order ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return ['text' => "⚠️ เกิดข้อผิดพลาดในการสั่งซื้อค่ะ กรุณาลองใหม่\n\nส่งตำแหน่งมาใหม่เพื่อค้นหาสินค้าค่ะ"];
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║  HELPER: SEARCH                          ║
    // ╚══════════════════════════════════════════╝

    /**
     * ค้นหาสินค้าใกล้ตัวและส่งผลลัพธ์
     */
    protected function searchNearbyAndRespond(FreshMarketConversation $conversation, float $lat, float $lng, array $filters = []): array
    {
        $listings = $this->marketService->searchListings($lat, $lng, null, $filters);

        if ($listings->isEmpty()) {
            $radius = $this->settings->default_search_radius_km;
            $conversation->transitionTo(FreshMarketConversation::STATE_SEARCH_BROWSING);

            return [
                'text' => "📍 ได้รับตำแหน่งแล้วค่ะ\n\nยังไม่มีสินค้าในรัศมี {$radius} กม. ค่ะ 😅\nลองบอกพี่ตลาดว่าอยากได้อะไร จะช่วยหาให้ค่ะ!",
            ];
        }

        // สร้าง Flex Carousel
        $listingData = $listings->take(5)->map(function ($listing) {
            return [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'price' => $listing->price,
                'unit' => $listing->unit,
                'image' => $listing->primary_image,
                'distance' => number_format($listing->distance_km ?? 0, 1),
                'shop_name' => $listing->seller?->shop_name ?? 'ร้านค้า',
            ];
        })->toArray();

        $flexCarousel = $this->lineService->buildListingsCarousel($listingData);

        $conversation->transitionTo(FreshMarketConversation::STATE_SEARCH_BROWSING, [
            'search' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'result_count' => $listings->count(),
                'result_listing_ids' => $listings->take(5)->pluck('id')->toArray(),
            ],
        ]);

        return [
            'text' => "📍 สินค้าใกล้คุณ ({$listings->count()} รายการ)\n\nกด \"สั่งซื้อ\" เพื่อสั่งซื้อได้เลยค่ะ",
            'flex' => $flexCarousel,
            'flex_alt_text' => "สินค้าใกล้ตัว {$listings->count()} รายการ",
        ];
    }

    // ╔══════════════════════════════════════════╗
    // ║  UTILITY METHODS                         ║
    // ╚══════════════════════════════════════════╝

    /**
     * ตรวจสอบและจัดการ timeout
     */
    protected function checkAndHandleTimeout(FreshMarketConversation $conversation): ?array
    {
        if (! $conversation->isExpired()) {
            return null;
        }

        $state = $conversation->conversation_state;
        $conversation->resetToIdle();

        $flowName = match (true) {
            str_starts_with($state, 'seller_') => 'การยืนยันตัวตน',
            str_starts_with($state, 'listing_') => 'การลงขายสินค้า',
            str_starts_with($state, 'search_') => 'การค้นหาสินค้า',
            str_starts_with($state, 'order_') => 'การสั่งซื้อ',
            default => 'รายการ',
        };

        return [
            'text' => "⏰ {$flowName}หมดเวลาแล้วค่ะ (ไม่มีการตอบกลับนาน)\n\nเริ่มใหม่ได้เลยนะคะ:\n🛒 \"อยากซื้อ\" - ค้นหาสินค้า\n🏷️ \"ลงขาย\" - ลงขายสินค้า\n📋 \"ช่วยเหลือ\" - ดูคำสั่งทั้งหมด",
        ];
    }

    /**
     * ตรวจจับคำสั่งพิเศษ
     */
    protected function detectCommand(string $message): ?string
    {
        $message = mb_strtolower(trim($message));

        $commands = [
            'กลับเมนู' => 'menu',
            'เมนู' => 'menu',
            'menu' => 'menu',
            'ลงขาย' => 'sell',
            'ขายของ' => 'sell',
            'อยากขาย' => 'sell',
            'อยากซื้อ' => 'buy',
            'ซื้อของ' => 'buy',
            'ซื้อ' => 'buy',
            'หาของ' => 'buy',
            'ค้นหา' => 'search',
            'สมัครไรเดอร์' => 'rider',
            'ไรเดอร์' => 'rider',
            'rider' => 'rider',
            'ออเดอร์' => 'my_orders',
            'คำสั่งซื้อ' => 'my_orders',
            'ช่วยเหลือ' => 'help',
            'help' => 'help',
            'สวัสดี' => 'greeting',
            'หวัดดี' => 'greeting',
        ];

        foreach ($commands as $keyword => $command) {
            if (str_contains($message, $keyword)) {
                return $command;
            }
        }

        return null;
    }

    /**
     * จัดการคำสั่งพิเศษ
     */
    protected function handleCommand(string $command, string $lineUserId, FreshMarketConversation $conversation, array $extra): array
    {
        switch ($command) {
            case 'menu':
                // กลับเมนูหลัก + ออก AI chat mode
                $conversation->clearFlowContext('ai_chat');
                $conversation->transitionTo('idle');

                return $this->buildGreetingWithButtons($conversation);

            case 'rider':
                return [
                    'text' => "🏍️ สมัครไรเดอร์\n\nสมัครเป็นไรเดอร์เซอร์วิสได้ที่:\n" . url('/taladsod/rider/register') . "\n\nรายได้ดี รับงานอิสระ! 💪",
                    'quick_replies' => [
                        ['label' => '🔙 กลับเมนู', 'postback' => 'action=menu&choice=back_to_menu', 'display_text' => 'กลับเมนู'],
                    ],
                ];

            case 'sell':
                // เช็ค phone verified ก่อนเริ่มลงขาย
                $seller = FreshMarketSeller::findByLineUserId($conversation->line_user_id);
                if ($seller && $seller->phone_verified_at) {
                    // ยืนยันแล้ว → ไปลงขายเลย
                    $conversation->transitionTo(FreshMarketConversation::STATE_LISTING_PHOTOS, [
                        'listing' => [
                            'images' => [],
                            'image_count' => 0,
                            'started_at' => now()->toIso8601String(),
                        ],
                    ]);

                    $progress = $conversation->getProgressText();

                    return [
                        'text' => "🏷️ ลงขายสินค้า\n\n{$progress}\n\n📸 ส่งรูปสินค้ามาเลยค่ะ (ได้สูงสุด 5 รูป)\n\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
                    ];
                }

                // ยังไม่ verify → ต้อง OTP ก่อน
                $conversation->transitionTo(FreshMarketConversation::STATE_SELLER_PHONE);

                return [
                    'text' => "🏷️ ลงขายสินค้า\n\n📱 ก่อนเริ่มลงขาย กรุณายืนยันเบอร์โทรศัพท์ค่ะ\n\nพิมพ์เบอร์โทรมาเลยค่ะ เช่น 0812345678\n\nพิมพ์ \"ยกเลิก\" ถ้าต้องการเริ่มใหม่",
                ];

            case 'buy':
            case 'search':
                $conversation->transitionTo(FreshMarketConversation::STATE_SEARCH_LOCATION, [
                    'search' => ['started_at' => now()->toIso8601String()],
                ]);

                $progress = $conversation->getProgressText();

                return [
                    'text' => "🛒 ค้นหาสินค้า\n\n{$progress}\n\n📍 ส่งตำแหน่งมาให้พี่ตลาดเลยค่ะ\nจะได้หาของใกล้บ้านให้นะคะ\n\nกดปุ่ม \"+\" → \"ส่งตำแหน่ง\" ใน LINE",
                ];

            case 'my_orders':
                return [
                    'text' => "📦 ออเดอร์ของคุณ\n\nดูออเดอร์ทั้งหมดได้ที่:\n".url('/taladsod/orders')."\n\nหรือบอกเลขออเดอร์มาได้ค่ะ",
                ];

            case 'help':
                return [
                    'text' => "📋 วิธีใช้งาน\n\nกดปุ่มด้านล่างเพื่อเลือกสิ่งที่ต้องการได้เลยค่ะ\n\nหรือพิมพ์ข้อความเหล่านี้ก็ได้:\n🛒 \"ซื้อของ\" — ค้นหาสินค้าใกล้ตัว\n🏷️ \"ลงขาย\" — ลงขายสินค้า\n📦 \"ออเดอร์\" — ดูคำสั่งซื้อ\n💬 \"คุยกับ AI\" — ถามพี่ตลาด\n❌ \"ยกเลิก\" — ยกเลิกสิ่งที่ทำอยู่\n🔙 \"กลับเมนู\" — กลับหน้าหลัก",
                    'quick_replies' => [
                        ['label' => '🛒 ซื้อของ', 'postback' => 'action=menu&choice=buy', 'display_text' => 'อยากซื้อ'],
                        ['label' => '🏷️ ลงขาย', 'postback' => 'action=menu&choice=sell', 'display_text' => 'ลงขาย'],
                        ['label' => '💬 คุยกับ AI', 'postback' => 'action=menu&choice=chat_ai', 'display_text' => 'คุยกับพี่ตลาด'],
                        ['label' => '🔙 กลับเมนู', 'postback' => 'action=menu&choice=back_to_menu', 'display_text' => 'กลับเมนู'],
                    ],
                ];

            case 'greeting':
                return $this->buildGreetingWithButtons($conversation);

            default:
                return $this->buildGreetingWithButtons($conversation);
        }
    }

    /**
     * ตรวจจับคำสั่ง "ยกเลิก"
     */
    protected function isCancel(string $message): bool
    {
        $msg = mb_strtolower(trim($message));

        return in_array($msg, ['ยกเลิก', 'cancel', 'เริ่มใหม่', 'reset', 'หยุด']);
    }

    /**
     * จัดการ input ผิดประเภทสำหรับ state ปัจจุบัน
     */
    protected function handleWrongInputType(FreshMarketConversation $conversation, string $inputType): array
    {
        $state = $conversation->conversation_state;
        $progress = $conversation->getProgressText();

        $guidance = match ($state) {
            'seller_phone' => "ตอนนี้รอเบอร์โทรค่ะ 📱\n\n• พิมพ์เบอร์โทร 10 หลัก\n• เช่น 0812345678",
            'seller_otp' => "ตอนนี้รอรหัส OTP ค่ะ 🔑\n\n• พิมพ์รหัส 6 หลักที่ได้รับทาง SMS\n• พิมพ์ \"ส่งใหม่\" ถ้าไม่ได้รับ",
            'listing_photos' => "ตอนนี้รอรูปสินค้าค่ะ 📸\n\n• ส่งรูปมาได้เลยค่ะ\n• พิมพ์ \"เสร็จ\" เมื่อส่งรูปครบ",
            'listing_details' => "ตอนนี้รอข้อมูลสินค้าค่ะ 📝\n\n• พิมพ์ชื่อสินค้า ราคา หน่วย\n• เช่น \"ผักบุ้ง 25 บาท/กำ\"",
            'listing_location' => "ตอนนี้รอพิกัดร้านค่ะ 📍\n\n• กดปุ่ม \"+\" → \"ส่งตำแหน่ง\"\n• หรือพิมพ์ \"ข้าม\"",
            'listing_review' => "กรุณาพิมพ์ \"ยืนยัน\" หรือ \"แก้ไข\" ค่ะ",
            'order_quantity' => "กรุณาบอกจำนวนที่ต้องการค่ะ\n\nเช่น \"3 กก. นัดรับ\"",
            'order_review' => "กรุณาพิมพ์ \"ยืนยัน\" หรือ \"ยกเลิก\" ค่ะ",
            default => "ไม่เข้าใจค่ะ ลองใหม่อีกครั้งนะคะ",
        };

        $result = '';
        if ($progress) {
            $result .= "{$progress}\n\n";
        }
        $result .= $guidance;
        $result .= "\n\nพิมพ์ \"ยกเลิก\" เพื่อเริ่มใหม่";

        return ['text' => $result];
    }

    /**
     * Parse จำนวนและวิธีรับจากข้อความ
     */
    protected function parseOrderQuantityFromText(string $message, array $orderCtx): ?array
    {
        // ลอง parse ด้วย regex ก่อน (เร็วกว่า AI)
        $quantity = null;
        $deliveryType = null;

        // หาตัวเลข
        if (preg_match('/(\d+)/', $message, $matches)) {
            $quantity = (int) $matches[1];
        }

        // หาวิธีรับ
        $msg = mb_strtolower($message);
        if (str_contains($msg, 'ส่ง') || str_contains($msg, 'rider') || str_contains($msg, 'จัดส่ง') || str_contains($msg, 'delivery')) {
            $deliveryType = 'rider';
        } elseif (str_contains($msg, 'รับ') || str_contains($msg, 'นัด') || str_contains($msg, 'pickup') || str_contains($msg, 'ไปรับ')) {
            $deliveryType = 'pickup';
        }

        if ($quantity && $quantity > 0) {
            return [
                'quantity' => $quantity,
                'delivery_type' => $deliveryType ?? 'pickup',
            ];
        }

        return null;
    }

    /**
     * จัดการ referral token
     */
    protected function handleReferralToken(string $lineUserId, string $token, FreshMarketConversation $conversation): array
    {
        $referral = FreshMarketReferral::findActiveByToken($token);

        if (! $referral) {
            return ['text' => 'ลิงก์แนะนำนี้หมดอายุหรือไม่ถูกต้องค่ะ'];
        }

        $referral->markAsFollowed($lineUserId);

        $referrerSeller = $referral->referrerSeller;
        $referrerName = $referrerSeller?->shop_name ?? 'เพื่อน';

        return [
            'text' => "🎉 ยินดีต้อนรับค่ะ!\n\nคุณได้รับคำแนะนำจาก \"{$referrerName}\"\n\n{$this->settings->welcome_message}",
        ];
    }

    /**
     * สร้างบริบทสำหรับ AI
     */
    protected function buildContext(FreshMarketConversation $conversation): array
    {
        $context = [
            'role' => $conversation->role,
            'conversation_state' => $conversation->conversation_state,
            'state_step' => $conversation->state_step,
            'state_total_steps' => $conversation->state_total_steps,
        ];

        // เพิ่ม flow context (เช็ค data access)
        if ($conversation->isInListingFlow()) {
            $context['listing_data'] = $conversation->getFlowContext('listing');
        }
        if ($conversation->isInOrderFlow() && ($this->settings->ai_can_access_orders ?? false)) {
            $context['order_data'] = $conversation->getFlowContext('order');
        }

        // สินค้าใกล้ตัว (เช็ค data access)
        if ($conversation->last_search_latitude && ($this->settings->ai_can_access_listings ?? true)) {
            try {
                $listings = $this->marketService->searchListings(
                    (float) $conversation->last_search_latitude,
                    (float) $conversation->last_search_longitude,
                    5
                );

                if ($listings->isNotEmpty()) {
                    $listingText = $listings->take(5)->map(function ($l) {
                        $text = "- {$l->title}";
                        // เฉพาะเมื่อ admin อนุญาตให้เข้าถึงราคา
                        if ($this->settings->ai_can_access_pricing ?? true) {
                            $text .= " ฿{$l->price}/{$l->unit}";
                        }
                        $text .= " ({$l->distance_km}กม.)";

                        return $text;
                    })->implode("\n");

                    $context['nearby_listings'] = $listingText;
                }
            } catch (\Exception $e) {
                // ไม่ต้อง error ถ้าหาสินค้าไม่ได้
            }
        }

        return $context;
    }

    /**
     * ส่งข้อความกลับทาง LINE
     */
    protected function sendReply(string $replyToken, array $result): void
    {
        $messages = [];
        $lastIndex = -1;

        if (! empty($result['text'])) {
            $messages[] = ['type' => 'text', 'text' => $result['text']];
            $lastIndex = count($messages) - 1;
        }

        if (! empty($result['flex'])) {
            $messages[] = [
                'type' => 'flex',
                'altText' => $result['flex_alt_text'] ?? 'ตลาดสดไทยพร้อม',
                'contents' => $result['flex'],
            ];
            $lastIndex = count($messages) - 1;
        }

        // Quick Reply ติดกับ message สุดท้าย (LINE API requirement)
        if (! empty($result['quick_replies']) && $lastIndex >= 0) {
            $messages[$lastIndex]['quickReply'] = [
                'items' => $this->buildQuickReplyItems($result['quick_replies']),
            ];
        }

        if (! empty($messages)) {
            $this->lineService->replyMessage($replyToken, $messages);
        }
    }

    /**
     * สร้าง Quick Reply items จาก array (รองรับ text, postback, message)
     */
    protected function buildQuickReplyItems(array $quickReplies): array
    {
        $items = [];

        foreach ($quickReplies as $reply) {
            if (is_string($reply)) {
                // Simple text button
                $items[] = [
                    'type' => 'action',
                    'action' => [
                        'type' => 'message',
                        'label' => mb_substr($reply, 0, 20),
                        'text' => $reply,
                    ],
                ];
            } elseif (isset($reply['postback'])) {
                // Postback button — routing ชัดเจน ไม่ต้อง text matching
                $items[] = [
                    'type' => 'action',
                    'action' => [
                        'type' => 'postback',
                        'label' => mb_substr($reply['label'], 0, 20),
                        'data' => $reply['postback'],
                        'displayText' => $reply['display_text'] ?? $reply['label'],
                    ],
                ];
            } else {
                // Message button with label/text
                $label = $reply['label'] ?? $reply['text'] ?? '';
                $items[] = [
                    'type' => 'action',
                    'action' => [
                        'type' => 'message',
                        'label' => mb_substr($label, 0, 20),
                        'text' => $reply['text'] ?? $label,
                    ],
                ];
            }
        }

        // LINE API: สูงสุด 13 Quick Reply items
        return array_slice($items, 0, 13);
    }

    /**
     * อัพโหลดรูปภาพ
     */
    protected function uploadImage(string $imageContent): string
    {
        try {
            if (class_exists(\App\Services\ImageUploadService::class)) {
                $service = app(ImageUploadService::class);

                return $service->uploadFromContent($imageContent, 'fresh-market');
            }

            $filename = 'fresh-market/'.uniqid().'.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageContent);

            return \Illuminate\Support\Facades\Storage::disk('public')->url($filename);
        } catch (\Exception $e) {
            Log::error('FreshMarket: อัพโหลดรูปล้มเหลว', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
