<?php

namespace App\Services;

use App\Models\FreshMarketBuyerPreference;
use App\Models\FreshMarketConversation;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketReferral;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketChannelManager - จัดการการสนทนาตลาดสด
 *
 * Conversation State Machine สำหรับ LINE OA
 * ตาม pattern ของ FortuneChannelManager
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

    // ===== Main Entry Points =====

    /**
     * ประมวลผลข้อความจากผู้ใช้
     *
     * @param  string  $lineUserId  LINE User ID
     * @param  string  $message  ข้อความ
     * @param  array  $extra  ข้อมูลเพิ่มเติม (reply_token, user_profile)
     * @return array{text: string, flex: ?array, quick_replies: ?array}
     */
    public function processMessage(string $lineUserId, string $message, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // ตรวจสอบ referral token
        if (preg_match('/^ref_([A-Za-z0-9]{32})$/i', trim($message), $matches)) {
            $result = $this->handleReferralToken($lineUserId, $matches[1], $conversation);

            // ✅ ส่ง reply สำหรับ referral token
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // เช็คคำสั่งพิเศษ
        $command = $this->detectCommand($message);
        if ($command) {
            $result = $this->handleCommand($command, $lineUserId, $conversation, $extra);

            // ✅ ส่ง reply สำหรับคำสั่งพิเศษ (แก้บั๊กไม่ตอบกลับ)
            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // บันทึกข้อความผู้ใช้
        $conversation->addMessage('user', $message);

        // ใช้ AI ตอบพร้อมบริบท
        try {
            $context = $this->buildContext($conversation);

            $aiResult = $this->aiService->generateResponse(
                $message,
                $conversation->getContext(10),
                $context
            );

            // บันทึกคำตอบ AI
            $conversation->addMessage('assistant', $aiResult['response'], [
                'tokens_used' => $aiResult['tokens_used'],
                'ai_provider' => $aiResult['provider'],
                'ai_model' => $aiResult['model'],
            ]);

            // วิเคราะห์ intent จาก AI
            $intent = $this->aiService->parseSearchIntent($message);

            // จัดการตาม intent
            $result = $this->handleIntent($intent, $conversation, $aiResult['response'], $extra);
        } catch (\Exception $e) {
            // ✅ Fallback: ถ้า AI ล้มเหลว ยังตอบผู้ใช้ได้
            Log::error('FreshMarketChannelManager: AI error', [
                'user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);

            $result = [
                'text' => "สวัสดีค่ะ! พี่ตลาดรับทราบข้อความแล้วค่ะ 😊\n\nตอนนี้ระบบ AI กำลังปรับปรุง ลองพิมพ์ \"ช่วยเหลือ\" เพื่อดูคำสั่งที่ใช้ได้ค่ะ",
            ];
        }

        // ส่งข้อความกลับ
        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    /**
     * ประมวลผลรูปภาพ (สำหรับลงขายสินค้า)
     *
     * @param  string  $lineUserId  LINE User ID
     * @param  string  $messageId  LINE Message ID ของรูป
     * @param  array  $extra  ข้อมูลเพิ่มเติม
     * @return array
     */
    public function processImage(string $lineUserId, string $messageId, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // ดาวน์โหลดรูปจาก LINE
        $imageContent = $this->lineService->getMessageContent($messageId);

        if (! $imageContent) {
            $result = ['text' => 'ขอโทษค่ะ ไม่สามารถรับรูปภาพได้ กรุณาลองส่งใหม่อีกครั้งค่ะ 🙏'];

            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // อัพโหลดรูปไปเก็บ
        $imageUrl = $this->uploadImage($imageContent);

        // เก็บ URL รูปใน context
        $currentContext = $conversation->context ?? [];
        $pendingImages = $currentContext['pending_images'] ?? [];
        $pendingImages[] = $imageUrl;
        $conversation->updateState($conversation->conversation_state, [
            'pending_images' => $pendingImages,
        ]);

        // ตอบกลับ
        $imageCount = count($pendingImages);
        $text = "📸 ได้รับรูปภาพที่ {$imageCount} แล้วค่ะ!\n\n";

        // ถ้าเป็นรูปแรก ถามรายละเอียด
        if ($imageCount === 1) {
            $text .= "บอกพี่ตลาดได้เลยค่ะว่า:\n";
            $text .= "• ชื่อสินค้า\n";
            $text .= "• ราคา และหน่วย (เช่น กก. ถุง กำ)\n\n";
            $text .= "หรือจะส่งรูปเพิ่มก็ได้ค่ะ (สูงสุด 5 รูป)";
        } else {
            $text .= "ส่งรูปเพิ่มได้อีก หรือบอกรายละเอียดสินค้าได้เลยค่ะ";
        }

        $conversation->updateState(FreshMarketConversation::STATE_LISTING);

        $result = ['text' => $text];

        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    /**
     * ประมวลผลพิกัด GPS
     *
     * @param  string  $lineUserId  LINE User ID
     * @param  float  $lat  ละติจูด
     * @param  float  $lng  ลองจิจูด
     * @param  array  $extra  ข้อมูลเพิ่มเติม
     * @return array
     */
    public function processLocation(string $lineUserId, float $lat, float $lng, array $extra = []): array
    {
        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $replyToken = $extra['reply_token'] ?? null;

        // อัพเดทพิกัด
        $conversation->updateSearchLocation($lat, $lng);

        $state = $conversation->conversation_state;

        // ถ้ากำลังลงขาย → บันทึกพิกัดร้าน
        if ($state === FreshMarketConversation::STATE_LISTING) {
            $conversation->updateState($state, [
                'listing_latitude' => $lat,
                'listing_longitude' => $lng,
            ]);

            $result = [
                'text' => "📍 ได้รับพิกัดแล้วค่ะ!\n\nบอกรายละเอียดสินค้าที่จะลงขายได้เลยค่ะ (ชื่อ, ราคา, หน่วย)",
            ];

            if ($replyToken) {
                $this->sendReply($replyToken, $result);
            }

            return $result;
        }

        // ถ้าเป็นผู้ซื้อ → ค้นหาสินค้าใกล้ตัว
        $listings = $this->marketService->searchListings($lat, $lng);

        if ($listings->isEmpty()) {
            $radius = $this->settings->default_search_radius_km;
            $result = [
                'text' => "📍 ได้รับตำแหน่งแล้วค่ะ\n\nยังไม่มีสินค้าในรัศมี {$radius} กม. ค่ะ 😅\nลองบอกพี่ตลาดว่าอยากได้อะไร จะช่วยหาให้ค่ะ!",
            ];
        } else {
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

            $result = [
                'text' => "📍 สินค้าใกล้คุณ ({$listings->count()} รายการ)",
                'flex' => $flexCarousel,
                'flex_alt_text' => "สินค้าใกล้ตัว {$listings->count()} รายการ",
            ];
        }

        $conversation->updateState(FreshMarketConversation::STATE_BROWSING);

        if ($replyToken) {
            $this->sendReply($replyToken, $result);
        }

        return $result;
    }

    // ===== Follow/Unfollow =====

    /**
     * ผู้ใช้เพิ่มเพื่อน (Follow)
     */
    public function handleFollow(string $lineUserId, array $extra = []): void
    {
        $replyToken = $extra['reply_token'] ?? null;

        // สร้าง conversation
        FreshMarketConversation::getOrCreate($lineUserId);

        // ตรวจสอบ referral
        $referral = FreshMarketReferral::findByReferredLineUser($lineUserId);
        if ($referral && $referral->status === 'pending') {
            $referral->markAsFollowed($lineUserId);
        }

        // ส่งข้อความต้อนรับ
        $welcomeMessage = $this->settings->welcome_message ?? "สวัสดีค่ะ! ยินดีต้อนรับสู่ตลาดสดไทยพร้อม 🏪";

        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, [
                ['type' => 'text', 'text' => $welcomeMessage],
            ]);
        }
    }

    /**
     * ผู้ใช้ลบเพื่อน (Unfollow)
     */
    public function handleUnfollow(string $lineUserId): void
    {
        Log::info('FreshMarket: User unfollowed', ['line_user_id' => $lineUserId]);
    }

    // ===== Postback Actions =====

    /**
     * จัดการ Postback (กดปุ่ม Flex)
     */
    public function handlePostback(string $lineUserId, string $data, array $extra = []): void
    {
        $replyToken = $extra['reply_token'] ?? null;

        // Parse postback data
        parse_str($data, $params);
        $action = $params['action'] ?? '';

        switch ($action) {
            case 'order':
                $this->handleOrderPostback($lineUserId, $params, $replyToken);
                break;

            case 'confirm_order':
                $this->handleConfirmOrderPostback($lineUserId, $params, $replyToken);
                break;

            case 'cancel_order':
                $this->handleCancelOrderPostback($lineUserId, $params, $replyToken);
                break;

            default:
                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => 'ขอโทษค่ะ พี่ตลาดไม่เข้าใจคำสั่งนี้ 🤔'],
                    ]);
                }
        }
    }

    // ===== Internal Methods =====

    /**
     * ตรวจจับคำสั่งพิเศษ
     */
    protected function detectCommand(string $message): ?string
    {
        $message = mb_strtolower(trim($message));

        $commands = [
            'ลงขาย' => 'sell',
            'ขายของ' => 'sell',
            'อยากขาย' => 'sell',
            'อยากซื้อ' => 'buy',
            'ซื้อ' => 'buy',
            'หาของ' => 'buy',
            'ค้นหา' => 'search',
            'หา' => 'search',
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
            case 'sell':
                $conversation->updateState(FreshMarketConversation::STATE_LISTING, []);

                return [
                    'text' => "🏷️ ลงขายสินค้า\n\nพี่ตลาดพร้อมช่วยลงขายค่ะ!\n\n📸 ส่งรูปสินค้ามาเลยค่ะ\n📍 แล้วก็ส่งพิกัดร้านมาด้วยนะคะ\n💰 แล้วบอกราคาและหน่วยขาย\n\nเริ่มจากส่งรูปมาก่อนเลยค่ะ!",
                ];

            case 'buy':
            case 'search':
                $conversation->updateState(FreshMarketConversation::STATE_SEARCHING, []);

                return [
                    'text' => "🛒 ค้นหาสินค้า\n\n📍 ส่งตำแหน่งมาให้พี่ตลาดเลยค่ะ\nจะได้หาของใกล้บ้านให้นะคะ\n\nหรือจะบอกว่าอยากได้อะไร พี่ตลาดจะช่วยหาให้ค่ะ 😊",
                ];

            case 'my_orders':
                return [
                    'text' => "📦 ออเดอร์ของคุณ\n\nดูออเดอร์ทั้งหมดได้ที่:\n".url('/taladsod/orders')."\n\nหรือบอกเลขออเดอร์มาได้ค่ะ",
                ];

            case 'help':
                return [
                    'text' => "📋 คำสั่งที่ใช้ได้\n\n🛒 \"อยากซื้อ\" - ค้นหาสินค้าใกล้ตัว\n🏷️ \"ลงขาย\" - ลงขายสินค้า\n📦 \"ออเดอร์\" - ดูออเดอร์\n📍 ส่งตำแหน่ง - หาสินค้าใกล้ตัว\n📸 ส่งรูป - ลงขายสินค้า\n\nหรือจะพิมพ์ถามอะไรก็ได้ค่ะ พี่ตลาดช่วยเองค่ะ 😊",
                ];

            case 'greeting':
                return [
                    'text' => "สวัสดีค่ะ! 🏪 พี่ตลาดยินดีให้บริการค่ะ\n\nวันนี้จะซื้อหรือจะขายคะ? 😊",
                ];

            default:
                return ['text' => 'พี่ตลาดไม่เข้าใจค่ะ ลองพิมพ์ "ช่วยเหลือ" ดูนะคะ'];
        }
    }

    /**
     * จัดการ intent จาก AI
     */
    protected function handleIntent(array $intent, FreshMarketConversation $conversation, string $aiResponse, array $extra): array
    {
        // ถ้า AI ตอบแล้ว ใช้คำตอบ AI เป็นหลัก
        return ['text' => $aiResponse];
    }

    /**
     * สร้างบริบทสำหรับ AI
     */
    protected function buildContext(FreshMarketConversation $conversation): array
    {
        $context = [
            'role' => $conversation->role,
            'conversation_state' => $conversation->conversation_state,
        ];

        // ถ้ามีพิกัดล่าสุด
        if ($conversation->last_search_latitude) {
            $listings = $this->marketService->searchListings(
                $conversation->last_search_latitude,
                $conversation->last_search_longitude,
                5, // ค้นหาแค่ 5 กม. สำหรับบริบท
            );

            if ($listings->isNotEmpty()) {
                $listingText = $listings->take(5)->map(function ($l) {
                    return "- {$l->title} ฿{$l->price}/{$l->unit} ({$l->distance_km}กม.)";
                })->implode("\n");

                $context['nearby_listings'] = $listingText;
            }
        }

        return $context;
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
     * ส่งข้อความกลับทาง LINE
     */
    protected function sendReply(string $replyToken, array $result): void
    {
        $messages = [];

        // ข้อความ text
        if (! empty($result['text'])) {
            $messages[] = ['type' => 'text', 'text' => $result['text']];
        }

        // Flex message
        if (! empty($result['flex'])) {
            $messages[] = [
                'type' => 'flex',
                'altText' => $result['flex_alt_text'] ?? 'ตลาดสดไทยพร้อม',
                'contents' => $result['flex'],
            ];
        }

        if (! empty($messages)) {
            $this->lineService->replyMessage($replyToken, $messages);
        }
    }

    /**
     * จัดการ postback สั่งซื้อ
     */
    protected function handleOrderPostback(string $lineUserId, array $params, ?string $replyToken): void
    {
        $listingId = $params['listing_id'] ?? null;

        if (! $listingId) {
            return;
        }

        $listing = FreshMarketListing::find($listingId);

        if (! $listing || ! $listing->isAvailableForPurchase()) {
            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => 'ขอโทษค่ะ สินค้านี้ไม่พร้อมขายแล้วค่ะ 😅'],
                ]);
            }

            return;
        }

        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $conversation->updateState(FreshMarketConversation::STATE_ORDERING, [
            'ordering_listing_id' => $listing->id,
        ]);

        $text = "🛒 สั่งซื้อ: {$listing->title}\n\n";
        $text .= "💰 ราคา: ฿".number_format($listing->price, 0)." / {$listing->unit}\n";
        $text .= "🏪 ร้าน: ".($listing->seller?->shop_name ?? 'ร้านค้า')."\n\n";
        $text .= "จะสั่งกี่ {$listing->unit} คะ? แล้วจะนัดรับเองหรือให้ส่งคะ?";

        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, [
                ['type' => 'text', 'text' => $text],
            ]);
        }
    }

    /**
     * จัดการ postback ยืนยันออเดอร์
     */
    protected function handleConfirmOrderPostback(string $lineUserId, array $params, ?string $replyToken): void
    {
        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, [
                ['type' => 'text', 'text' => '✅ ยืนยันคำสั่งซื้อเรียบร้อยค่ะ! พี่ตลาดจะแจ้งผู้ขายให้ทันทีค่ะ 📞'],
            ]);
        }
    }

    /**
     * จัดการ postback ยกเลิกออเดอร์
     */
    protected function handleCancelOrderPostback(string $lineUserId, array $params, ?string $replyToken): void
    {
        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, [
                ['type' => 'text', 'text' => '❌ ยกเลิกคำสั่งซื้อเรียบร้อยค่ะ ไว้มาอุดหนุนใหม่นะคะ 😊'],
            ]);
        }
    }

    /**
     * อัพโหลดรูปภาพ
     */
    protected function uploadImage(string $imageContent): string
    {
        try {
            // ใช้ ImageUploadService ถ้ามี
            if (class_exists(\App\Services\ImageUploadService::class)) {
                $service = app(ImageUploadService::class);

                return $service->uploadFromContent($imageContent, 'fresh-market');
            }

            // Fallback: เก็บใน storage
            $filename = 'fresh-market/'.uniqid().'.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageContent);

            return \Illuminate\Support\Facades\Storage::disk('public')->url($filename);
        } catch (\Exception $e) {
            Log::error('FreshMarket: อัพโหลดรูปล้มเหลว', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
